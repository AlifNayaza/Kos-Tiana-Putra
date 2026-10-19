<?php

namespace App\Http\Controllers\Pengelola;

use App\Http\Controllers\Controller;
use App\Models\AppNotification;
use App\Models\RentBill;
use App\Models\TenantProfile;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class RentManagementController extends Controller
{
    /**
     * Tampilkan daftar tagihan sewa kos dengan filter bulan & status (FR-04).
     */
    public function index(Request $request)
    {
        $currentMonth = Carbon::now()->month;
        $currentYear = Carbon::now()->year;

        $monthFilter = $request->query('month', $currentMonth);
        $yearFilter = $request->query('year', $currentYear);
        $statusFilter = $request->query('status', 'semua');
        $search = $request->query('q');

        $query = RentBill::with(['tenantProfile.user', 'room', 'confirmer']);

        if ($monthFilter && $monthFilter !== 'semua') {
            $query->where('month', (int) $monthFilter);
        }

        if ($yearFilter && $yearFilter !== 'semua') {
            $query->where('year', (int) $yearFilter);
        }

        if ($statusFilter && $statusFilter !== 'semua') {
            $query->where('status', $statusFilter);
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('bill_code', 'like', "%{$search}%")
                  ->orWhereHas('room', function ($rq) use ($search) {
                      $rq->where('room_number', 'like', "%{$search}%");
                  })
                  ->orWhereHas('tenantProfile.user', function ($uq) use ($search) {
                      $uq->where('name', 'like', "%{$search}%");
                  });
            });
        }

        $bills = $query->latest('due_date')->paginate(15);

        // Ringkasan Finansial Tagihan
        $stats = [
            'total_bills'   => RentBill::where('year', $yearFilter)->where('month', $monthFilter)->count(),
            'lunas_sum'     => RentBill::where('year', $yearFilter)->where('month', $monthFilter)->where('status', 'lunas')->sum('amount'),
            'menunggu_sum'  => RentBill::where('year', $yearFilter)->where('month', $monthFilter)->whereIn('status', ['menunggu', 'menunggak'])->sum('amount'),
            'need_verify'   => RentBill::where('status', 'menunggu')->whereNotNull('payment_proof')->count(),
        ];

        return view('pengelola.tagihan.index', compact('bills', 'stats', 'monthFilter', 'yearFilter', 'statusFilter', 'search'));
    }

    /**
     * Konfirmasi pelunasan pembayaran sewa secara manual oleh Pengelola (FR-05).
     */
    public function confirmPayment(Request $request, RentBill $bill)
    {
        $pengelola = Auth::user();

        $bill->update([
            'status'         => 'lunas',
            'payment_date'   => $bill->payment_date ?: now(),
            'payment_method' => $bill->payment_method ?: 'Transfer Bank / Tunai Terverifikasi',
            'confirmed_by'   => $pengelola->id,
            'notes'          => 'Lunas diverifikasi oleh ' . $pengelola->name . ' pada ' . now()->format('d/m/Y H:i'),
        ]);

        // Kirim notifikasi ke penghuni
        if ($bill->tenantProfile && $bill->tenantProfile->user_id) {
            AppNotification::create([
                'user_id'    => $bill->tenantProfile->user_id,
                'title'      => 'Pembayaran Sewa Terverifikasi Lunas!',
                'message'    => "Tagihan sewa bulan {$bill->period_label} ({$bill->formatted_amount}) telah berhasil diverifikasi oleh {$pengelola->name}.",
                'type'       => 'rent',
                'action_url' => route('penghuni.tagihan'),
                'is_read'    => false,
            ]);
        }

        return redirect()->back()
            ->with('success', "Pembayaran tagihan {$bill->bill_code} ({$bill->room->room_number}) berhasil dikonfirmasi Lunas.");
    }

    /**
     * Tandai tagihan menjadi status Menunggak jika telah melewati jatuh tempo.
     */
    public function markOverdue(RentBill $bill)
    {
        $bill->update(['status' => 'menunggak']);

        if ($bill->tenantProfile && $bill->tenantProfile->user_id) {
            AppNotification::create([
                'user_id'    => $bill->tenantProfile->user_id,
                'title'      => 'Peringatan Tagihan Menunggak',
                'message'    => "Tagihan sewa bulan {$bill->period_label} telah melewati jatuh tempo. Mohon segera melunasi pembayaran.",
                'type'       => 'rent',
                'action_url' => route('penghuni.tagihan'),
                'is_read'    => false,
            ]);
        }

        return redirect()->back()
            ->with('warning', "Tagihan {$bill->bill_code} ditandai sebagai Menunggak.");
    }

    /**
     * Generate tagihan sewa massal 1-Klik untuk seluruh penghuni aktif pada bulan berjalan.
     */
    public function generateMonthlyBills(Request $request)
    {
        $request->validate([
            'month'    => ['required', 'integer', 'between:1,12'],
            'year'     => ['required', 'integer', 'min:2025', 'max:2030'],
            'due_date' => ['required', 'date'],
        ]);

        $month = (int) $request->input('month');
        $year = (int) $request->input('year');
        $dueDate = $request->input('due_date');

        $activeTenants = TenantProfile::with('room')
            ->where('is_active', true)
            ->whereNotNull('room_id')
            ->get();

        $generatedCount = 0;

        DB::transaction(function () use ($activeTenants, $month, $year, $dueDate, &$generatedCount) {
            foreach ($activeTenants as $tenant) {
                // Cek apakah tagihan untuk bulan & tahun ini sudah pernah dibuat
                $exists = RentBill::where('tenant_profile_id', $tenant->id)
                    ->where('year', $year)
                    ->where('month', $month)
                    ->exists();

                if (!$exists) {
                    $cleanRoom = str_replace('Kamar ', '', $tenant->room->room_number);
                    $billCode = sprintf('TAG-%d%02d-%s', $year, $month, $cleanRoom);

                    $bill = RentBill::create([
                        'bill_code'         => $billCode,
                        'tenant_profile_id' => $tenant->id,
                        'room_id'           => $tenant->room_id,
                        'month'             => $month,
                        'year'              => $year,
                        'amount'            => $tenant->room->monthly_price,
                        'due_date'          => $dueDate,
                        'status'            => 'menunggu',
                        'notes'             => 'Tagihan sewa bulanan otomatis',
                    ]);

                    // Kirim notifikasi ke penghuni
                    AppNotification::create([
                        'user_id'    => $tenant->user_id,
                        'title'      => "Tagihan Sewa Bulan {$bill->period_label} Terbit",
                        'message'    => "Tagihan sewa kamar Anda sebesar {$bill->formatted_amount} telah terbit. Jatuh tempo: " . Carbon::parse($dueDate)->format('d M Y') . ".",
                        'type'       => 'rent',
                        'action_url' => route('penghuni.tagihan'),
                        'is_read'    => false,
                    ]);

                    $generatedCount++;
                }
            }
        });

        return redirect()->route('pengelola.tagihan.index', ['month' => $month, 'year' => $year])
            ->with('success', "Berhasil men-generate {$generatedCount} tagihan sewa baru untuk seluruh penghuni aktif.");
    }
}

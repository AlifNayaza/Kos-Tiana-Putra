<?php

namespace App\Http\Controllers\Pengelola;

use App\Http\Controllers\Controller;
use App\Models\RentBill;
use App\Services\NotificationService;
use App\Services\RentBillService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class RentManagementController extends Controller
{
    public function __construct(
        private RentBillService $bills,
        private NotificationService $notifier,
    ) {
    }

    /**
     * Tampilkan daftar tagihan sewa kos dengan filter bulan & status (FR-04).
     */
    public function index(Request $request)
    {
        $monthFilter = $request->query('month', Carbon::now()->month);
        $yearFilter = $request->query('year', Carbon::now()->year);
        $statusFilter = $request->query('status', 'semua');
        $search = $request->query('q');

        $monthSelected = $monthFilter && $monthFilter !== 'semua';
        $yearSelected = $yearFilter && $yearFilter !== 'semua';

        $query = RentBill::with(['tenantProfile.user', 'room', 'confirmer']);

        if ($monthSelected) {
            $query->where('month', (int) $monthFilter);
        }

        if ($yearSelected) {
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

        $bills = $query->latest('due_date')->paginate(15)->withQueryString();

        // Ringkasan finansial mengikuti filter periode ("semua" = tanpa batasan)
        $periodQuery = fn () => RentBill::query()
            ->when($monthSelected, fn ($q) => $q->where('month', (int) $monthFilter))
            ->when($yearSelected, fn ($q) => $q->where('year', (int) $yearFilter));

        $period = $periodQuery()
            ->selectRaw("COUNT(*) as total, COALESCE(SUM(CASE WHEN status = 'lunas' THEN amount ELSE 0 END), 0) as lunas, COALESCE(SUM(CASE WHEN status IN ('menunggu','menunggak') THEN amount ELSE 0 END), 0) as open_amount")
            ->first();

        $stats = [
            'total_bills'  => (int) $period->total,
            'lunas_sum'    => (int) $period->lunas,
            'menunggu_sum' => (int) $period->open_amount,
            'need_verify'  => RentBill::where('status', 'menunggu')->whereNotNull('payment_proof')->count(),
        ];

        return view('pengelola.tagihan.index', compact('bills', 'stats', 'monthFilter', 'yearFilter', 'statusFilter', 'search'));
    }

    /**
     * Konfirmasi pelunasan pembayaran sewa secara manual oleh Pengelola (FR-05).
     */
    public function confirmPayment(Request $request, RentBill $bill)
    {
        $pengelola = Auth::user();

        $confirmed = DB::transaction(function () use ($bill, $pengelola) {
            $fresh = RentBill::whereKey($bill->id)->lockForUpdate()->firstOrFail();

            if ($fresh->status === 'lunas') {
                return false;
            }

            $stamp = 'Lunas diverifikasi oleh ' . $pengelola->name . ' pada ' . now()->format('d/m/Y H:i');

            $fresh->update([
                'status'         => 'lunas',
                'payment_date'   => $fresh->payment_date ?: now(),
                'payment_method' => $fresh->payment_method ?: 'Transfer Bank / Tunai Terverifikasi',
                'confirmed_by'   => $pengelola->id,
                // Catatan penghuni dipertahankan, jejak verifikasi ditambahkan.
                'notes'          => trim(($fresh->notes ? $fresh->notes . ' • ' : '') . $stamp),
            ]);

            if ($fresh->tenantProfile?->user_id) {
                $this->notifier->notify(
                    $fresh->tenantProfile->user_id,
                    'Pembayaran Sewa Terverifikasi Lunas!',
                    "Tagihan sewa bulan {$fresh->period_label} ({$fresh->formatted_amount}) telah berhasil diverifikasi oleh {$pengelola->name}.",
                    'rent',
                    route('penghuni.tagihan'),
                );
            }

            return true;
        });

        if (!$confirmed) {
            return redirect()->back()->with('info', "Tagihan {$bill->bill_code} sudah berstatus Lunas sebelumnya.");
        }

        return redirect()->back()
            ->with('success', "Pembayaran tagihan {$bill->bill_code} ({$bill->room->room_number}) berhasil dikonfirmasi Lunas.");
    }

    /**
     * Tandai tagihan menjadi status Menunggak jika telah melewati jatuh tempo.
     * Tagihan yang sudah lunas tidak dapat diturunkan statusnya.
     */
    public function markOverdue(RentBill $bill)
    {
        if (!$this->bills->markOverdue($bill)) {
            return redirect()->back()
                ->with('info', "Tagihan {$bill->bill_code} tidak diubah: hanya tagihan berstatus Menunggu yang dapat ditandai Menunggak.");
        }

        return redirect()->back()
            ->with('warning', "Tagihan {$bill->bill_code} ditandai sebagai Menunggak.");
    }

    /**
     * Generate tagihan sewa massal 1-Klik untuk seluruh penghuni aktif pada periode terpilih.
     */
    public function generateMonthlyBills(Request $request)
    {
        $request->validate([
            'month'    => ['required', 'integer', 'between:1,12'],
            'year'     => ['required', 'integer', 'min:2025', 'max:2035'],
            'due_date' => ['required', 'date'],
        ]);

        $month = (int) $request->input('month');
        $year = (int) $request->input('year');

        $generatedCount = $this->bills->generateForMonth($month, $year, $request->input('due_date'));

        return redirect()->route('pengelola.tagihan.index', ['month' => $month, 'year' => $year])
            ->with('success', "Berhasil men-generate {$generatedCount} tagihan sewa baru untuk seluruh penghuni aktif.");
    }
}

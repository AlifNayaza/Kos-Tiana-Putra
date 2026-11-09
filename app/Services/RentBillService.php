<?php

namespace App\Services;

use App\Models\RentBill;
use App\Models\TenantProfile;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class RentBillService
{
    public function __construct(private NotificationService $notifier)
    {
    }

    /**
     * Terbitkan tagihan sewa untuk semua penghuni aktif pada bulan/tahun tertentu.
     * Penghuni yang sudah punya tagihan periode tersebut dilewati (idempoten).
     *
     * @return int jumlah tagihan baru
     */
    public function generateForMonth(int $month, int $year, Carbon|string $dueDate): int
    {
        $due = Carbon::parse($dueDate);
        $created = 0;

        $tenants = TenantProfile::with('room')
            ->where('is_active', true)
            ->whereNotNull('room_id')
            ->get();

        DB::transaction(function () use ($tenants, $month, $year, $due, &$created) {
            foreach ($tenants as $tenant) {
                $exists = RentBill::where('tenant_profile_id', $tenant->id)
                    ->where('year', $year)
                    ->where('month', $month)
                    ->exists();

                if ($exists || !$tenant->room) {
                    continue;
                }

                $cleanRoom = str_replace('Kamar ', '', $tenant->room->room_number);

                $bill = RentBill::create([
                    'bill_code'         => sprintf('TAG-%d%02d-%s', $year, $month, $cleanRoom),
                    'tenant_profile_id' => $tenant->id,
                    'room_id'           => $tenant->room_id,
                    'month'             => $month,
                    'year'              => $year,
                    'amount'            => $tenant->room->monthly_price,
                    'due_date'          => $due->toDateString(),
                    'status'            => 'menunggu',
                    'notes'             => 'Tagihan sewa bulanan otomatis',
                ]);

                $this->notifier->notify(
                    $tenant->user_id,
                    "Tagihan Sewa Bulan {$bill->period_label} Terbit",
                    "Tagihan sewa kamar Anda sebesar {$bill->formatted_amount} telah terbit. Jatuh tempo: " . $due->format('d M Y') . '.',
                    'rent',
                    route('penghuni.tagihan'),
                );

                $created++;
            }
        });

        return $created;
    }

    /**
     * Tandai tagihan yang lewat jatuh tempo sebagai menunggak (hanya yang masih "menunggu").
     *
     * @return int jumlah tagihan yang diubah
     */
    public function markOverdueBills(?Carbon $today = null): int
    {
        $today = ($today ?? Carbon::today())->toDateString();
        $count = 0;

        RentBill::with('tenantProfile')
            ->where('status', 'menunggu')
            ->whereDate('due_date', '<', $today)
            ->each(function (RentBill $bill) use (&$count) {
                if ($this->markOverdue($bill)) {
                    $count++;
                }
            });

        return $count;
    }

    /**
     * Tandai satu tagihan menunggak. Tagihan yang sudah lunas atau sudah menunggak
     * tidak diubah. Mengembalikan true jika status benar-benar berubah.
     */
    public function markOverdue(RentBill $bill): bool
    {
        return DB::transaction(function () use ($bill) {
            $fresh = RentBill::whereKey($bill->id)->lockForUpdate()->first();

            if (!$fresh || $fresh->status !== 'menunggu') {
                return false;
            }

            $fresh->update(['status' => 'menunggak']);

            $userId = $fresh->tenantProfile?->user_id ?? $bill->tenantProfile?->user_id;
            if ($userId) {
                $this->notifier->notify(
                    $userId,
                    'Peringatan Tagihan Menunggak',
                    "Tagihan sewa bulan {$fresh->period_label} telah melewati jatuh tempo. Mohon segera melunasi pembayaran.",
                    'rent',
                    route('penghuni.tagihan'),
                );
            }

            return true;
        });
    }
}

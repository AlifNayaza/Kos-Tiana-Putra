<?php

namespace App\Console\Commands;

use App\Services\RentBillService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class GenerateMonthlyBillsCommand extends Command
{
    protected $signature = 'tagihan:terbitkan
                            {--month= : Bulan (1-12), default bulan berjalan}
                            {--year= : Tahun, default tahun berjalan}
                            {--due-day= : Tanggal jatuh tempo, default dari config kos.billing.due_day}';

    protected $description = 'Terbitkan tagihan sewa bulanan untuk semua penghuni aktif (aman dijalankan berulang)';

    public function handle(RentBillService $bills): int
    {
        $now = Carbon::now();
        $month = (int) ($this->option('month') ?: $now->month);
        $year = (int) ($this->option('year') ?: $now->year);
        $dueDay = (int) ($this->option('due-day') ?: config('kos.billing.due_day'));

        if ($month < 1 || $month > 12 || $year < 2020 || $dueDay < 1 || $dueDay > 28) {
            $this->error('Parameter tidak valid. Bulan 1-12, tahun >= 2020, tanggal jatuh tempo 1-28.');
            return self::FAILURE;
        }

        $dueDate = Carbon::create($year, $month, $dueDay);
        $count = $bills->generateForMonth($month, $year, $dueDate);

        $this->info("{$count} tagihan baru diterbitkan untuk {$month}/{$year} (jatuh tempo {$dueDate->toDateString()}).");

        return self::SUCCESS;
    }
}

<?php

namespace App\Console\Commands;

use App\Services\RentBillService;
use Illuminate\Console\Command;

class MarkOverdueBillsCommand extends Command
{
    protected $signature = 'tagihan:tandai-menunggak';

    protected $description = 'Tandai tagihan sewa yang lewat jatuh tempo sebagai menunggak dan beri tahu penghuni';

    public function handle(RentBillService $bills): int
    {
        $count = $bills->markOverdueBills();

        $this->info("{$count} tagihan ditandai menunggak.");

        return self::SUCCESS;
    }
}

<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;

/**
 * Ekspresi SQL untuk mengelompokkan per bulan yang jalan di MySQL (produksi) maupun SQLite (test).
 */
class SqlDate
{
    /**
     * Ekspresi "YYYY-MM" dari kolom tanggal, untuk GROUP BY.
     */
    public static function yearMonth(string $column): string
    {
        return DB::connection()->getDriverName() === 'sqlite'
            ? "strftime('%Y-%m', {$column})"
            : "DATE_FORMAT({$column}, '%Y-%m')";
    }
}

<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * Menjalankan prediksi stok (skrip Python) di luar siklus request web.
 * Dipanggil dengan dispatchAfterResponse() agar tidak butuh worker terpisah,
 * namun halaman pengelola tidak menunggu proses Python selesai.
 */
class RunStockPrediction implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, Queueable;

    public const RUNNING_CACHE_KEY = 'stok_prediksi_running';
    public const LAST_ERROR_CACHE_KEY = 'stok_prediksi_last_error';

    public int $timeout = 120;
    public int $tries = 1;
    public int $uniqueFor = 300;

    public function handle(): void
    {
        Cache::put(self::RUNNING_CACHE_KEY, true, now()->addMinutes(5));
        Cache::forget(self::LAST_ERROR_CACHE_KEY);

        try {
            $exitCode = Artisan::call('stok:prediksi');
            $output = trim(Artisan::output());

            if ($exitCode !== 0) {
                throw new RuntimeException("stok:prediksi gagal (exit {$exitCode}): {$output}");
            }
        } catch (\Throwable $e) {
            Log::error('Prediksi stok gagal: ' . $e->getMessage());
            Cache::put(self::LAST_ERROR_CACHE_KEY, mb_substr(strip_tags($e->getMessage()), 0, 300), now()->addHour());
            throw $e;
        } finally {
            Cache::forget(self::RUNNING_CACHE_KEY);
        }
    }
}

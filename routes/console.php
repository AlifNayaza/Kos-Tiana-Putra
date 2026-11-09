<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Jadwal harian prediksi stok (Machine Learning Python) - NFR-03 & FR-13
Schedule::command('stok:prediksi')->dailyAt('00:05')->withoutOverlapping();

// Tagihan sewa: terbit otomatis tiap tanggal 1, lalu tandai menunggak tiap pagi.
// Memerlukan scheduler aktif (cron: * * * * * php artisan schedule:run).
Schedule::command('tagihan:terbitkan')->monthlyOn(1, '06:00')->withoutOverlapping();
Schedule::command('tagihan:tandai-menunggak')->dailyAt('06:30')->withoutOverlapping();

<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Jadwal Eksekusi Harian Prediksi Stok (Machine Learning Python) - NFR-03 & FR-13
Schedule::command('stok:prediksi')->dailyAt('00:05');

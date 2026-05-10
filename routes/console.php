<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

// Jalankan crawler setiap 1 Jam (bisa diubah sesuai kebutuhan)
// ->withoutOverlapping() SANGAT PENTING: Mencegah crawler menumpuk jika proses sebelumnya belum selesai
Schedule::command('crawler:run')
    ->everyTenMinutes()
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/crawler.log')); // Simpan log output ke file

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Jadwal Petugas Kebersihan Database (Setiap Tengah Malam)
Schedule::command('db:retention')
    ->dailyAt('00:00') // Berjalan otomatis jam 12 malam
    ->appendOutputTo(storage_path('logs/retention.log'));

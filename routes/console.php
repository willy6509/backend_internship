<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
\Illuminate\Support\Facades\Artisan::command('sentinel:cuci', function () {
    $this->info("Memulai proses ulang data SENTINEL...");
    
    // Ambil data yang masih Netral/Kosong
    $records = \Illuminate\Support\Facades\DB::table('crawled_data')
        ->whereNull('ai_sentiment')
        ->orWhere('ai_sentiment', 'Netral')
        ->orWhere('ai_sentiment', '')
        ->get();
        
    $total = $records->count();
    if ($total === 0) {
        $this->info("Aman! Tidak ada data yang perlu dicuci.");
        return;
    }
    
    $this->info("Ditemukan $total data. Memulai pencucian AI...");
    
    foreach ($records as $record) {
        try {
            // Tembak ulang ke API temen lu (timeout diperpanjang jadi 60 detik)
            $response = \Illuminate\Support\Facades\Http::timeout(60)->post('http://103.245.38.28:8000/result/predict', [
                'text' => $record->content
            ]);
            
            if ($response->successful() && isset($response->json()['status']) && $response->json()['status'] === 'success') {
                $result = $response->json()['data'];
                
                // Update datanya ke PostgreSQL
                \Illuminate\Support\Facades\DB::table('crawled_data')->where('id', $record->id)->update([
                    'ai_sentiment' => $result['sentiment'] ?? $record->ai_sentiment,
                    'main_topic'   => $result['topic'] ?? $record->main_topic,
                    'is_emergency' => $result['is_emergency'] ?? $record->is_emergency,
                    'location'     => ($record->location === 'Tidak Diketahui' || empty($record->location)) 
                                        ? ($result['location'] ?? $record->location) 
                                        : $record->location,
                ]);
                $this->info("✅ ID {$record->id} sukses diupdate jadi: " . ($result['sentiment'] ?? 'Netral'));
            }
        } catch (\Exception $e) {
            $this->error("❌ ID {$record->id} gagal: AI timeout atau error.");
        }
    }
    $this->info("Pencucian Selesai Total!");
});

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

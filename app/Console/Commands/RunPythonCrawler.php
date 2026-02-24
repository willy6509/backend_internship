<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;

class RunPythonCrawler extends Command
{
    // Nama perintah untuk dijalankan
    protected $signature = 'crawler:run';
    protected $description = 'Menjalankan script Python Crawler secara otomatis dari Laravel';

    public function handle()
    {
        $this->info("🚀 Memulai Mesin Python Crawler...");

        // Tentukan path ke script Python Anda (menggunakan base_path agar dinamis)
        $scriptPath = base_path('crawlers/x_crawler.py');

        $process = new Process(
            ['python', $scriptPath],
            null, // working directory default
            ['PYTHONIOENCODING' => 'utf-8'] // <-- INI OBATNYA
        );
        
        // Karena crawling butuh waktu lama (ada sleep/jeda), matikan batasan waktu (timeout)
        $process->setTimeout(null); 

        try {
            // Jalankan proses dan tampilkan output Python secara real-time ke log Laravel
            $process->run(function ($type, $buffer) {
                if (Process::ERR === $type) {
                    // Jika ada error dari Python, catat ke console error
                    echo "ERR > ".$buffer;
                } else {
                    // Tampilkan output normal (print) dari Python
                    echo $buffer;
                }
            });

            if (!$process->isSuccessful()) {
                throw new ProcessFailedException($process);
            }

            $this->info("\n✅ Crawling Selesai!");

        } catch (\Exception $e) {
            $this->error("\n❌ Terjadi kesalahan saat menjalankan Python: " . $e->getMessage());
            // Catat ke log Laravel (storage/logs/laravel.log) agar mudah diaudit
            \Illuminate\Support\Facades\Log::error("Python Crawler Error: " . $e->getMessage());
        }
    }
}

<?php

namespace App\Console\Commands;

use App\Models\CrawledData;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class RunDataRetention extends Command
{
    /**
     * Nama perintah yang akan dipanggil oleh sistem
     *
     * @var string
     */
    protected $signature = 'db:retention';

    /**
     * Deskripsi perintah
     *
     * @var string
     */
    protected $description = 'Menjalankan siklus pembersihan data (Soft Delete & Force Delete) untuk menjaga kapasitas PostgreSQL';

    public function handle(): int
    {
        $this->info('Mulai menjalankan Data Retention Policy...');

        try {
            // --- TAHAP 1: ARSIPKAN DATA LAMA (SOFT DELETE) ---
            $sixMonthsAgo = Carbon::now()->subMonths(6);

            $archivedCount = 0;
            CrawledData::where('created_at', '<', $sixMonthsAgo)
                ->chunkById(500, function ($items) use (&$archivedCount) {
                    foreach ($items as $item) {
                        if (method_exists($item, 'trashed') && $item->trashed()) {
                            continue;
                        }

                        $item->delete();
                        $archivedCount++;
                    }
                });

            if ($archivedCount > 0) {
                $this->info("Berhasil mengarsipkan (Soft Delete) {$archivedCount} baris data intelijen lama.");
                Log::info("RunDataRetention: archived {$archivedCount} crawled_data rows.");
            } else {
                $this->info('Tidak ada data lama untuk diarsipkan.');
            }

            // --- TAHAP 2: PEMUSNAHAN PERMANEN (FORCE DELETE) ---
            $threeMonthsAgo = Carbon::now()->subMonths(3);

            $destroyedCount = 0;
            CrawledData::onlyTrashed()
                ->where('deleted_at', '<', $threeMonthsAgo)
                ->chunkById(500, function ($items) use (&$destroyedCount) {
                    foreach ($items as $item) {
                        $item->forceDelete();
                        $destroyedCount++;
                    }
                });

            if ($destroyedCount > 0) {
                $this->info("Berhasil memusnahkan permanen (Force Delete) {$destroyedCount} baris data sampah.");
                Log::info("RunDataRetention: destroyed {$destroyedCount} crawled_data rows.");
            } else {
                $this->info('Tidak ada data sampah untuk dimusnahkan.');
            }

            $this->info('Data Retention selesai dieksekusi! Server PostgreSQL aman.');
        } catch (\Throwable $e) {
            Log::error('RunDataRetention failed: '.$e->getMessage(), ['exception' => $e]);
            $this->error('Terjadi kesalahan saat menjalankan Data Retention. Periksa log aplikasi.');

            return 1;
        }

        return 0;
    }
}

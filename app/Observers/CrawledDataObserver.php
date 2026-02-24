<?php
namespace App\Observers;

use App\Models\CrawledData;
use Illuminate\Support\Facades\Hash;

class CrawledDataObserver
{
    /**
     * Dijalankan TEPAT SEBELUM data di-insert ke database.
     */
    public function creating(CrawledData $crawledData): void
    {
        // 1. Ambil data terakhir untuk mendapatkan previous_hash
        // Menggunakan lockForUpdate() agar aman dari Race Condition jika crawling sangat cepat
        $lastRecord = CrawledData::lockForUpdate()->latest('created_at')->first();
        
        $previousHash = $lastRecord ? $lastRecord->current_hash : 'GENESIS_BLOCK_0000000000000000';
        $crawledData->previous_hash = $previousHash;

        // 2. Kumpulkan data yang akan di-hash (Data Kritis)
        // Jika ada yang mengubah satu huruf saja di DB, kombinasi ini akan menghasilkan hash yang berbeda
        $dataToHash = implode('|', [
            $crawledData->previous_hash,
            $crawledData->type,
            $crawledData->username,
            $crawledData->posted_at,
            $crawledData->content,
            $crawledData->url
        ]);

        // 3. Generate SHA-256 Hash
        $crawledData->current_hash = hash('sha256', $dataToHash);
    }
}
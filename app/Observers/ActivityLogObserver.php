<?php
namespace App\Observers;

use App\Models\ActivityLog;

class ActivityLogObserver
{
    public function creating(ActivityLog $log): void
    {
        // 1. Ambil Hash Terakhir (Chain)
        $lastLog = ActivityLog::latest('created_at')->first();
        $previousHash = $lastLog ? $lastLog->current_hash : 'GENESIS_LOG_HASH_000000000000';
        
        $log->previous_hash = $previousHash;

        // 2. Siapkan data untuk di-seal
        // Gabungkan semua komponen kritikal
        $dataToHash = implode('|', [
            $previousHash,
            $log->user_id,
            $log->event,
            $log->subject_id,
            json_encode($log->properties), // Perubahan data juga di-hash
            $log->created_at
        ]);

        // 3. Generate SHA-256 Signature
        $log->current_hash = hash('sha256', $dataToHash);
    }
}

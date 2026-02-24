<?php

namespace App\Services;

use App\Models\CrawledData;

class DuplicateCheckerService
{
    /**
     * Mengecek apakah konten teks persis sama sudah ada di database.
     */
    public static function isContentExist($content)
    {
        // Cek secara presisi (Exact Match)
        return CrawledData::where('content', $content)->exists();
    }

    /**
     * (OPSIONAL/Masa Depan) Jika Anda ingin AI yang lebih pintar.
     * Mengecek apakah teksnya "mirip" meskipun tidak 100% sama persis.
     */
    public static function isContentSimilar($newContent)
    {
        // Logika untuk mengecek kemiripan kalimat (Fuzzy String Matching)
        // Bisa dikembangkan nanti jika buzzer memodifikasi sedikit teksnya
        return false; 
    }
}
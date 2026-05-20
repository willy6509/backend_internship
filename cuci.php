<?php

echo "Memulai proses ulang data SENTINEL...\n";

// Ambil data yang masih Netral/Kosong
$records = \Illuminate\Support\Facades\DB::table('crawled_data')
    ->whereNull('ai_sentiment')
    ->orWhere('ai_sentiment', 'Netral')
    ->orWhere('ai_sentiment', '')
    ->get();
    
$total = $records->count();
if ($total === 0) {
    echo "Aman! Tidak ada data yang perlu dicuci.\n";
    return;
}

echo "Ditemukan $total data. Memulai pencucian AI...\n";

foreach ($records as $record) {
    try {
        // Tembak ulang ke API FastAPI
        $response = \Illuminate\Support\Facades\Http::timeout(60)->post('http://103.245.38.28:8000/result/predict', [
            'text' => $record->content
        ]);
        
        if ($response->successful() && isset($response->json()['status']) && $response->json()['status'] === 'success') {
            $result = $response->json()['data'];
            
            \Illuminate\Support\Facades\DB::table('crawled_data')->where('id', $record->id)->update([
                'ai_sentiment' => $result['sentiment'] ?? $record->ai_sentiment,
                'main_topic'   => $result['topic'] ?? $record->main_topic,
                'is_emergency' => $result['is_emergency'] ?? $record->is_emergency,
                'location'     => ($record->location === 'Tidak Diketahui' || empty($record->location)) 
                                    ? ($result['location'] ?? $record->location) 
                                    : $record->location,
            ]);
            echo "✅ ID {$record->id} sukses diupdate jadi: " . ($result['sentiment'] ?? 'Netral') . "\n";
        } else {
             echo "⚠️ ID {$record->id} gagal: Response API AI tidak valid.\n";
        }
    } catch (\Exception $e) {
        echo "❌ ID {$record->id} gagal: AI timeout atau error.\n";
    }
}
echo "Pencucian Selesai Total!\n";

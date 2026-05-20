<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CrawledData;
use App\Services\DuplicateCheckerService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class DataController extends Controller
{
    public function index(Request $request)
    {
        $limit = $request->query('limit', 50000);
        $query = CrawledData::select('id', 'type', 'source', 'username', 'posted_at', 'content', 'url', 'ai_sentiment', 'main_topic', 'is_emergency', 'location');

        if ($request->has('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('content', 'ilike', '%'.$search.'%')
                    ->orWhere('username', 'ilike', '%'.$search.'%');
            });
        }

        if ($request->has('start_date') && $request->has('end_date')) {
            $startDate = $request->input('start_date');
            $endDate = $request->input('end_date');
            if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $startDate) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $endDate)) {
                $query->whereBetween('posted_at', [$startDate.' 00:00:00', $endDate.' 23:59:59']);
            }
        }

        $query->orderBy('posted_at', 'desc');
        $data = $query->paginate($limit);

        return response()->json(['success' => true, 'data' => $data], 200);
    }

    public function show($id)
    {
        $data = CrawledData::where('id', $id)->first();
        if (! $data) {
            return response()->json(['success' => false, 'message' => 'Data tidak ditemukan.'], 404);
        }

        $replies = [];
        if ($data->type === 'post') {
            $replies = CrawledData::where('parent_url', $data->url)
                ->select('id', 'username', 'posted_at', 'content', 'url', 'ai_sentiment', 'main_topic')
                ->orderBy('posted_at', 'asc')
                ->get();
        }

        return response()->json(['success' => true, 'data' => ['post' => $data, 'replies' => $replies]], 200);
    }

    public function ingestData(Request $request)
    {
        // FIX FINAL: Tinggalkan Regex. Gunakan bawaan Laravel 'starts_with' yang 100% aman.
        $validated = $request->validate([
            'type' => 'required|in:post,reply',
            'username' => 'required|string',
            'posted_at' => 'required|date_format:Y-m-d H:i:s',
            'content' => 'required|string',
            'url' => 'required|url|starts_with:https://x.com/,https://twitter.com/',
            'parent_url' => 'nullable|url|starts_with:https://x.com/,https://twitter.com/',
        ]);

        $content = $request->input('content');

        if (DuplicateCheckerService::isContentExist($content)) {
            return response()->json([
                'status' => 'ignored',
                'message' => 'Konten teks sudah ada di database, diabaikan.',
                'url' => $request->input('url'),
            ], 200);
        }

        // --- INTEGRASI KE ML FASTAPI ---
        $mlData = [
            'ai_sentiment' => null,
            'main_topic' => null,
            'is_emergency' => false,
            'location' => null,
        ];

        try {
            $response = Http::timeout(10)->post('http://103.245.38.28:8000/result/predict', [
                'text' => $content
            ]);

            if ($response->successful()) {
                $result = $response->json();
                if (isset($result['status']) && $result['status'] === 'success') {
                    $mlData = [
                        'ai_sentiment' => $result['data']['sentiment'] ?? null,
                        'main_topic'   => $result['data']['topic'] ?? null,
                        'is_emergency' => $result['data']['is_emergency'] ?? false,
                        'location'     => $result['data']['location'] ?? null,
                    ];
                }
            } else {
                Log::warning('ML API Error: ' . $response->body());
            }
        } catch (\Exception $e) {
            Log::error('ML API Connection Failed: ' . $e->getMessage());
        }

        // --- AWAL JARING PENGAMAN (FALLBACK LOKASI) ---
        if (empty($mlData['location']) || $mlData['location'] === 'Tidak Diketahui') {
            $jateng_cities = [
                'semarang', 'grobogan', 'rembang', 'demak', 'kendal', 'salatiga', 
                'solo', 'surakarta', 'banyumas', 'magelang', 'pati', 'kudus', 
                'jepara', 'blora', 'sragen', 'boyolali', 'klaten', 'sukoharjo', 
                'wonogiri', 'karanganyar', 'wonosobo', 'purworejo', 'kebumen', 
                'cilacap', 'purbalingga', 'banjarnegara', 'brebes', 'tegal', 
                'pemalang', 'pekalongan', 'batang', 'temanggung'
            ];

            // Pakai variabel $content milik lu, dibikin huruf kecil semua
            $textLower = strtolower($content);

            foreach ($jateng_cities as $city) {
                if (str_contains($textLower, $city)) {
                    // Kalau ketemu, timpa nilai location-nya
                    $mlData['location'] = ucfirst($city);
                    break;
                }
            }
        }
        // --- AKHIR JARING PENGAMAN ---

        // Simpan ke DB beserta hasil ML
        $record = \App\Models\CrawledData::firstOrCreate(
            ['url' => $validated['url']],
            [
                'type' => $validated['type'],
                'source' => 'X',
                'username' => $validated['username'],
                'posted_at' => $validated['posted_at'],
                'content' => $validated['content'],
                'parent_url' => $validated['parent_url'] ?? null,
                'raw_payload' => $request->all(),
                'ai_sentiment' => $mlData['ai_sentiment'],
                'main_topic' => $mlData['main_topic'],
                'is_emergency' => $mlData['is_emergency'],
                'location' => $mlData['location'],
            ]
        );

        if ($record->wasRecentlyCreated) {
            return response()->json(['status' => 'inserted'], 201);
        }

        return response()->json(['status' => 'ignored_duplicate'], 200);
    }

// --- TAMBAHAN UNTUK FITUR VALIDASI ANALYST ---

    public function updateSentiment(Request $request, $id)
    {
        $request->validate([
            'ai_sentiment' => 'sometimes|string|in:Positif,Negatif,Netral',
            'is_validated' => 'sometimes|boolean',
            'validated_by' => 'nullable|string',
        ]);

        $data = \App\Models\CrawledData::find($id);

        if (!$data) {
            return response()->json(['success' => false, 'message' => 'Data tidak ditemukan'], 404);
        }

        $data->update($request->only(['ai_sentiment', 'is_validated', 'validated_by']));

	DB::table('activity_logs')->insert([
            'user_id' => auth()->id() ?? 1, // Jaga-jaga kalau auth belum nyangkut
            'action' => 'VALIDATE_DATA',
            'target' => 'Data ID: ' . $id,
            'ip_address' => request()->ip(),
            'details' => json_encode(['sentiment' => $request->ai_sentiment, 'status' => 'Validated']),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Validasi sentimen berhasil disimpan!',
            'data' => $data
        ]);
    }

    public function destroy($id)
    {
        $data = \App\Models\CrawledData::find($id);

        if (!$data) {
            return response()->json(['success' => false, 'message' => 'Data tidak ditemukan'], 404);
        }

        $data->delete();

	DB::table('activity_logs')->insert([
            'user_id' => auth()->id() ?? 1,
            'action' => 'REJECT_DATA',
            'target' => 'Data ID: ' . $id,
            'ip_address' => request()->ip(),
            'details' => json_encode(['status' => 'Deleted/Rejected as Spam']),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Data hoax/spam berhasil dihapus dari sistem.'
        ]);
    }

}

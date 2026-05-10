<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CrawledData;
use App\Services\DuplicateCheckerService;
use Illuminate\Http\Request;

class DataController extends Controller
{
    // Mengambil data untuk dashboard (Dapat diakses Officer, Analyst, Admin)
    public function index(Request $request)
    {
        $limit = $request->query('limit', 50);

        // Ambil data terbaru, hindari memuat raw_payload yang berat untuk list
        $query = CrawledData::select('id', 'type', 'source', 'username', 'posted_at', 'content', 'url');

        // --- TAMBAHAN 1: Fitur Pencarian (Search) ---
        if ($request->has('search')) {
            $search = $request->input('search');
            // Gunakan kurung function agar OR tidak merusak filter lain
            $query->where(function ($q) use ($search) {
                // Gunakan 'ilike' khusus untuk PostgreSQL (Case Insensitive)
                $q->where('content', 'ilike', '%'.$search.'%')
                    ->orWhere('username', 'ilike', '%'.$search.'%');
            });
        }

        // --- TAMBAHAN 2: Fitur Filter Tanggal ---
        if ($request->has('start_date') && $request->has('end_date')) {
            $startDate = $request->input('start_date');
            $endDate = $request->input('end_date');

            if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $startDate) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $endDate)) {
                $query->whereBetween('posted_at', [
                    $startDate.' 00:00:00',
                    $endDate.' 23:59:59',
                ]);
            }
        }

        // Urutkan dan Paginasi
        $query->orderBy('posted_at', 'desc');
        $data = $query->paginate($limit);

        return response()->json([
            'success' => true,
            'data' => $data,
        ], 200);
    }

    // Mengambil detail laporan/post beserta komentarnya
    public function show($id)
    {
        $data = CrawledData::where('id', $id)->first();

        if (! $data) {
            return response()->json([
                'success' => false,
                'message' => 'Data tidak ditemukan.',
            ], 404);
        }

        // Ambil balasan jika ini adalah post utama
        $replies = [];
        if ($data->type === 'post') {
            $replies = CrawledData::where('parent_url', $data->url)
                ->select('id', 'username', 'posted_at', 'content', 'url')
                ->orderBy('posted_at', 'asc')
                ->get();
        }

        return response()->json([
            'success' => true,
            'data' => [
                'post' => $data,
                'replies' => $replies,
            ],
        ], 200);
    }

    public function ingestData(Request $request)
    {
        // Validasi data yang dikirim oleh Python
        $validated = $request->validate([
            'type' => 'required|in:post,reply',
            'username' => 'required|string',
            'posted_at' => 'required|date_format:Y-m-d H:i:s',
            'content' => 'required|string',
            'url' => 'required|url|regex:/^https:\/\/(x\.com|twitter\.com)\//i',
            'parent_url' => 'nullable|url|regex:/^https:\/\/(x\.com|twitter\.com)\//i',
        ]);

        $content = $request->input('content');

        if (DuplicateCheckerService::isContentExist($content)) {

            // Langsung tolak dan suruh Python lanjut ke data berikutnya
            return response()->json([
                'status' => 'ignored',
                'message' => 'Konten teks sudah ada di database, diabaikan.',
                'url' => $request->input('url'),
            ], 200);
        }

        // Simpan ke DB. Observer Blockchain akan OTOMATIS BERJALAN di sini!
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
            ]
        );

        if ($record->wasRecentlyCreated) {
            return response()->json(['status' => 'inserted'], 201);
        }

        return response()->json(['status' => 'ignored_duplicate'], 200);
    }
}

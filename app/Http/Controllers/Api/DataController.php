<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CrawledDataLedger;
use Illuminate\Http\Request;

class DataController extends Controller
{
    // Mengambil data untuk dashboard (Dapat diakses Officer, Analyst, Admin)
    public function index(Request $request)
    {
        $limit = $request->query('limit', 50);
        
        // Ambil data terbaru, hindari memuat raw_payload yang berat untuk list
        $query = CrawledDataLedger::select('id', 'type', 'source', 'username', 'posted_at', 'content', 'url')
                    ->orderBy('posted_at', 'desc');

        $data = $query->paginate($limit);

        return response()->json([
            'success' => true,
            'data' => $data
        ], 200);
    }

    // Mengambil detail laporan/post beserta komentarnya
    public function show($id)
    {
        $data = CrawledDataLedger::where('id', $id)->first();

        if (!$data) {
            return response()->json([
                'success' => false,
                'message' => 'Data tidak ditemukan.'
            ], 404);
        }

        // Ambil balasan jika ini adalah post utama
        $replies = [];
        if ($data->type === 'post') {
            $replies = CrawledDataLedger::where('parent_url', $data->url)
                        ->select('id', 'username', 'posted_at', 'content', 'url')
                        ->orderBy('posted_at', 'asc')
                        ->get();
        }

        return response()->json([
            'success' => true,
            'data' => [
                'post' => $data,
                'replies' => $replies
            ]
        ], 200);
    }
}

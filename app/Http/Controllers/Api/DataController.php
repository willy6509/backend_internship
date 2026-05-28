<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\CrawledData;
use Illuminate\Support\Facades\DB;

class DataController extends Controller
{
    public function index()
    {
        $data = CrawledData::where('is_validated', false)->orderBy('id', 'desc')->paginate(50);
        return response()->json(['success' => true, 'data' => $data]);
    }

    public function show($id)
    {
        $data = CrawledData::find($id);
        if (!$data) return response()->json(['success' => false, 'message' => 'Data not found'], 404);
        return response()->json(['success' => true, 'data' => $data]);
    }

    public function ingestData(Request $request)
    {
        $request->validate([
            'type'      => 'required|string',
            'username'  => 'required|string',
            'posted_at' => 'required',
            'content'   => 'required|string',
            'url'       => 'required|string',
        ]);

        // Cek duplikat berdasarkan URL
        $existing = CrawledData::where('url', $request->url)->first();
        if ($existing) {
            return response()->json(['success' => true, 'message' => 'Duplicate', 'data' => $existing], 200);
        }

        // Generate hash otomatis
        $currentHash  = hash('sha256', $request->content . $request->url);
        $lastData     = CrawledData::latest()->first();
        $previousHash = $lastData ? $lastData->current_hash : hash('sha256', 'GENESIS');

        $data = CrawledData::create([
            'type'          => $request->type,
            'source'        => $request->source ?? 'X',
            'username'      => $request->username,
            'posted_at'     => $request->posted_at,
            'content'       => $request->content,
            'url'           => $request->url,
            'parent_url'    => $request->parent_url ?? null,
            'raw_payload'   => $request->raw_payload ?? [],
            'current_hash'  => $currentHash,
            'previous_hash' => $previousHash,
        ]);

        return response()->json(['success' => true, 'message' => 'Data saved', 'data' => $data], 201);
    }

    public function keywords()
    {
        $filters = DB::table('crawling_filters')
            ->where('is_active', true)
            ->get();

        // Pisahkan berdasarkan tipe keyword (location vs topic)
        $locations = $filters->where('platform', 'location')->pluck('keyword')->toArray();
        $topics    = $filters->where('platform', 'topic')->pluck('keyword')->toArray();

        // Fallback kalau kosong
        if (empty($locations)) {
            $locations = ['semarang', 'jateng', 'solo', 'magelang', 'banyumas', 'klaten', 'demak', 'pati'];
        }
        if (empty($topics)) {
            $topics = ['polisi', 'oknum', 'polda', 'begal', 'klitih', 'lantas', 'isilop'];
        }

        return response()->json(['success' => true, 'data' => [
            'locations' => $locations,
            'topics'    => $topics,
        ]]);
    }

    public function updateSentiment(Request $request, $id)
    {
        $data = CrawledData::find($id);
        if (!$data) return response()->json(['success' => false, 'message' => 'Data not found'], 404);

        $data->update([
            'ai_sentiment' => $request->ai_sentiment ?? $data->ai_sentiment,
            'is_validated' => true,
            'validated_by' => $request->validated_by ?? 'Analyst',
            'validated_at' => now()
        ]);

        return response()->json(['success' => true, 'message' => 'Data successfully validated', 'data' => $data]);
    }

    public function destroy($id)
    {
        $data = CrawledData::find($id);
        if (!$data) return response()->json(['success' => false, 'message' => 'Data not found'], 404);
        $data->delete();
        return response()->json(['success' => true, 'message' => 'Data deleted successfully']);
    }
}

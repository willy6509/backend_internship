<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FilterController extends Controller
{
    public function index()
    {
        $filters = DB::table('crawling_filters')->orderBy('id', 'desc')->get();
        return response()->json(['success' => true, 'data' => $filters]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'keyword' => 'required|string',
            'platform' => 'required|string',
        ]);

        $id = DB::table('crawling_filters')->insertGetId([
            'keyword' => $request->keyword,
            'platform' => $request->platform,
            'is_active' => $request->is_active ?? true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

	DB::table('activity_logs')->insert([
            'user_id' => auth()->id() ?? 1,
            'action' => 'CREATE_FILTER',
            'target' => 'Keyword: ' . $request->keyword,
            'ip_address' => request()->ip(),
            'details' => json_encode(['platform' => $request->platform]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $newFilter = DB::table('crawling_filters')->where('id', $id)->first();
        return response()->json(['success' => true, 'data' => $newFilter]);
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'keyword' => 'required|string',
            'platform' => 'required|string',
        ]);

        DB::table('crawling_filters')->where('id', $id)->update([
            'keyword' => $request->keyword,
            'platform' => $request->platform,
            'updated_at' => now(),
        ]);

        $updatedFilter = DB::table('crawling_filters')->where('id', $id)->first();
        return response()->json(['success' => true, 'data' => $updatedFilter]);
    }

    public function toggleActive(Request $request, $id)
    {
        $request->validate(['is_active' => 'required|boolean']);

        DB::table('crawling_filters')->where('id', $id)->update([
            'is_active' => $request->is_active,
            'updated_at' => now(),
        ]);

        return response()->json(['success' => true, 'message' => 'Status filter diubah']);
    }

    public function destroy($id)
    {
        DB::table('crawling_filters')->where('id', $id)->delete();
        return response()->json(['success' => true, 'message' => 'Filter berhasil dihapus']);
    }
}

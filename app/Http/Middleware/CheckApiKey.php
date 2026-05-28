<?php
namespace App\Http\Middleware;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;
class CheckApiKey
{
    public function handle(Request $request, Closure $next): Response
    {
        $clientIp = $request->ip();

        // IP whitelist dinonaktifkan - validasi cukup pakai API key
        
        // Cek API Key
        $apiKey = (string) $request->header('x-api-key', '');
        $expected = config('sentinel.api_key', env('SENTINEL_API_KEY'));
        if (empty($expected)) {
            Log::error('SENTINEL API key is not configured');
            return response()->json([
                'status' => 'error',
                'message' => 'Server konfigurasi tidak lengkap.',
            ], 500);
        }
        if (! hash_equals((string) $expected, $apiKey)) {
            Log::warning('Invalid API key attempt', ['ip' => $clientIp]);
            return response()->json([
                'status' => 'error',
                'message' => 'Akses Ditolak! API Key tidak valid atau kedaluwarsa.',
            ], 401);
        }

        return $next($request);
    }
}

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
        // Ambil IP klien (Laravel menghormati trusted proxies bila dikonfigurasi)
        $clientIp = $request->ip();

        // Ambil daftar IP yang diizinkan dari konfigurasi atau env (CSV/CIDR atau array)
        $allowed = config('sentinel.allowed_ips', env('SENTINEL_ALLOWED_IPS', '127.0.0.1,::1'));
        if (! is_array($allowed)) {
            $allowed = array_filter(array_map('trim', explode(',', (string) $allowed)));
        }

        // Periksa apakah IP cocok dengan salah satu entri whitelist (exact atau CIDR)
        $ipAllowed = false;
        foreach ($allowed as $pattern) {
            if ($pattern === '') {
                continue;
            }

            if (strpos($pattern, '/') !== false) {
                if ($this->ipInRange($clientIp, $pattern)) {
                    $ipAllowed = true;
                    break;
                }
            } elseif ($pattern === $clientIp) {
                $ipAllowed = true;
                break;
            }
        }

        if (! $ipAllowed) {
            Log::warning('Access blocked by IP whitelist', ['ip' => $clientIp, 'allowed' => $allowed]);

            return response()->json([
                'status' => 'error',
                'message' => 'Akses Ditolak! IP Address Anda ('.$clientIp.') diblokir oleh sistem SENTINEL.',
            ], 403);
        }

        // --- PERTAHANAN LAPIS 1: CEK API KEY ---
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

        // Semua pemeriksaan lolos
        return $next($request);
    }

    private function ipInRange(string $ip, string $cidr): bool
    {
        [$subnet, $bits] = array_pad(explode('/', $cidr), 2, null);
        if ($subnet === null || $bits === null) {
            return false;
        }

        $ipBin = @inet_pton($ip);
        $subnetBin = @inet_pton($subnet);
        if ($ipBin === false || $subnetBin === false) {
            return false;
        }

        $bits = (int) $bits;
        $bytes = strlen($ipBin);
        if ($bits < 0 || $bits > $bytes * 8) {
            return false;
        }

        $fullBytes = intdiv($bits, 8);
        $remainder = $bits % 8;

        $mask = str_repeat("\xFF", $fullBytes);
        if ($remainder > 0) {
            $mask .= chr((~(0xFF >> $remainder)) & 0xFF);
        }
        $mask = str_pad($mask, $bytes, "\x00");

        return ($ipBin & $mask) === ($subnetBin & $mask);
    }
}

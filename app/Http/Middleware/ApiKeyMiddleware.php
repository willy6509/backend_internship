<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class ApiKeyMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $apiKey = $request->header('X-API-KEY');
        
        // Cek apakah API Key cocok dengan yang ada di .env Laravel
        if ($apiKey !== env('INTERNAL_API_KEY')) {
            return response()->json(['message' => 'Unauthorized Machine'], 401);
        }

        return $next($request);
    }
}

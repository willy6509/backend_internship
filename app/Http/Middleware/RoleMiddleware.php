<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    /**
     * Handle an incoming request.
     * @param string ...$roles
     */
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        if (!$request->user() || !in_array($request->user()->role, $roles)) {
            // Log percobaan akses ilegal ke ActivityLog (opsional tapi disarankan)
            if ($request->user()) {
                \App\Models\ActivityLog::create([
                    'user_id' => $request->user()->id,
                    'user_ip' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                    'event' => 'unauthorized_access',
                    'description' => 'Mencoba mengakses endpoint di luar hak akses role: ' . $request->user()->role,
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Akses ditolak. Anda tidak memiliki izin untuk tindakan ini.'
            ], 403);
        }

        return $next($request);
    }
}

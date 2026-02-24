<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\ActivityLog;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $request->validate([
            'nip' => 'required|string',
            'password' => 'required|string'
        ]);

        $user = User::where('nip', $request->nip)->first();

        if (!$user || !Hash::check($request->password, $user->password) || !$user->is_active) {
            return response()->json([
                'success' => false,
                'message' => 'Kredensial tidak valid atau akun dinonaktifkan.'
            ], 401);
        }

        // Update last login
        $user->update([
            'last_login_at' => now(),
            'last_login_ip' => $request->ip()
        ]);

        // Buat token Sanctum
        $token = $user->createToken('sentinel_auth_token')->plainTextToken;

        // Catat di Blockchain Log
        // ActivityLog::create([
        //     'user_id' => $user->id,
        //     'user_ip' => $request->ip(),
        //     'user_agent' => $request->userAgent(),
        //     'event' => 'login',
        //     'description' => 'User berhasil login ke sistem.',
        // ]);

        // Mengirimkan token, frontend bisa menyimpannya di HTTP-Only cookie via Next.js API Routes
        return response()->json([
            'success' => true,
            'message' => 'Login berhasil',
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'role' => $user->role,
                    'region_code' => $user->region_code
                ],
                'token' => $token
            ]
        ], 200);
    }

    public function logout(Request $request)
    {
        // Hapus token saat ini
        $request->user()->currentAccessToken()->delete();

        ActivityLog::create([
            'user_id' => $request->user()->id,
            'user_ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'event' => 'logout',
            'description' => 'User logout dari sistem.',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Logout berhasil.'
        ], 200);
    }
}

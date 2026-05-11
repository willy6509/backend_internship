<?php
namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
class AuthController extends Controller
{
    public function login(Request $request)
    {
        $request->validate([
            'nip' => 'required|string',
            'password' => 'required|string',
        ]);
        $user = User::where('nip', $request->nip)->first();
        if (! $user || ! Hash::check($request->password, $user->password) || ! $user->is_active) {
            return response()->json([
                'success' => false,
                'message' => 'Kredensial tidak valid atau akun dinonaktifkan.',
            ], 401);
        }
        $user->update([
            'last_login_at' => now(),
            'last_login_ip' => $request->ip(),
        ]);
        $token = $user->createToken('sentinel_auth_token')->plainTextToken;
        ActivityLog::create([
            'user_id' => $user->id,
            'event' => 'login',
            'subject_id' => $user->id,
            'description' => 'User berhasil login ke sistem.',
            'properties' => [
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ],
        ]);
        return response()->json([
            'success' => true,
            'message' => 'Login berhasil',
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'role' => $user->role,
                    'region_code' => $user->region_code,
                ],
                'token' => $token,
            ],
        ], 200);
    }
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        ActivityLog::create([
            'user_id' => $request->user()->id,
            'event' => 'logout',
            'subject_id' => $request->user()->id,
            'description' => 'User logout dari sistem.',
            'properties' => [
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ],
        ]);
        return response()->json([
            'success' => true,
            'message' => 'Logout berhasil.',
        ], 200);
    }
}

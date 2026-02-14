<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function login(Request $request) {
        // 1. Validasi Input
        $request->validate([
            'nip' => 'required',
            'password' => 'required'
        ]);

        // 2. Cek User
        $user = User::where('nip', $request->nip)->first();

        // 3. Cek Password & Buat Token
        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json(['message' => 'NIP atau Password salah'], 401);
        }

        // 4. Buat Token (Semacam KTP Digital untuk akses API)
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'Login berhasil',
            'access_token' => $token,
            'token_type' => 'Bearer',
            'user' => $user
        ]);
    }
}

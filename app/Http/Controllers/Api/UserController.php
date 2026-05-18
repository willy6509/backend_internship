<?php
namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public function index()
    {
        $users = User::select('id', 'name', 'username', 'nrp', 'email', 'role', 'region_code', 'is_active', 'last_login_at', 'created_at')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $users,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string',
            'username' => 'required|string|unique:users',
            'nrp' => 'required|string|unique:users',
            'email' => 'required|email|unique:users',
            'password' => 'required|string|min:6',
            'role' => 'required|in:super_admin,analyst,user,officer',
            'region_code' => 'nullable|string',
        ]);

        $user = User::create([
            'name' => $request->name,
            'username' => $request->username,
            'nrp' => $request->nrp,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => $request->role,
            'region_code' => $request->region_code,
            'is_active' => true,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'User berhasil dibuat.',
            'data' => $user,
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $request->validate([
            'role' => 'sometimes|in:super_admin,analyst,user,officer',
            'is_active' => 'sometimes|boolean',
            'region_code' => 'nullable|string',
        ]);

        $user->update($request->only(['role', 'is_active', 'region_code']));

        return response()->json([
            'success' => true,
            'message' => 'User berhasil diupdate.',
            'data' => $user,
        ]);
    }

    public function destroy($id)
    {
        $user = User::findOrFail($id);
        $user->delete();

        return response()->json([
            'success' => true,
            'message' => 'User berhasil dihapus.',
        ]);
    }
}

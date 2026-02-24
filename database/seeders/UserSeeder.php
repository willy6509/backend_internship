<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Buat Superadmin
        User::create([
            'nip' => 'SUPERADMIN001',
            'name' => 'super_admin',
            'email' => 'admin@polda.go.id',
            'password' => Hash::make('rahasia123'),
            'role' => 'superadmin',
            'is_active' => true,
        ]);

        // Buat Petugas Lapangan
        User::create([
            'nip' => 'POL001',
            'name' => 'Petugas Semarang',
            'email' => 'operator.smg@polda.go.id',
            'password' => Hash::make('rahasia123'),
            'role' => 'officer',
            'region_code' => 'SMG',
            'is_active' => true,
        ]);
    }
}

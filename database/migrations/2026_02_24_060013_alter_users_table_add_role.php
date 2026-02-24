<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->uuid('id')->primary(); // Wajib UUID
            $table->string('nip')->unique()->nullable(); // Nomor Induk Pegawai (Identitas Asli)
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            
            // RBAC & Wilayah
            $table->enum('role', ['officer', 'analyst', 'admin', 'superadmin'])->default('officer');
            $table->string('region_code')->nullable()->index(); // Kode Wilayah (misal: SL01 untuk Solo)
            
            // Keamanan Akun
            $table->boolean('is_active')->default(true); // Untuk mematikan akun tanpa hapus
            $table->timestamp('last_login_at')->nullable();
            $table->ipAddress('last_login_ip')->nullable();
            
            $table->rememberToken();
            $table->timestamps();
            $table->softDeletes(); // Audit trail: Data user tidak pernah hilang fisik
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};

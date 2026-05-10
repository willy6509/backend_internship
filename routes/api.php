<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DataController;
use App\Http\Middleware\CheckApiKey;
use Illuminate\Support\Facades\Route;

// --- Authentication ---
Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:login');

// Rute khusus untuk Crawler Python (Dilindungi Middleware API Key buatan sendiri)
Route::post('/internal/ingest', [DataController::class, 'ingestData'])
    ->middleware([CheckApiKey::class, 'throttle:120,1']);

// --- Protected Routes ---
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);

    // Dashboard Data (Bisa diakses Officer, Analyst, Admin)
    Route::middleware('role:officer,analyst,admin,superadmin')->group(function () {
        Route::get('/crawled-data', [DataController::class, 'index']);
        Route::get('/crawled-data/{id}', [DataController::class, 'show']);
    });

    // Analyst & Admin Only (Konfigurasi & Manajemen AI)
    Route::middleware('role:analyst,admin,superadmin')->group(function () {
        // Endpoint untuk trigger retrain AI atau update rules crawling (contoh)
        Route::post('/ai/sync', function () {
            return response()->json(['message' => 'Syncing with AI database...'], 202);
        });
    });

    // Admin & Superadmin Only (User Management & Audit)
    Route::middleware('role:admin,superadmin')->group(function () {
        Route::get('/audit-logs', function () {
            return response()->json(\App\Models\ActivityLog::latest()->paginate(50), 200);
        });
    });
});

<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DataController;

// --- Authentication ---
Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:login');

// Rute khusus untuk Crawler Python (Dilindungi Middleware API Key buatan sendiri)
Route::post('/internal/ingest', [\App\Http\Controllers\Api\DataController::class, 'ingestData'])
    ->middleware(\App\Http\Middleware\ApiKeyMiddleware::class);

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

<?php
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DataController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\FilterController; // JANGAN LUPA IMPORT INI
use App\Http\Middleware\CheckApiKey;
use Illuminate\Support\Facades\Route;

Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:login');

Route::post('/internal/ingest', [DataController::class, 'ingestData'])
    ->middleware([CheckApiKey::class, 'throttle:120,1']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);

    // Officer, Analyst, Admin, Super Admin
    Route::middleware('role:officer,analyst,admin,super_admin')->group(function () {
        Route::get('/crawled-data', [DataController::class, 'index']);
        Route::get('/crawled-data/{id}', [DataController::class, 'show']);
    });

    // Analyst, Admin, Super Admin
    Route::middleware('role:analyst,admin,super_admin')->group(function () {
        Route::post('/ai/sync', function () {
            return response()->json(['message' => 'Syncing with AI database...'], 202);
        });

        // RUTE VALIDASI SENTIMEN
        Route::patch('/crawled-data/{id}', [DataController::class, 'updateSentiment']);
        Route::delete('/crawled-data/{id}', [DataController::class, 'destroy']);

        // RUTE PENGATURAN FILTER CRAWLING
        Route::get('/filters', [FilterController::class, 'index']);
        Route::post('/filters', [FilterController::class, 'store']);
        Route::put('/filters/{id}', [FilterController::class, 'update']);
        Route::patch('/filters/{id}', [FilterController::class, 'toggleActive']);
        Route::delete('/filters/{id}', [FilterController::class, 'destroy']);
    });

    // Admin & Super Admin Only
    Route::middleware('role:admin,super_admin')->group(function () {
        Route::get('/audit-logs', function () {
            return response()->json(\App\Models\ActivityLog::latest()->paginate(50), 200);
        });

        // User Management
        Route::get('/users', [UserController::class, 'index']);
        Route::post('/users', [UserController::class, 'store']);
        Route::put('/users/{id}', [UserController::class, 'update']);
        Route::delete('/users/{id}', [UserController::class, 'destroy']);
    });
});

<?php

namespace App\Providers;

use App\Models\ActivityLog;
use App\Models\CrawledData;
use App\Observers\ActivityLogObserver;
use App\Observers\CrawledDataObserver;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        CrawledData::observe(CrawledDataObserver::class);
        ActivityLog::observe(ActivityLogObserver::class);

        // Batasi akses API publik
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });

        // Perlindungan khusus Brute-force Login
        RateLimiter::for('login', function (Request $request) {
            return Limit::perMinute(5)->by($request->ip()); // Maksimal 5x coba login per menit
        });
    }
}

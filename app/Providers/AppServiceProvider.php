<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Models\CrawledData;
use App\Observers\CrawledDataObserver;

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
    }
}

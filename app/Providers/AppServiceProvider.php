<?php

namespace App\Providers;

use App\Models\Blogger;
use App\Observers\BloggerObserver;
use Illuminate\Support\Facades\URL;
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
        Blogger::observe(BloggerObserver::class);

        if ($this->app->isProduction()) {
            URL::forceScheme('https');
        }
    }
}

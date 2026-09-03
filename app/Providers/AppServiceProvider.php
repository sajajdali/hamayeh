<?php

namespace App\Providers;

use App\Http\Controllers\EventSettingsController;
use App\Models\Blogger;
use App\Observers\BloggerObserver;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
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

        View::composer(['layouts.app', 'layouts::app'], function ($view): void {
            $seo = json_decode((string) DB::table('settings')->where('key', 'site_seo')->value('value'), true);
            $seo = is_array($seo) ? array_replace(EventSettingsController::defaultSeo(), $seo) : EventSettingsController::defaultSeo();
            $seo['image_url'] = ! empty($seo['image_path'])
                ? route('design.seo-image', ['image' => basename($seo['image_path'])])
                : url('/design/assets/logo-genavehei.png');

            $view->with('seoMeta', $seo);
        });
    }
}

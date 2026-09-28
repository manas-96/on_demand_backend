<?php

namespace App\Providers;

use App\Models\Banner;
use App\Models\PromotionalVideo;
use App\Models\ProviderProfile;
use App\Models\RequestPhoto;
use App\Models\ServiceCategory;
use App\Models\ServiceRequest;
use App\Models\User;
use App\Observers\RequestPhotoObserver;
use Illuminate\Support\Facades\Cache;
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
        RequestPhoto::observe(RequestPhotoObserver::class);

        View::composer('layouts.admin', function ($view) {
            $sidebarCounts = Cache::remember('admin_sidebar_counts', 30, function () {
                return [
                    'requests' => ServiceRequest::count(),
                    'customers' => User::where('role', 'customer')->count(),
                    'providers' => ProviderProfile::count(),
                    'technicians' => User::where('role', 'technician')->count(),
                    'categories' => ServiceCategory::parents()->count(),
                    'banners' => Banner::count(),
                    'videos' => PromotionalVideo::count(),
                ];
            });

            $view->with('sidebarCounts', $sidebarCounts);
        });
    }
}

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
use App\Services\BusinessTime;
use App\Services\Payment\FakePaymentGateway;
use App\Services\Payment\StripeGateway;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use RuntimeException;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(StripeGateway::class, function ($app) {
            if (config('services.payments.driver') !== 'fake') {
                return new StripeGateway;
            }

            if ($app->isProduction() && ! config('services.payments.allow_fake_in_production')) {
                throw new RuntimeException(
                    'PAYMENT_DRIVER=fake is not allowed when APP_ENV=production. Configure Stripe keys or set PAYMENT_ALLOW_FAKE_IN_PRODUCTION=true for a non-public test server.'
                );
            }

            return new FakePaymentGateway;
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        RequestPhoto::observe(RequestPhotoObserver::class);

        // Stored timestamps are UTC; web panels, emails and PDFs show them in the business zone.
        Carbon::macro('inDisplayTimezone', function (?string $timezone = null): Carbon {
            /** @var Carbon $this */
            return $this->copy()->setTimezone(BusinessTime::timezone($timezone ?? BusinessTime::displayTimezone()));
        });

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

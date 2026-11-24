<?php

use App\Http\Controllers\Admin\AdminBannerController;
use App\Http\Controllers\Admin\AdminCustomerController;
use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\AdminEmailTestController;
use App\Http\Controllers\Admin\AdminFinancialController;
use App\Http\Controllers\Admin\AdminNotificationController;
use App\Http\Controllers\Admin\AdminPlaceController;
use App\Http\Controllers\Admin\AdminProfileController;
use App\Http\Controllers\Admin\AdminPromotionalVideoController;
use App\Http\Controllers\Admin\AdminProviderController;
use App\Http\Controllers\Admin\AdminRequestController;
use App\Http\Controllers\Admin\AdminReviewController;
use App\Http\Controllers\Admin\AdminServiceController;
use App\Http\Controllers\Admin\AdminSettingController;
use App\Http\Controllers\Admin\AdminSupportController;
use App\Http\Controllers\Admin\AdminTechnicianController;
use App\Http\Controllers\Auth\ProviderRegistrationController;
use App\Http\Controllers\PrivacyPolicyController;
use App\Http\Controllers\Provider\ProviderAccountController;
use App\Http\Controllers\Provider\ProviderDashboardController;
use App\Http\Controllers\Provider\ProviderEarningController;
use App\Http\Controllers\Provider\ProviderRequestController;
use App\Http\Controllers\Provider\ProviderSupportController;
use App\Http\Controllers\Provider\ProviderTechnicianController;
use App\Http\Controllers\SupportController;
use App\Http\Controllers\Web\WebAuthController;
use App\Http\Middleware\EnsureUserIsAdmin;
use App\Http\Middleware\EnsureUserIsProvider;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// Landing Page with Login & Registration Options
Route::get('/', [WebAuthController::class, 'landing'])->name('landing');
Route::get('/login', [WebAuthController::class, 'landing'])->name('login');
Route::post('/login', [WebAuthController::class, 'login'])->name('login.post');
Route::post('/logout', [WebAuthController::class, 'logout'])->name('logout');

// Public Provider Self-Registration
Route::get('/register/provider', [ProviderRegistrationController::class, 'showRegistrationForm'])->name('register.provider');
Route::post('/register/provider', [ProviderRegistrationController::class, 'register'])->name('register.provider.post');

// Public Privacy Policy Page
Route::get('/privacy-policy', [PrivacyPolicyController::class, 'show'])->name('privacy-policy');

// Public Support & Helpdesk Page (throttled to 5 requests per minute per IP for security)
Route::get('/support', [SupportController::class, 'show'])->name('support');
Route::post('/support', [SupportController::class, 'store'])->middleware('throttle:5,1')->name('support.store');

// Public Storage & Uploads File Server Routes
Route::get('/storage/{path}', function (string $path) {
    $cleanPath = ltrim($path, '/');
    if (str_starts_with($cleanPath, 'storage/')) {
        $cleanPath = Str::after($cleanPath, 'storage/');
    } elseif (str_starts_with($cleanPath, 'public/')) {
        $cleanPath = Str::after($cleanPath, 'public/');
    }

    $fullPath = storage_path('app/public/'.$cleanPath);

    if (! file_exists($fullPath)) {
        abort(404);
    }

    return response()->file($fullPath);
})->where('path', '.*');

Route::get('/uploads/{path}', function (string $path) {
    $cleanPath = ltrim($path, '/');
    if (str_starts_with($cleanPath, 'storage/')) {
        $cleanPath = Str::after($cleanPath, 'storage/');
    } elseif (str_starts_with($cleanPath, 'public/')) {
        $cleanPath = Str::after($cleanPath, 'public/');
    }

    $fullPath = storage_path('app/public/'.$cleanPath);

    if (! file_exists($fullPath)) {
        abort(404);
    }

    return response()->file($fullPath);
})->where('path', '.*');

// Protected Admin Web Routes
Route::middleware(['auth', EnsureUserIsAdmin::class])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', function () {
        return redirect()->route('admin.dashboard');
    });

    Route::get('/dashboard', [AdminDashboardController::class, 'dashboard'])->name('dashboard');

    // Place Autocomplete & Details Proxy
    Route::get('/places/autocomplete', [AdminPlaceController::class, 'autocomplete'])->name('places.autocomplete');
    Route::get('/places/details', [AdminPlaceController::class, 'details'])->name('places.details');

    // Customers
    Route::get('/customers', [AdminCustomerController::class, 'index'])->name('customers.index');
    Route::get('/customers/create', [AdminCustomerController::class, 'create'])->name('customers.create');
    Route::post('/customers', [AdminCustomerController::class, 'store'])->name('customers.store');
    Route::get('/customers/{id}', [AdminCustomerController::class, 'show'])->name('customers.show');
    Route::post('/customers/{id}/toggle-status', [AdminCustomerController::class, 'toggleStatus'])->name('customers.toggle-status');

    // Providers
    Route::get('/providers', [AdminProviderController::class, 'index'])->name('providers.index');
    Route::get('/providers/create', [AdminProviderController::class, 'create'])->name('providers.create');
    Route::post('/providers', [AdminProviderController::class, 'store'])->name('providers.store');
    Route::get('/providers/{id}', [AdminProviderController::class, 'show'])->name('providers.show');
    Route::get('/providers/{id}/edit', [AdminProviderController::class, 'edit'])->name('providers.edit');
    Route::put('/providers/{id}', [AdminProviderController::class, 'update'])->name('providers.update');
    Route::post('/providers/{id}/toggle-approval', [AdminProviderController::class, 'toggleApproval'])->name('providers.toggle-approval');
    Route::post('/providers/{id}/toggle-job-acceptance', [AdminProviderController::class, 'toggleJobAcceptance'])->name('providers.toggle-job-acceptance');

    // Technicians
    Route::get('/technicians', [AdminTechnicianController::class, 'index'])->name('technicians.index');

    // Service Categories
    Route::get('/services', [AdminServiceController::class, 'index'])->name('services.index');
    Route::get('/services/create', [AdminServiceController::class, 'create'])->name('services.create');
    Route::post('/services', [AdminServiceController::class, 'store'])->name('services.store');
    Route::get('/services/{id}', [AdminServiceController::class, 'show'])->name('services.show');
    Route::get('/services/{id}/edit', [AdminServiceController::class, 'edit'])->name('services.edit');
    Route::put('/services/{id}', [AdminServiceController::class, 'update'])->name('services.update');
    Route::post('/services/{id}/toggle-status', [AdminServiceController::class, 'toggleStatus'])->name('services.toggle-status');

    // Banners Management
    Route::get('/banners', [AdminBannerController::class, 'index'])->name('banners.index');
    Route::get('/banners/create', [AdminBannerController::class, 'create'])->name('banners.create');
    Route::post('/banners', [AdminBannerController::class, 'store'])->name('banners.store');
    Route::get('/banners/{id}/edit', [AdminBannerController::class, 'edit'])->name('banners.edit');
    Route::put('/banners/{id}', [AdminBannerController::class, 'update'])->name('banners.update');
    Route::delete('/banners/{id}', [AdminBannerController::class, 'destroy'])->name('banners.destroy');
    Route::post('/banners/{id}/toggle-status', [AdminBannerController::class, 'toggleStatus'])->name('banners.toggle-status');

    // Promotional Videos Management
    Route::get('/videos', [AdminPromotionalVideoController::class, 'index'])->name('videos.index');
    Route::get('/videos/create', [AdminPromotionalVideoController::class, 'create'])->name('videos.create');
    Route::post('/videos', [AdminPromotionalVideoController::class, 'store'])->name('videos.store');
    Route::get('/videos/{id}/edit', [AdminPromotionalVideoController::class, 'edit'])->name('videos.edit');
    Route::put('/videos/{id}', [AdminPromotionalVideoController::class, 'update'])->name('videos.update');
    Route::delete('/videos/{id}', [AdminPromotionalVideoController::class, 'destroy'])->name('videos.destroy');
    Route::post('/videos/{id}/toggle-status', [AdminPromotionalVideoController::class, 'toggleStatus'])->name('videos.toggle-status');

    // Request Lifecycle Inspector
    Route::get('/requests', [AdminRequestController::class, 'index'])->name('requests.index');
    Route::get('/requests/{id}', [AdminRequestController::class, 'show'])->name('requests.show');
    Route::get('/requests/{id}/invoice/download', [AdminRequestController::class, 'downloadInvoice'])->name('requests.invoice.download');
    Route::post('/requests/{id}/assign-provider', [AdminRequestController::class, 'assignProvider'])->name('requests.assign-provider');
    Route::post('/requests/{id}/override-status', [AdminRequestController::class, 'overrideStatus'])->name('requests.override-status');

    // Financials
    Route::get('/invoices', [AdminFinancialController::class, 'invoices'])->name('financials.invoices');
    Route::get('/earnings', [AdminFinancialController::class, 'earnings'])->name('financials.earnings');

    // Reviews & Settings
    Route::get('/reviews', [AdminReviewController::class, 'index'])->name('reviews.index');
    Route::get('/notifications', [AdminNotificationController::class, 'index'])->name('notifications.index');
    Route::post('/notifications', [AdminNotificationController::class, 'sendPromotionalWeb'])->name('notifications.send');
    Route::get('/email-test', [AdminEmailTestController::class, 'index'])->name('email-test.index');
    Route::post('/email-test/send', [AdminEmailTestController::class, 'sendTestEmail'])->name('email-test.send');
    Route::get('/settings', [AdminSettingController::class, 'index'])->name('settings.index');
    Route::post('/settings', [AdminSettingController::class, 'update'])->name('settings.update');
    Route::get('/privacy-policy', [AdminSettingController::class, 'editPrivacyPolicy'])->name('privacy-policy.edit');
    Route::post('/privacy-policy', [AdminSettingController::class, 'updatePrivacyPolicy'])->name('privacy-policy.update');

    // Customer Support Tickets
    Route::get('/support', [AdminSupportController::class, 'index'])->name('support.index');
    Route::get('/support/{id}', [AdminSupportController::class, 'show'])->name('support.show');
    Route::get('/support/chat/{id}', [AdminSupportController::class, 'show'])->name('support.chat');
    Route::post('/support/{id}/update-status', [AdminSupportController::class, 'updateStatus'])->name('support.update-status');

    // Profile & Password
    Route::get('/profile', [AdminProfileController::class, 'show'])->name('profile.show');
    Route::put('/profile', [AdminProfileController::class, 'update'])->name('profile.update');
    Route::put('/profile/password', [AdminProfileController::class, 'updatePassword'])->name('profile.password');
});

// Protected Provider Web Routes
Route::middleware(['auth', EnsureUserIsProvider::class])->prefix('provider')->name('provider.')->group(function () {
    Route::get('/', function () {
        return redirect()->route('provider.dashboard');
    });

    Route::get('/dashboard', [ProviderDashboardController::class, 'dashboard'])->name('dashboard');

    // Technicians
    Route::get('/technicians', [ProviderTechnicianController::class, 'index'])->name('technicians.index');
    Route::get('/technicians/create', [ProviderTechnicianController::class, 'create'])->name('technicians.create');
    Route::post('/technicians', [ProviderTechnicianController::class, 'store'])->name('technicians.store');
    Route::post('/technicians/{id}/toggle-status', [ProviderTechnicianController::class, 'toggleStatus'])->name('technicians.toggle-status');

    // Job Requests
    Route::get('/requests/incoming', [ProviderRequestController::class, 'incoming'])->name('requests.incoming');
    Route::get('/requests/accepted', [ProviderRequestController::class, 'accepted'])->name('requests.accepted');
    Route::get('/requests', [ProviderRequestController::class, 'index'])->name('requests.index');
    Route::get('/requests/{id}', [ProviderRequestController::class, 'show'])->name('requests.show');
    Route::get('/requests/{id}/technician-location', [ProviderRequestController::class, 'technicianLocation'])->name('requests.technician-location');
    Route::get('/requests/{id}/invoice/download', [ProviderRequestController::class, 'downloadInvoice'])->name('requests.invoice.download');
    Route::post('/requests/{id}/accept', [ProviderRequestController::class, 'acceptRequest'])->name('requests.accept');
    Route::post('/requests/{id}/bid', [ProviderRequestController::class, 'submitBid'])->name('requests.bid');
    Route::post('/requests/{id}/assign-technician', [ProviderRequestController::class, 'assignTechnician'])->name('requests.assign-technician');
    Route::post('/requests/{id}/chat-notify', [ProviderRequestController::class, 'notifyChat'])->name('requests.chat-notify');
    Route::post('/requests/{id}/chat-send', [ProviderRequestController::class, 'sendChat'])->name('requests.chat-send');
    Route::post('/quotes/{quoteId}/approve', [ProviderRequestController::class, 'approveQuote'])->name('quotes.approve');
    Route::put('/quotes/{quoteId}', [ProviderRequestController::class, 'updateQuote'])->name('quotes.update');

    // Earnings
    Route::get('/earnings', [ProviderEarningController::class, 'index'])->name('earnings.index');

    // Profile & Password
    Route::get('/profile', [ProviderAccountController::class, 'show'])->name('profile.show');
    Route::put('/profile', [ProviderAccountController::class, 'update'])->name('profile.update');
    Route::put('/profile/password', [ProviderAccountController::class, 'updatePassword'])->name('profile.password');

    // Support & Helpdesk
    Route::get('/support', [ProviderSupportController::class, 'index'])->name('support.index');
    Route::get('/support/{id}', [ProviderSupportController::class, 'show'])->name('support.show');
});

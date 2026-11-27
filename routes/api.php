<?php

use App\Http\Controllers\Admin\AdminNotificationController;
use App\Http\Controllers\Api\V1\Auth\AuthController;
use App\Http\Controllers\Api\V1\ChatNotificationController;
use App\Http\Controllers\Api\V1\Customer\CustomerAddressController;
use App\Http\Controllers\Api\V1\Customer\CustomerCategoryController;
use App\Http\Controllers\Api\V1\Customer\CustomerProviderController;
use App\Http\Controllers\Api\V1\Customer\CustomerRequestController;
use App\Http\Controllers\Api\V1\DeviceTokenController;
use App\Http\Controllers\Api\V1\Provider\ProviderBidController;
use App\Http\Controllers\Api\V1\StripeWebhookController;
use App\Http\Controllers\Api\V1\Technician\TechnicianJobController;
use App\Http\Controllers\Api\V1\UserNotificationController;
use App\Http\Controllers\PrivacyPolicyController;
use App\Http\Controllers\Provider\ProviderRequestController;
use App\Http\Controllers\SupportController;
use App\Models\SystemSetting;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes - V1
|--------------------------------------------------------------------------
*/

Route::prefix('v1')->group(function () {

    // Public Auth & Settings
    Route::post('/auth/register', [AuthController::class, 'register']);
    Route::post('/auth/verify-email-otp', [AuthController::class, 'verifyEmailOtp']);
    Route::post('/auth/resend-email-otp', [AuthController::class, 'resendEmailOtp'])->middleware('throttle:3,1');
    Route::post('/auth/login', [AuthController::class, 'login']);
    Route::post('/auth/forgot-password', [AuthController::class, 'forgotPassword'])->middleware('throttle:5,1');
    Route::post('/auth/reset-password', [AuthController::class, 'resetPassword'])->middleware('throttle:5,1');
    Route::post('/stripe/webhook', StripeWebhookController::class);
    Route::get('/privacy-policy', [PrivacyPolicyController::class, 'apiShow']);
    Route::post('/support', [SupportController::class, 'apiStore'])->middleware('throttle:5,1');

    Route::get('/settings', function () {
        $settings = SystemSetting::getSettings();

        return response()->json([
            'success' => true,
            'data' => [
                'tax_rate' => (float) $settings->tax_rate,
                'tax_rate_decimal' => round((float) $settings->tax_rate / 100, 4),
                'platform_fee' => (float) $settings->platform_fee,
                'min_booking_fee' => (float) $settings->min_booking_fee,
            ],
        ]);
    });

    // Public customer catalog: guests can browse before signing in (read-only, no user data).
    Route::prefix('customer')->middleware('throttle:300,1')->group(function () {
        Route::get('/categories', [CustomerCategoryController::class, 'index']);
        Route::get('/categories/{id}', [CustomerCategoryController::class, 'show']);
        Route::get('/categories/{id}/subcategories', [CustomerCategoryController::class, 'subcategories']);
        Route::get('/banners', [CustomerCategoryController::class, 'banners']);
        Route::get('/videos', [CustomerCategoryController::class, 'videos']);
        Route::get('/providers', [CustomerProviderController::class, 'index']);
    });

    // Protected API Endpoints
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/auth/me', [AuthController::class, 'me']);
        Route::post('/auth/logout', [AuthController::class, 'logout']);

        // Device Token Registration
        Route::post('/device-token', [DeviceTokenController::class, 'store']);
        Route::delete('/device-token', [DeviceTokenController::class, 'destroy']);

        // In-App Notification History
        Route::get('/notifications', [UserNotificationController::class, 'index']);
        Route::post('/notifications/{id}/read', [UserNotificationController::class, 'read']);
        Route::post('/notifications/read-all', [UserNotificationController::class, 'readAll']);

        // Chat / Firestore Push Notification Dispatcher
        Route::post('/chat/notify', [ChatNotificationController::class, 'notify']);

        // Admin Promotional Push Notification API
        Route::post('/admin/notifications/promotional', [AdminNotificationController::class, 'sendPromotionalApi']);

        // Customer APIs
        Route::prefix('customer')->group(function () {
            // Customer Addresses
            Route::get('/addresses', [CustomerAddressController::class, 'index']);
            Route::post('/addresses', [CustomerAddressController::class, 'store']);
            Route::put('/addresses/{id}', [CustomerAddressController::class, 'update']);
            Route::delete('/addresses/{id}', [CustomerAddressController::class, 'destroy']);
            Route::post('/addresses/{id}/default', [CustomerAddressController::class, 'setDefault']);

            // Customer Requests
            Route::get('/requests', [CustomerRequestController::class, 'index']);
            Route::post('/requests', [CustomerRequestController::class, 'store']);
            Route::get('/requests/{id}', [CustomerRequestController::class, 'show']);
            Route::post('/requests/{id}/cancel', [CustomerRequestController::class, 'cancel']);
            Route::get('/requests/{id}/bids', [CustomerRequestController::class, 'bids']);
            Route::get('/requests/{id}/quote', [CustomerRequestController::class, 'getQuote']);
            Route::get('/requests/{id}/invoice/download', [CustomerRequestController::class, 'downloadInvoice']);
            Route::post('/bids/{bidId}/negotiate', [CustomerRequestController::class, 'negotiate']);
            Route::post('/bids/{bidId}/select', [CustomerRequestController::class, 'selectBid']);
            Route::post('/quotes/{quoteId}/approve', [CustomerRequestController::class, 'approveQuote']);
            Route::get('/requests/{id}/payment-summary', [CustomerRequestController::class, 'paymentSummary']);
            Route::post('/requests/{id}/payment-intent', [CustomerRequestController::class, 'paymentIntent']);
            Route::post('/requests/{id}/payment-confirm', [CustomerRequestController::class, 'confirmPayment']);
            Route::get('/requests/{id}/review', [CustomerRequestController::class, 'getReview']);
            Route::post('/requests/{id}/review', [CustomerRequestController::class, 'storeReview']);
        });

        // Provider APIs
        Route::prefix('provider')->group(function () {
            Route::get('/requests', [ProviderBidController::class, 'incomingRequests']);
            Route::post('/requests/{requestId}/bid', [ProviderBidController::class, 'submitBid']);
            Route::post('/requests/{requestId}/assign-technician', [ProviderBidController::class, 'assignTechnician']);
            Route::post('/quotes/{quoteId}/approve', [ProviderBidController::class, 'approveQuote']);
            Route::put('/quotes/{quoteId}', [ProviderBidController::class, 'updateQuote']);
            Route::get('/requests/{requestId}/invoice/download', [ProviderRequestController::class, 'downloadInvoice']);
            Route::get('/earnings', [ProviderBidController::class, 'earnings']);
        });

        // Technician APIs
        Route::prefix('technician')->group(function () {
            Route::get('/duty-status', [TechnicianJobController::class, 'getDutyStatus']);
            Route::post('/duty-status', [TechnicianJobController::class, 'toggleDutyStatus']);
            Route::post('/location', [TechnicianJobController::class, 'updateLocation']);
            Route::get('/jobs', [TechnicianJobController::class, 'myJobs']);
            Route::get('/jobs/{assignmentId}', [TechnicianJobController::class, 'show']);
            Route::get('/jobs/{assignmentId}/quote-status', [TechnicianJobController::class, 'checkQuoteStatus']);
            Route::post('/jobs/{assignmentId}/accept', [TechnicianJobController::class, 'acceptJob']);
            Route::post('/jobs/{assignmentId}/arrive', [TechnicianJobController::class, 'markArrived']);
            Route::post('/jobs/{assignmentId}/inspection', [TechnicianJobController::class, 'submitInspection']);
            Route::post('/jobs/{assignmentId}/quote', [TechnicianJobController::class, 'submitQuote']);
            Route::post('/jobs/{assignmentId}/progress', [TechnicianJobController::class, 'updateProgress']);
            Route::get('/jobs/{assignmentId}/invoice', [TechnicianJobController::class, 'getInvoice']);
            Route::get('/jobs/{assignmentId}/invoice/download', [TechnicianJobController::class, 'downloadInvoice']);
            Route::post('/jobs/{assignmentId}/complete', [TechnicianJobController::class, 'completeJob']);
            Route::get('/ratings', [TechnicianJobController::class, 'getRatingSummary']);
        });

    });

});

<?php

use App\Http\Controllers\Api\V1\Auth\AuthController;
use App\Http\Controllers\Api\V1\Customer\CustomerAddressController;
use App\Http\Controllers\Api\V1\Customer\CustomerCategoryController;
use App\Http\Controllers\Api\V1\Customer\CustomerProviderController;
use App\Http\Controllers\Api\V1\Customer\CustomerRequestController;
use App\Http\Controllers\Api\V1\Provider\ProviderBidController;
use App\Http\Controllers\Api\V1\Technician\TechnicianJobController;
use App\Http\Controllers\Provider\ProviderRequestController;
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
    Route::post('/auth/login', [AuthController::class, 'login']);

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

    // Protected API Endpoints
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/auth/me', [AuthController::class, 'me']);
        Route::post('/auth/logout', [AuthController::class, 'logout']);

        // Customer APIs
        Route::prefix('customer')->group(function () {
            // Customer Categories & Providers
            Route::get('/categories', [CustomerCategoryController::class, 'index']);
            Route::get('/categories/{id}', [CustomerCategoryController::class, 'show']);
            Route::get('/categories/{id}/subcategories', [CustomerCategoryController::class, 'subcategories']);
            Route::get('/banners', [CustomerCategoryController::class, 'banners']);
            Route::get('/videos', [CustomerCategoryController::class, 'videos']);
            Route::get('/providers', [CustomerProviderController::class, 'index']);
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
            Route::get('/requests/{id}/bids', [CustomerRequestController::class, 'bids']);
            Route::get('/requests/{id}/quote', [CustomerRequestController::class, 'getQuote']);
            Route::get('/requests/{id}/invoice/download', [CustomerRequestController::class, 'downloadInvoice']);
            Route::post('/bids/{bidId}/negotiate', [CustomerRequestController::class, 'negotiate']);
            Route::post('/bids/{bidId}/select', [CustomerRequestController::class, 'selectBid']);
            Route::post('/quotes/{quoteId}/approve', [CustomerRequestController::class, 'approveQuote']);
            Route::post('/requests/{id}/pay', [CustomerRequestController::class, 'payRequest']);
            Route::post('/invoices/{invoiceId}/pay', [CustomerRequestController::class, 'pay']);
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
            Route::post('/jobs/{assignmentId}/settle', [TechnicianJobController::class, 'settleInvoice']);
            Route::post('/jobs/{assignmentId}/complete', [TechnicianJobController::class, 'completeJob']);
        });

    });

});

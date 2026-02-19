<?php

use App\Http\Controllers\Admin\AdminOrderController;
use App\Http\Controllers\Admin\AdminOrderRefundController;
use App\Http\Controllers\Admin\DeliveryFeeSettingController;
use App\Http\Controllers\Auth\EmailVerificationController;
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\LogoutController;
use App\Http\Controllers\Auth\PasswordUpdateController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Customer\ContactMessageController;
use App\Http\Controllers\Customer\CourierRatingController;
use App\Http\Controllers\Customer\CustomerCourierController;
use App\Http\Controllers\Customer\OrdersController;
use App\Http\Controllers\Customer\PaymentController;
use App\Http\Controllers\Order\OrderPriceController;
use App\Http\Controllers\Profile\UserProfileController;
use App\Http\Controllers\Stripe\StripeWebhookController;
use Illuminate\Support\Facades\Route;
use Symfony\Component\HttpFoundation\Request;

// Webhooks
Route::post('/stripe/webhook', [StripeWebhookController::class, 'handleWebhook']);

// Public Order Price Estimation
Route::post('orders/calculate-price', [OrderPriceController::class, 'estimate']);

// Auth Routes
Route::prefix('auth')->group(function () {
    Route::post('/register', [RegisterController::class, 'store']);
    Route::post('/login', [LoginController::class, 'store']);
    Route::post('/logout', [LogoutController::class, 'destroy'])->middleware('auth:sanctum');

    // Email verification
    Route::post('/email/verify', [EmailVerificationController::class, 'verify']);
    Route::post('/email/resend', [EmailVerificationController::class, 'resend']);

    // Forgot Password (Public)
    Route::post('/forgot-password', [ForgotPasswordController::class, 'store']);
    Route::post('/forgot-password/verify', [ForgotPasswordController::class, 'verify']);
    Route::post('/forgot-password/reset', [ForgotPasswordController::class, 'reset']);
});

// Profile Group
Route::middleware('auth:sanctum')->group(function () {
    Route::put('/profile/password', [PasswordUpdateController::class, 'update']);
});

// Customer Routes
Route::prefix('customer')->middleware(['auth:sanctum','role:customer'])->group(function () {

    // Profile
    Route::get('profile', [UserProfileController::class, 'show'])->name('customer.profile.show');
    Route::patch('profile', [UserProfileController::class, 'update'])->name('customer.profile.update');

    // Become Courier
    Route::post('/become-courier',[CustomerCourierController::class, 'store'])->name('customer.become-courier'); // Customer will become courier
    
    // Orders
    Route::apiResource('orders', OrdersController::class)->only(['index','store','show','update']);

    // Payment
    Route::post('/orders/{order}/pay',[PaymentController::class,'store'])->name('customer.order.pay');

    // Contact Message
    Route::apiResource('/contact-message',ContactMessageController::class)->only('store')->middleware('throttle:5,1');

    // Rate Courier
    Route::apiResource('/courier-ratings', CourierRatingController::class)->only('store');
});

// Admin Routes
Route::prefix('admin')->middleware(['auth:sanctum', 'role:admin'])->group(function () {
    // Get and Update Pricing Settings
    Route::get('/delivery-pricing-settings', [DeliveryFeeSettingController::class, 'index'])->name('admin.delivery-pricing-settings.index');
    Route::patch('/delivery-pricing-settings/distance', [DeliveryFeeSettingController::class, 'updateDistanceFee'])->name('admin.delivery-pricing-settings.update-distance');
    Route::patch('/delivery-pricing-settings/item-type', [DeliveryFeeSettingController::class, 'updateItemTypeFee'])->name('admin.delivery-pricing-settings.update-item-type');

    // Order Management
    Route::apiResource('orders', AdminOrderController::class)->only(['index']);
    Route::patch('/orders/{order}/refund',[AdminOrderRefundController::class, 'update'])->name('admin.orders.refunds.update');
    
});


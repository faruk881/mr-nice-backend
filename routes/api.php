<?php

use App\Http\Controllers\Admin\AdminFaqController;
use App\Http\Controllers\Admin\AdminOrderController;
use App\Http\Controllers\Admin\AdminOrderRefundController;
use App\Http\Controllers\Admin\AdminPrivacyController;
use App\Http\Controllers\Admin\AdminTermsController;
use App\Http\Controllers\Admin\DeliveryFeeSettingController;
use App\Http\Controllers\Auth\EmailVerificationController;
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\LogoutController;
use App\Http\Controllers\Auth\PasswordUpdateController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Courier\CourierDeliveryController;
use App\Http\Controllers\Courier\CourierOrderController;
use App\Http\Controllers\CourierVerificationController;
use App\Http\Controllers\Customer\ContactMessageController;
use App\Http\Controllers\Customer\CourierRatingController;
use App\Http\Controllers\Customer\CustomerCourierController;
use App\Http\Controllers\Customer\CustomerPaymentMethodsController;
use App\Http\Controllers\Customer\OrdersController;
use App\Http\Controllers\Customer\PaymentController;
use App\Http\Controllers\Order\OrderPriceController;
use App\Http\Controllers\Profile\UserProfileController;
use App\Http\Controllers\Stripe\StripeWebhookController;
use Illuminate\Support\Facades\Route;
use Symfony\Component\HttpFoundation\Request;

// Stripe Webhooks
Route::post('/stripe/webhook', [StripeWebhookController::class, 'handleWebhook']);

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
Route::prefix('customer')->middleware(['auth:sanctum','role:customer'],'status')->group(function () {

    // Order Price Estimation
    Route::post('orders/calculate-price', [OrderPriceController::class, 'estimate']);

    // Profile
    Route::get('profile', [UserProfileController::class, 'show'])->name('customer.profile.show');
    Route::patch('profile', [UserProfileController::class, 'update'])->name('customer.profile.update');

    // Payment Methods
    Route::get('payment-methods', [CustomerPaymentMethodsController::class, 'index'])->name('customer.payment-methods.index');
    Route::post('payment-methods', [CustomerPaymentMethodsController::class, 'store'])->name('customer.payment-methods.store');


    // Become Courier
    Route::post('/become-courier',[CustomerCourierController::class, 'store'])->name('customer.become-courier'); // Customer will become courier
    
    // Orders
    Route::post('/orders', [OrdersController::class, 'store'])->middleware('throttle:5,1');
    Route::apiResource('/orders', OrdersController::class)->only(['index','show','update']);

    // Payment
    Route::post('/orders/{order}/pay',[PaymentController::class,'store'])->name('customer.order.pay');

    // Contact Message
    Route::apiResource('/contact-message',ContactMessageController::class)->only('store')->middleware('throttle:5,1');

    // Rate Courier
    Route::apiResource('/courier-ratings', CourierRatingController::class)->only('store');

    // Get FAQs, Terms, Privacy Policy
    Route::get('/faqs', [AdminFaqController::class, 'index'])->name('customer.faqs.index');
    Route::get('/terms', [AdminTermsController::class, 'show'])->name('customer.terms.show');
    Route::get('/privacy-policy', [AdminPrivacyController::class, 'show'])->name('customer.privacy.show');
});

// Courier Routes
Route::prefix('courier')->middleware(['auth:sanctum','role:courier','status'])->group(function () {

    // Orders Routes
    Route::get('orders', [CourierOrderController::class, 'index'])->name('courier.orders.index');

    // check if the documents verified.
    Route::middleware('courier.status:verified')->group(function () {

        // Profile
        Route::get('profile', [UserProfileController::class, 'show'])->name('courier.profile.show');
        Route::patch('profile', [UserProfileController::class, 'update'])->name('courier.profile.update');

        // Orders
        Route::get('/orders/{order}', [CourierOrderController::class, 'show'])->name('courier.orders.show');
        Route::patch('/orders/{order}/accept', [CourierOrderController::class, 'accept'])->name('courier.orders.accept');
        Route::get('/deliveries',[CourierDeliveryController::class, 'index'])->name('courier.deliveries.index');
        Route::get('/deliveries/{id}',[CourierDeliveryController::class, 'show'])->name('courier.deliveries.show');
        Route::patch('/deliveries/{id}/pickup', [CourierDeliveryController::class, 'pickup'])->name('courier.orders.pickup');
        Route::patch('/deliveries/{id}/deliver', [CourierDeliveryController::class, 'deliver'])->name('courier.orders.deliver');

    });
});

// Admin Routes
Route::prefix('admin')->middleware(['auth:sanctum', 'role:admin'])->group(function () {
    // Get and Update Pricing Settings
    Route::get('/delivery-pricing-settings', [DeliveryFeeSettingController::class, 'index'])->name('admin.delivery-pricing-settings.index');
    Route::patch('/delivery-pricing-settings/distance', [DeliveryFeeSettingController::class, 'updateDistanceFee'])->name('admin.delivery-pricing-settings.update-distance');
    Route::patch('/delivery-pricing-settings/item-type', [DeliveryFeeSettingController::class, 'updateItemTypeFee'])->name('admin.delivery-pricing-settings.update-item-type');

    // Courier id verification
    Route::patch('/couriers/{courier}/verification', [CourierVerificationController::class, 'update'])->name('admin.couriers.verification.update');
    
    // Order Management
    Route::apiResource('orders', AdminOrderController::class)->only(['index']);
    Route::patch('/orders/{order}/refund',[AdminOrderRefundController::class, 'update'])->name('admin.orders.refunds.update');

    // Terms
    Route::get('terms', [AdminTermsController::class, 'show'])->name('admin.terms.show');
    Route::patch('terms', [AdminTermsController::class, 'update'])->name('admin.terms.update');

    // Privacy Policy
    Route::get('privacy-policy', [AdminPrivacyController::class, 'show'])->name('admin.privacy.show');
    Route::patch('privacy-policy', [AdminPrivacyController::class, 'update'])->name('admin.privacy.update');

    // FAQs
    Route::apiResource('faqs', AdminFaqController::class);
    
});


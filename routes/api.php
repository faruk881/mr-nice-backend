<?php

use App\Http\Controllers\Admin\DeliveryFeeSettingController;
use App\Http\Controllers\Auth\EmailVerificationController;
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\LogoutController;
use App\Http\Controllers\Auth\PasswordUpdateController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Customer\CustomerCourierController;
use App\Http\Controllers\Customer\OrdersController;
use App\Http\Controllers\Customer\PaymentController;
use App\Http\Controllers\Order\OrderPriceController;
use App\Http\Controllers\Stripe\StripeWebhookController;
use Illuminate\Support\Facades\Route;
use Symfony\Component\HttpFoundation\Request;

// Webhooks
Route::post('/stripe/webhook', [StripeWebhookController::class, 'handleWebhook']);

// Auth Group
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

Route::prefix('orders')->group(function(){
    Route::post('/calculate-price', [OrderPriceController::class, 'estimate']);
});

// Profile Group
Route::middleware('auth:sanctum')->group(function () {
    Route::put('/profile/password', [PasswordUpdateController::class, 'update']);
});

// Customer Routes
Route::prefix('customer')->middleware(['auth:sanctum','role:customer'])->group(function () {
    Route::post('/become-courier',[CustomerCourierController::class, 'store'])->name('customer.become-courier'); // Customer will become courier
    
    // Orders
    Route::post('/orders', [OrdersController::class, 'store'])->name('customer.orders.store');
    Route::get('/orders', [OrdersController::class, 'index'])->name('customer.orders.index');
    Route::get('/orders/{id}', [OrdersController::class, 'show'])->name('customer.orders.show');
    Route::patch('/orders/{id}', [OrdersController::class, 'update'])->name('customer.orders.update');

    Route::post('/orders/{order}/pay',[PaymentController::class,'store'])->name('customer.order.pay');
});

// Admin Routes
Route::prefix('admin')->middleware(['auth:sanctum', 'role:admin'])->group(function () {
    // Get and Update Pricing Settings
    Route::get('/delivery-pricing-settings', [DeliveryFeeSettingController::class, 'index'])->name('admin.delivery-pricing-settings.index');
    Route::patch('/delivery-pricing-settings/distance', [DeliveryFeeSettingController::class, 'updateDistanceFee'])->name('admin.delivery-pricing-settings.update-distance');
    Route::patch('/delivery-pricing-settings/item-type', [DeliveryFeeSettingController::class, 'updateItemTypeFee'])->name('admin.delivery-pricing-settings.update-item-type');
});








// Only Test Purpose Route
Route::get('/payment-success/{orderId}', function($orderId) {
    // Find payment and order
    $order = \App\Models\Order::findOrFail($orderId);
    return response()->json([
        'message' => "Payment successful for order #{$order->id}",
        'order' => $order
    ]);
})->name('payment.success');

Route::get('/payment-cancel/{orderId}', function($orderId) {
    $order = \App\Models\Order::findOrFail($orderId);
    return response()->json([
        'message' => "Payment canceled for order #{$order->id}",
        'order' => $order
    ]);
})->name('payment.cancel');


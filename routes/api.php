<?php

use App\Http\Controllers\Admin\DeliveryFeeSettingController;
use App\Http\Controllers\Auth\EmailVerificationController;
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\LogoutController;
use App\Http\Controllers\Auth\PasswordUpdateController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Customer\CustomerCourierController;
use App\Http\Controllers\Customer\DeliveryRequestController;
use App\Http\Controllers\Customer\PaymentController;
use App\Http\Controllers\Order\OrderPriceController;
use Illuminate\Support\Facades\Route;
use Symfony\Component\HttpFoundation\Request;

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
    Route::post('/delivery-request',[DeliveryRequestController::class,'store'])->name('customer.delivery-request.create');
    Route::post('/orders/{order}/pay',[PaymentController::class,'store'])->name('customer.order.pay');
});

// Admin Routes
Route::prefix('admin')->middleware(['auth:sanctum', 'role:admin'])->group(function () {
    // Get and Update Pricing Settings
    Route::get('/delivery-pricing-settings', [DeliveryFeeSettingController::class, 'index'])->name('admin.delivery-pricing-settings.index');
    Route::patch('/delivery-pricing-settings/distance', [DeliveryFeeSettingController::class, 'updateDistanceFee'])->name('admin.delivery-pricing-settings.update-distance');
    Route::patch('/delivery-pricing-settings/item-type', [DeliveryFeeSettingController::class, 'updateItemTypeFee'])->name('admin.delivery-pricing-settings.update-item-type');
});


<?php

use App\Http\Controllers\Admin\AdminActivityStatsController;
use App\Http\Controllers\Admin\AdminUsersController;
use App\Http\Controllers\Admin\AdminCourierPayoutsController;
use App\Http\Controllers\Admin\AdminCustomerController;
use App\Http\Controllers\Admin\AdminDashboardStatsController;
use App\Http\Controllers\Admin\AdminFaqController;
use App\Http\Controllers\Admin\AdminOrderController;
use App\Http\Controllers\Admin\AdminOrderRefundController;
use App\Http\Controllers\Admin\AdminPrivacyController;
use App\Http\Controllers\Admin\AdminTermsController;
use App\Http\Controllers\Admin\DeliveryFeeSettingController;
use App\Http\Controllers\Admin\PlatformCommissionSettingController;
use App\Http\Controllers\Admin\DeliveryApprovalController;
use App\Http\Controllers\Admin\PayoutThrseholdsController;
use App\Http\Controllers\Admin\RefundPolicySettingsController;
use App\Http\Controllers\Auth\EmailVerificationController;
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\LogoutController;
use App\Http\Controllers\Auth\PasswordUpdateController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Courier\CourierDeliveryController;
use App\Http\Controllers\Courier\CourierOrderController;
use App\Http\Controllers\Courier\CourierEarningsController;
use App\Http\Controllers\Courier\CourierNotificationsController;
use App\Http\Controllers\Courier\CourierPaymentMethodsController;
use App\Http\Controllers\Courier\CourierPayoutsController;
use App\Http\Controllers\Courier\CourierStripeController;
use App\Http\Controllers\Customer\ContactMessageController;
use App\Http\Controllers\Customer\CourierRatingController;
use App\Http\Controllers\Customer\CustomerCourierController;
use App\Http\Controllers\Customer\CustomerNotificationsController;
use App\Http\Controllers\Customer\CustomerPaymentMethodsController;
use App\Http\Controllers\Customer\OrdersController;
use App\Http\Controllers\Customer\PaymentController;
use App\Http\Controllers\Order\OrderPriceController;
use App\Http\Controllers\Profile\NotificationsController;
use App\Http\Controllers\Profile\UserProfileController;
use App\Http\Controllers\Stripe\StripeConnectWebhookController;
use App\Http\Controllers\Stripe\StripeWebhookController;
use Illuminate\Support\Facades\Route;
use Symfony\Component\HttpFoundation\Request;

// Stripe Webhooks
Route::post('/stripe/webhook', [StripeWebhookController::class, 'handleWebhook']);
// Route::post('/stripe-connect/webhook',[StripeConnectWebhookController::class,'handleWebhook']);

// Notificaitons
Route::get('/notifications', [NotificationsController::class, 'index'])->name('notifications.index')->middleware('auth:sanctum');
Route::patch('/notifications/{id}', [NotificationsController::class, 'update'])->name('notifications.update')->middleware('auth:sanctum');

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
    
    // Profile
    Route::get('/profile', [UserProfileController::class, 'show'])->name('customer.profile.show');
    Route::patch('/profile', [UserProfileController::class, 'update'])->name('customer.profile.update');
});

// Customer Routes
Route::prefix('customer')->middleware(['auth:sanctum','role:customer'],'status')->group(function () {

    // Order Price Estimation
    Route::post('orders/calculate-price', [OrderPriceController::class, 'estimate']);

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

        // Orders
        Route::get('/orders/{order}', [CourierOrderController::class, 'show'])->name('courier.orders.show');
        Route::patch('/orders/{order}/accept', [CourierOrderController::class, 'accept'])->name('courier.orders.accept');
        Route::get('/deliveries',[CourierDeliveryController::class, 'index'])->name('courier.deliveries.index');
        Route::get('/deliveries/{id}',[CourierDeliveryController::class, 'show'])->name('courier.deliveries.show');
        Route::patch('/deliveries/{id}/pickup', [CourierDeliveryController::class, 'pickup'])->name('courier.orders.pickup');
        Route::patch('/deliveries/{id}/deliver', [CourierDeliveryController::class, 'deliver'])->name('courier.orders.deliver');

        // Earnings
        Route::get('/earnings', [CourierEarningsController::class, 'index'])->name('courier.earnings.index');
        Route::get('/earnings/delivery-history', [CourierEarningsController::class, 'deliveryHistory'])->name('courier.earnings.delivery-history');
        Route::get('/earnings/payout-history', [CourierEarningsController::class, 'payoutHistory'])->name('courier.earnings.payout-history');

        // Payouts
        Route::post('/payouts',[CourierPayoutsController::class,'store'])->name('courier.payouts.store');

        // Payment Methods
        Route::get('/payment-methods',[CourierPaymentMethodsController::class,'index'])->name('courier.payment-methods.index');

        // Stripe
        // Route::get('/stripe/connect', [CourierStripeController::class, 'redirectToStripe'])->name('courier.stripe.connect');
        

    });


});

Route::prefix('courier')->group(function(){
    // Stripe callback
    Route::get('/stripe/callback', [CourierStripeController::class, 'handleStripeCallback'])->name('courier.stripe.callback');
});


// Admin Routes
Route::prefix('admin')->middleware(['auth:sanctum', 'role:admin'])->group(function () {

    // Dashboard
    Route::get('/dashboard-stats',[AdminDashboardStatsController::class,'index'])->name('admin.dashboard-stats');
    Route::get('/activity-stats',[AdminActivityStatsController::class,'index'])->name('admin.activity-stats');

    // Delivery
    Route::get('/deliveries', [DeliveryApprovalController::class, 'index'])->name('admin.deliveries.index');
    Route::patch('/deliveries/{orderNumber}', [DeliveryApprovalController::class, 'update'])->name('admin.deliveries.update');

    // Platform commission settings
    Route::get('/platform-commission-settings',[PlatformCommissionSettingController::class,'index'])->name('admin.platform-commission-settings.index');
    Route::patch('/platform-commission-settings',[PlatformCommissionSettingController::class,'update'])->name('admin.platform-commission-settings.update');

    // Refund Policy 
    Route::get('/refund_policy_settings',[RefundPolicySettingsController::class,'index'])->name('admin.refund-policy-settings.index');
    Route::patch('/refund_policy_settings',[RefundPolicySettingsController::class,'update'])->name('admin.refund-policy-settings.update');

    // Courier Payouts
    Route::get('/courier-payouts', [AdminCourierPayoutsController::class, 'index'])->name('courier.earnings.index');
    Route::patch('/courier-payouts/{id}', [AdminCourierPayoutsController::class, 'update'])->name('courier.earnings.update');

    // Get and Update Pricing Settings
    Route::get('/delivery-pricing-settings', [DeliveryFeeSettingController::class, 'index'])->name('admin.delivery-pricing-settings.index');
    Route::patch('/delivery-pricing-settings/distance', [DeliveryFeeSettingController::class, 'updateDistanceFee'])->name('admin.delivery-pricing-settings.update-distance');
    Route::patch('/delivery-pricing-settings/item-type', [DeliveryFeeSettingController::class, 'updateItemTypeFee'])->name('admin.delivery-pricing-settings.update-item-type');
    Route::patch('/delivery-pricing-settings/base-fare', [DeliveryFeeSettingController::class, 'updateBaseFare'])->name('admin.delivery-pricing-settings.update-base-fare');

    // Manage Users
    Route::get('/users',[AdminUsersController::class,'index'])->name('admin.users.index');
    Route::patch('/couriers/{courier}/verification', [AdminUsersController::class, 'updateVerification'])->name('admin.couriers.update.verification');
    Route::patch('/users/{user}/status', [AdminUsersController::class, 'updateStatus'])->name('admin.users.update.status');


    // Payout Thrseholds
    Route::get('/payout-thrseholds',[PayoutThrseholdsController::class,'index'])->name('admin.payout-thrseholds.index');
    Route::patch('/payout-thrseholds',[PayoutThrseholdsController::class,'update'])->name('admin.payout-thrseholds.update');
    
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


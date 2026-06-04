<?php

use App\Http\Controllers\Admin\SeoController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ChatController;
use App\Http\Controllers\Api\ContactUsController;
use App\Http\Controllers\Api\CustomerMobileListingController;
use App\Http\Controllers\Api\DeleteAccountController;
use App\Http\Controllers\Api\FaqController;
use App\Http\Controllers\Api\FilterMobileController;
use App\Http\Controllers\Api\HomeController;
use App\Http\Controllers\Api\MobileCartController;
use App\Http\Controllers\Api\MobileFilterController;
use App\Http\Controllers\Api\MobileListingController;
use App\Http\Controllers\Api\MobileSearchController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\OnlinePaymentController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\RequestFormController;
use App\Http\Controllers\Api\SocialLoginController;
use App\Http\Controllers\Api\VendorMobileListingController;
use App\Http\Controllers\Api\VendorSubscriptionController;
use App\Http\Controllers\PermissionController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\SideMenueController;
use App\Http\Controllers\SideMenuPermissionController;
use Illuminate\Support\Facades\Route;


/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

// Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
//     return $request->user();
// });

//Contact Us
Route::get('/contact-us', [ContactUsController::class, 'index']);

Route::post('/payment/initiate', [PaymentController::class, 'initiate']);
Route::get('/payment/return', [PaymentController::class, 'returnUrl'])->name('payment.return');
Route::post('/payment/ipn-listener', [PaymentController::class, 'ipnListener']);

Route::post('/payment/create', [PaymentController::class,'createPayment']);
Route::get('/payment/webview/{amount}/', [PaymentController::class,'webview']);
Route::get('/payment/return', [PaymentController::class,'returnUrl']);
Route::post('/update-payment-status', [PaymentController::class,'ipnListener']);
Route::get('/payment/verify/{reference}', [PaymentController::class,'verifyPayment']);


Route::post('/roles', [RoleController::class, 'store']);
Route::post('/permissions', [PermissionController::class, 'store']);
Route::post('/sidemenue', [SideMenueController::class, 'store']);
Route::post('/permission-insert', [SideMenuPermissionController::class, 'assignPermissions']);
Route::post('/seo-bulk', [SeoController::class, 'storeBulk'])
    ->name('seo.bulk-update');
//Chat Images API
Route::post('/chat-images', [ChatController::class, 'chatImages']);

Route::get('/vendors', [HomeController::class, 'allVendors']);

// Auth APIs
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/sendOtp', [AuthController::class, 'sendOtp']);
Route::post('/verifyOtp', [AuthController::class, 'verifyOtp']);
Route::post('/resetPassword', [AuthController::class, 'resetPassword']);
Route::post('/resend-otp', [AuthController::class, 'resendOtp']);
Route::prefix('forgot-password')->group(function () {
    Route::post('/send-otp', [AuthController::class, 'forgotPasswordSendOtp']);
    Route::post('/verify-otp', [AuthController::class, 'forgotPasswordVerifyOtp']);
    Route::post('/reset', [AuthController::class, 'forgotPasswordReset']);
    Route::post('/resend-otp', [AuthController::class, 'forgotPasswordResendOtp']);
});

//check email api
Route::post('/check-email', [AuthController::class, 'checkEmail']);
Route::post('/check-user', [AuthController::class, 'checkUser']);

//social login
Route::post('/social-login', [SocialLoginController::class, 'socialLogin']);
Route::post('/apple-login', [SocialLoginController::class, 'appleLogin']);

// get cities api
Route::get('/get-cities',[MobileFilterController::class,'getCities']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/getProfile', [AuthController::class, 'getProfile']);
    Route::post('/updateProfile', [AuthController::class, 'updateProfile']);
    Route::post('/changePassword', [AuthController::class, 'changePassword']);
    Route::delete('/deleteaccount', [DeleteAccountController::class, 'deleteAccount']);
    Route::delete('/vendordeleteaccount', [DeleteAccountController::class, 'vendordeleteAccount']);

    // mark as sold in mobile listing 
    Route::post('/listings/{id}/mark-as-sold', [MobileListingController::class, 'markAsSold']);

    //notifications
    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::get('/notifications/{notificationId}/seen', [NotificationController::class, 'seenNotification']);
    Route::delete('/delete-notification', [NotificationController::class, 'deleteAllNotifications']);
    Route::post('/chat', [NotificationController::class, 'Chat']);
    Route::post('/chat', [NotificationController::class, 'Chat']);
    // Mobile Request API
    Route::get('/getrequestedmobile', [RequestFormController::class, 'getRequestedMobile']);
    Route::post('/mobilerequestform', [RequestFormController::class, 'mobilerequestform']);

    //place order 
    Route::post('/place-order', [OnlinePaymentController::class, 'placeOrder']);

    //order and tracking
    Route::get('/orders', [OrderController::class, 'index']);
    Route::get('/orders/{id}', [OrderController::class, 'show']);
    Route::get('/orders/{id}/track', [OrderController::class, 'track']);
    Route::post('/reorder/{orderId}', [OrderController::class, 'reOrder']);
    Route::post('/shipping', [OrderController::class, 'shippingAddress']);
    Route::get('/shipping-address', [OrderController::class, 'getShippingAddress']);
    Route::delete('/shipping-address/{id}', [OrderController::class, 'deleteShippingAddress']);
    //vendor side track order
    Route::get('/vendor-orders', [OrderController::class, 'vendorOrders']);
    Route::get('/orders-vendor/{id}/track', [OrderController::class, 'trackVendor']);
    Route::post('/vendor/order/{id}/update-status',[OrderController::class, 'updateOrderStatusByVendor']);
    Route::get('/sales-report', [OrderController::class, 'salesReport']);
    Route::get('/orders-statistics', [OrderController::class, 'getOrderStatistics']);
    
    Route::get('/order-brand/{orderId}', [OrderController::class, 'getOrderBrand']);
    Route::get('/order-brand-model/{orderId}/{brandId}', [OrderController::class, 'getOrderBrandModel']);

    //vendor create device receipt
    Route::post('/devicereceipts/{orderId}', [OrderController::class, 'deviceReceipt']);
    //customer to show the receipt
    Route::get('/receipt/{deviceReceiptId}', [OrderController::class, 'getReceipt']);

    // filter orders
    Route::get('/filter-orders', [OrderController::class, 'filterOrders']);
    
    // vendor side Mobile Listing
    Route::post('/mobilelisting', [VendorMobileListingController::class, 'mobileListing']);
    Route::get('/getmobilelisting', [MobileListingController::class, 'getmobileListing']);
    Route::post('/editmobilelisting/{id}', [VendorMobileListingController::class, 'editListing']);
    Route::delete('/deletemobilelisting', [VendorMobileListingController::class, 'deleteMobileListing']);

    // deactivate vendor mobile listing
    Route::post('/deactivatemobile/{id}/listings', [VendorMobileListingController::class, 'deactivateMobileListing']);

    // customer side mobile listing
    Route::post('/customermobilelisting', [CustomerMobileListingController::class, 'customermobileListing']);
    Route::get('/getcustomermobilelisting', [CustomerMobileListingController::class, 'getcustomermobileListing']);  
    Route::delete('/customerdeletemobilelisting', [MobileListingController::class, 'customerdeleteMobileListing']);
    Route::get('/mobile-listing/{id}', [MobileListingController::class, 'getMobileListingById']);

    //Nearby Customer Listings
    Route::get('/getnearbycustomerlistings', [MobileListingController::class, 'getNearbyCustomerListings']);

    //faq
    Route::get('/faqs', [FaqController::class, 'index']);

    //customer add to cart
    Route::post('/mobile-cart-store', [MobileCartController::class, 'store']);
    Route::get('/mobile-cart-get', [MobileCartController::class, 'getCart']);
    Route::post('/mobile-cart-update', [MobileCartController::class, 'updateQuantity']);
    Route::delete('/mobile-cart-delete', [MobileCartController::class, 'deleteCart']);
     Route::post('/checkout', [MobileCartController::class, 'checkout']);

    //vendor subscription 
    Route::post('/vendor-subscription/subscribe', [VendorSubscriptionController::class, 'subscribe']);
    Route::get('/vendor-subscription/current', [VendorSubscriptionController::class, 'current']);
    Route::get('/subscription-plans', [VendorSubscriptionController::class, 'getSubscriptionPlans']);
    
    //search home screen
    Route::get('/mobile/search', [MobileSearchController::class, 'search']);
    Route::get('/mobile/search-history', [MobileSearchController::class, 'searchHistory']);
    Route::get('/mobile/search/history', [MobileSearchController::class, 'getSearchHistory']);
    Route::get('/mobile/recent-searches', [MobileSearchController::class, 'getRecentSearches']);
    Route::delete('/mobile/recent-search/delete', [MobileSearchController::class, 'delete']);
    Route::delete('/mobile/recent-search/delete-all', [MobileSearchController::class, 'deleteAll']);

    // Order list api
    Route::get('/orderlist', [OrderController::class, 'getorderlist']);
    Route::get('/customer-orderlist/{orderId}', [OrderController::class, 'customerorderlist']);

    // Get vendor order list api
     Route::get('/vendor-orderlist/{orderId}', [OrderController::class, 'vendorOrderList']);

     Route::post('/mobile-filters-data', [MobileFilterController::class, 'getData']);
     
     Route::get('/customer-last-search', [MobileFilterController::class, 'getLastSearch']);
});

// customer side Home Screen API
Route::get('/listings/top-selling', [HomeController::class, 'getTopSellingListings']);
Route::get('/listings/nearby', [HomeController::class, 'getNearbyListings']);

//filter searchers api
Route::get('/models/{brand_id}', [MobileFilterController::class, 'getModels']);
Route::get('/brands', [MobileFilterController::class, 'getBrands']);

Route::get('/getminmaxprice', [MobileFilterController::class, 'getMinMaxPrice']);


//Mobile listing preview api
Route::get('/mobilelistingpreview/{id}', [VendorMobileListingController::class, 'previewListing']);

Route::get('/customermobilelistingpreview/{id}', [CustomerMobileListingController::class, 'previewCustomerListing']);
// Device details
Route::get('/devicedetails/{id}', [HomeController::class, 'deviceDetails']);

Route::get('/vendor-mobiles-device-details/{id}', [HomeController::class, 'vendorMobileDeviceDetails']);

// Customer device details api
Route::get('/customerdevicedetails/{id}', [MobileListingController::class, 'getCustomerDeviceDetail']);

// delivery methods
Route::get('/delivery-method', [OnlinePaymentController::class, 'deliveryMethods']);




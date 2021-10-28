<?php
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
Route::post('login', 'API\UserController@registration')->name('login');
Route::prefix('User')->group(function () {
    Route::post('registration', 'API\UserController@registration');
});
Route::prefix('Product')->group(function () {
    Route::get('getCategoryList', 'API\ProductController@getCategoryList');
    Route::post('getProductList', 'API\ProductController@getProductList');
    Route::post('searchProduct', 'API\ProductController@searchProduct');
    Route::post('searchProductByCategory', 'API\ProductController@searchProductByCategory');
    Route::post('getProductById', 'API\ProductController@getProductById')->name('getProductById');
    Route::post('getProductByCategoryId', 'API\ProductController@getProductByCategoryId')->name('getProductByCategoryId');
    Route::post('sortByProduct', 'API\ProductController@sortByProduct');
});

Route::prefix('Settings')->group(function () {
    Route::get('getBannerList', 'API\SettingsController@getBannerList');
    Route::get('getFAQList', 'API\SettingsController@getFAQList');
    Route::get('getCityList', 'API\SettingsController@getCityList');
    Route::get('getAreaList', 'API\SettingsController@getAreaList');
    Route::get('getAddressList', 'API\SettingsController@getAddressList');
    Route::post('getAreaByCity', 'API\SettingsController@getAreaByCity');
});

Route::group(['middleware' => 'auth:api'], function(){

    Route::prefix('Product')->group(function () {      
        // Route::post('addupdateToCart', 'API\ProductController@addupdateToCart');
        // Route::get('getCartList', 'API\ProductController@getCartList');
        // Route::post('removeProductFromCart', 'API\ProductController@removeProductFromCart');
        Route::post('addupdateToWishlist', 'API\ProductController@addupdateToWishlist');
        Route::get('getWishlistList', 'API\ProductController@getWishlistList');
        Route::post('removeProductFromWishlist', 'API\ProductController@removeProductFromWishlist');
    });
    Route::prefix('User')->group(function () {
        Route::post('Logout', 'API\UserController@Logout');
        Route::get('getProfile', 'API\UserController@getProfile');
        Route::post('updateProfile', 'API\UserController@updateProfile');
        Route::post('addDeliveryAddress', 'API\UserController@addDeliveryAddress');
        Route::get('getAllDeliveryAddress', 'API\UserController@getAllDeliveryAddress');
        Route::post('updateDeliveryAddress', 'API\UserController@updateDeliveryAddress');
        Route::post('deleteDeliveryAddress', 'API\UserController@deleteDeliveryAddress');
        Route::get('getDefaultDeliveryDetails', 'API\UserController@getDefaultDeliveryDetails');
    });
 
    Route::prefix('Order')->group(function () {
        // Route::post('getPaymentSummary', 'API\OrdersController@getPaymentSummary');
        Route::get('getShippingCharge', 'API\OrdersController@getShippingCharge');
        Route::post('getPaymentSummary', 'API\OrdersController@getPaymentSummary');
        Route::get('getCouponList', 'API\OrdersController@getCouponList');
        Route::post('applyCoupon', 'API\OrdersController@applyCoupon');
        Route::post('placeOrder', 'API\OrdersController@placeOrder');
        Route::post('getMyOrderList', 'API\OrdersController@getMyOrderList');
        Route::post('getOrderDetailsById', 'API\OrdersController@getOrderDetailsById');
        // Route::get('deletePaymentSummary', 'API\OrdersController@deletePaymentSummary');

        Route::post('cancelledOrder', 'API\OrdersController@cancelledOrder');
        Route::post('raiseComplaint', 'API\OrdersController@raiseComplaint');
        Route::post('productReviewRating', 'API\OrdersController@productReviewRating');
        Route::post('getAllComplaint', 'API\OrdersController@getAllComplaint');

        Route::post('orderReviewRating', 'API\OrdersController@orderReviewRating');
        Route::post('getAllOrderRating', 'API\OrdersController@getAllOrderRating');

        Route::post('stripePaymentIntent', 'API\OrdersController@stripePaymentIntent');
    });

    Route::prefix('DeliveryBoy')->group(function () {
        Route::post('Logout', 'API\DeliveryBoyController@Logout');
        Route::get('getProfile', 'API\DeliveryBoyController@getProfile');
        Route::post('changeAvialableStatus', 'API\DeliveryBoyController@changeAvialableStatus');
        Route::post('updateProfile', 'API\DeliveryBoyController@updateProfile');
        Route::post('getPendingOrders', 'API\DeliveryBoyController@getPendingOrders');
        Route::post('getCompletedOrders', 'API\DeliveryBoyController@getCompletedOrders');
        Route::post('getOrderDetails', 'API\DeliveryBoyController@getOrderDetails');

        Route::post('startDelivery', 'API\DeliveryBoyController@startDelivery');
        Route::post('completeDelivery', 'API\DeliveryBoyController@completeDelivery');
        Route::post('onHoldDelivery', 'API\DeliveryBoyController@onHoldDelivery');
    });

    Route::post('getAllNotification', 'API\SettingsController@getAllNotification');
});

Route::prefix('DeliveryBoy')->group(function () {
    Route::post('userLogin', 'API\DeliveryBoyController@userLogin');
});

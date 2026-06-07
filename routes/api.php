<?php

use App\Http\Controllers\ContactController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\CouponController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\LocationController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\StaffController;
use App\Http\Controllers\UploadController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\TourController;
use App\Http\Controllers\TourScheduleController;
use Illuminate\Support\Facades\Route;

//public
Route::get('/locations/all', [LocationController::class, 'getAllLocation']);
Route::get('/locations/popular', [LocationController::class, 'getPopularLocations']);
Route::get('/tours/search', [TourController::class, 'indexForCustomer']);
Route::get('/tours/{id}', [TourController::class, 'getTourForCustomerById']);
Route::get('/locations/get-grouped-locations', [LocationController::class, 'getGroupedLocations']);
Route::post('/contact', [ContactController::class, 'send']);
Route::group(['prefix' => 'auth'], function () {
    // Đăng nhập/Đăng ký thường
    Route::post('login', [AuthController::class, 'login']);
    Route::post('login-admin', [AuthController::class, 'loginForAdmin']);
    Route::post('register', [AuthController::class, 'register']);
    Route::post('refresh', [AuthController::class, 'refresh']);
    Route::post('/forgot-password', [AuthController::class, 'sendResetLinkEmail']);
    Route::post('/reset-password', [AuthController::class, 'resetPassword']);
    // Social Login (Google/Facebook)
    Route::get('{provider}/url', [AuthController::class, 'getSocialAuthUrl']);
    Route::get('{provider}/callback', [AuthController::class, 'socialLoginCallback']);
    Route::get('/email/verify/{id}/{hash}', [AuthController::class, 'verifyEmail'])
        ->middleware(['signed'])->name('verification.verify');
    Route::post('email/resend', [AuthController::class, 'resendVerification'])
        ->middleware('throttle:6,1');
});

Route::group(['middleware' => 'auth:api', 'prefix' => 'auth'], function () {
    Route::get('profile', [AuthController::class, 'me']);
    Route::post ('change-password', [AuthController::class, 'changePassword']);
    Route::post('logout', [AuthController::class, 'logout']);
});

Route::group(['middleware' => 'auth:api', 'prefix' => 'users'], function () {
    Route::put('update-info', [UserController::class, 'updateCurrtentUser']);
});

Route::group(['middleware' => 'auth:api', 'prefix' => 'bookings'], function () {
    Route::put('{id}/cancel', [BookingController::class, 'CancelBooking']);
});


Route::group(['middleware' => 'auth:api', 'prefix' => 'files'], function () {
    Route::post('upload', [UploadController::class, 'uploadFile']);
    Route::post('upload-multiple', [UploadController::class, 'uploadMultipleFiles']);
});

Route::post('momo/ipn-handler', [PaymentController::class, 'momoIpn']);
Route::get('payments/check-status/{orderId}', [PaymentController::class, 'checkStatus']);
Route::post('payments/retry/{booking_id}', [PaymentController::class, 'retryPayment']);
Route::group(['prefix' => 'payments'], function () {
    Route::post('checkout', [PaymentController::class, 'checkout']);
});

Route::get('user-bookings/{id}', [BookingController::class, 'getBookingDetailForCustomer'])->middleware('auth:api');
Route::get('guest-bookings/{id}', [BookingController::class, 'getBookingDetailForGuest']);
Route::group(['middleware' => 'auth:api', 'prefix' => 'bookings'], function () {
    Route::get('user-bookings', [BookingController::class, 'getUserBookings']);
});
Route::group(['prefix' => 'coupons'], function () {
    Route::get('for-payment', [CouponController::class, 'getCouponForPayment']);
});

Route::get('/tours/tour-schedules/{id}', [TourScheduleController::class, 'getTourScheduleById']);
Route::get('admin/tours/search', [TourController::class, 'indexForAdmin']);
// Route::get('/departs', [DepartController::class, 'index']);
Route::group(['middleware' => ['auth:api'], 'prefix' => 'admin'], function () {
    Route::get('/users/{id}', [UserController::class, 'getUserById']);
    Route::get('/locations/{id}', [LocationController::class, 'getLocationById']);
    Route::get('/tours/{id}', [TourController::class, 'getTourById']);
    Route::get('/tours/tour-schedules/{id}', [TourScheduleController::class, 'getTourScheduleById']);
    Route::get('/bookings/{id}', [BookingController::class, 'getBookingDetail']);
    Route::get('/coupons/{id}', [CouponController::class, 'getCouponById']);
    Route::get('/coupons/detail/{id}', [CouponController::class, 'getCouponDetailById']);


    Route::get('/staffs/all', [StaffController::class, 'getAllStaff']);
    Route::get('/staffs', [StaffController::class, 'index']);
    Route::post('/staffs/add_staff', [StaffController::class, 'createStaff']);
    Route::put('/staffs/update-info/{id}', [StaffController::class, 'updateStaff']);
    Route::put('/staffs/{id}/active', [StaffController::class, 'changeStatusStaff']);
    Route::delete('/staffs/{id}/delete', [StaffController::class, 'deleteStaff']);

    Route::get('/customers', [CustomerController::class, 'index']);
    Route::post('/customers/add_customer', [CustomerController::class, 'createCustomer']);
    Route::put('/customers/update-info/{id}', [CustomerController::class, 'updateCustomer']);
    Route::put('/customers/{id}/active', [CustomerController::class, 'changeStatusCustomer']);
    Route::delete('/customers/{id}/delete', [CustomerController::class, 'deleteCustomer']);

    Route::get('/locations', [LocationController::class, 'index']);
    Route::post('/locations/add_location', [LocationController::class, 'createLocation']);
    Route::put('/locations/update-info/{id}', [LocationController::class, 'updateLocation']);
    Route::put('/locations/{id}/active', [LocationController::class, 'changeStatusLocation']);
    Route::delete('/locations/{id}/delete', [LocationController::class, 'deleteLocation']);

    Route::get('/tours', [TourController::class, 'index']);
    Route::post('/tours/add_tour', [TourController::class, 'createTour']);
    Route::put('/tours/update-info/{id}', [TourController::class, 'updateTour']);
    Route::put('/tours/{id}/active', [TourController::class, 'changeStatusTour']);
    Route::delete('/tours/{id}/delete', [TourController::class, 'deleteTour']);
    Route::get('/tours/{tourId}/tour-schedules', [TourScheduleController::class, 'index']);
    Route::get('/tours/{tourId}/tour-schedules/all', [TourScheduleController::class, 'getTourScheduleByTourId']);
    Route::post('/tours/tour-schedules/add_tourschedule', [TourScheduleController::class, 'createTourSchedule']);
    Route::put('/tours/tour-schedules/update-info/{id}', [TourScheduleController::class, 'updateTourSchedule']);
    Route::put('/tours/tour-schedules/{id}/active', [TourScheduleController::class, 'changeStatus']);

    Route::get('/coupons', [CouponController::class, 'index']);
    Route::post('/coupons/add_coupon', [CouponController::class, 'createCoupon']);
    Route::put('/coupons/update-info/{id}', [CouponController::class, 'updateCoupon']);
    Route::put('/coupons/{id}/active', [CouponController::class, 'changeStatusCoupon']);
    Route::delete('/coupons/{id}/delete', [CouponController::class, 'deleteCoupon']);

    Route::get(('/roles/functions'), [RoleController::class, 'getAllFunction']);
    Route::get('/roles', [RoleController::class, 'getPagenatedRole']);
    Route::get('/roles/staff', [RoleController::class, 'getRoleForStaff']);
    Route::post('/roles/add_role', [RoleController::class, 'create']);
    Route::get('/roles/{id}', [RoleController::class, 'getRoleById']);
    Route::put('/roles/{id}', [RoleController::class, 'update']);
    Route::delete('/roles/{id}/delete', [RoleController::class, 'delete']);

    Route::get('/bookings', [BookingController::class, 'index']);
    Route::post('/bookings/add_booking', [BookingController::class, 'create']);
    Route::put('/bookings/{id}/status', [BookingController::class, 'changeStatus']);
    Route::put('/bookings/{id}', [BookingController::class, 'update']);

    Route::get('/dashboard/stats', [DashboardController::class, 'getStats']);
});
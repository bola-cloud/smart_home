<?php

use Illuminate\Support\Facades\Route;
use Mcamara\LaravelLocalization\Facades\LaravelLocalization;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/
Route::get('/mqtt/listen', [\App\Http\Controllers\MqttController::class, 'startListening']);
Route::get('/error/not-admin', function () {
    return view('errors_not_admin');
})->name('error.not_admin');
Route::get('/pricing', function () {
    return view('layouts.pricing');
})->name('error.not_admin');

Route::get('/villa-pricing', [\App\Http\Controllers\PricingUserController::class, 'index'])->name('user.villa-pricing');
Route::post('/villa-pricing/calculate', [\App\Http\Controllers\PricingUserController::class, 'calculate'])->name('user.villa-pricing.calculate');

Route::group([
    'prefix' => LaravelLocalization::setLocale(),
    'middleware' => [
        \Mcamara\LaravelLocalization\Middleware\LocaleSessionRedirect::class,
        \Mcamara\LaravelLocalization\Middleware\LaravelLocalizationRedirectFilter::class,
        \Mcamara\LaravelLocalization\Middleware\LaravelLocalizationViewPath::class,
        'auth:sanctum',
        config('jetstream.auth_session'),
        'verified',
        'admin.category'  // Middleware to restrict access to admin users
    ]
], function () {
    Route::get('/', [\App\Http\Controllers\Admin\Dashboard::class, 'index'])->name('admin.dashboard');
    Route::resource('users', \App\Http\Controllers\Admin\UserController::class);
    Route::get('/admin/users/{user}/details', [\App\Http\Controllers\Admin\UserController::class, 'showDetails'])->name('users.details');
    Route::resource('projects', \App\Http\Controllers\Admin\ProjectController::class);
    Route::resource('sections', \App\Http\Controllers\Admin\SectionController::class);
    Route::resource('devices', \App\Http\Controllers\Admin\DeviceController::class);
    Route::get('/devices/{device}/add-components', [\App\Http\Controllers\Admin\ComponentController::class, 'addComponents'])->name('devices.add_components');
    Route::get('/devices/{device}/components', [\App\Http\Controllers\Admin\ComponentController::class, 'showComponents'])->name('devices.show_components');
    Route::resource('/components', \App\Http\Controllers\Admin\ComponentController::class);
    Route::post('/components/store-for-device/{device}', [\App\Http\Controllers\Admin\ComponentController::class, 'storeForDevice'])->name('components.store_for_device');  
    Route::post('/devices/{device}/update-order', [\App\Http\Controllers\Admin\ComponentController::class, 'updateOrderAndEdit'])->name('components.update_order_and_edit');
    Route::resource('components', \App\Http\Controllers\Admin\ComponentController::class);
    Route::resource('blogs', App\Http\Controllers\Admin\BlogController::class);
    Route::resource('products', App\Http\Controllers\Admin\ProductController::class);

    Route::get('checkouts', [App\Http\Controllers\Admin\OrderController::class, 'index'])->name('checkouts.index');
    Route::put('checkouts/{checkoutId}/{status}', [App\Http\Controllers\Admin\OrderController::class, 'updateStatus'])->name('checkouts.updateStatus');
    Route::resource('device_types', \App\Http\Controllers\Admin\DeviceTypeController::class);

    // Display the shipping update form
    // Main page
    Route::get('/regions-districts', [\App\Http\Controllers\Admin\ShippingController::class, 'index'])->name('shipping.form');

    // Fetch cities and districts
    Route::get('/fetch-cities', [\App\Http\Controllers\Admin\ShippingController::class, 'fetchCities'])->name('fetch.cities');
    Route::get('/fetch-districts', [\App\Http\Controllers\Admin\ShippingController::class, 'fetchDistricts'])->name('fetch.districts');

    // Store regions, cities, and districts
    Route::post('/store-region', [\App\Http\Controllers\Admin\ShippingController::class, 'storeRegion'])->name('regions.store');
    Route::post('/store-city', [\App\Http\Controllers\Admin\ShippingController::class, 'storeCity'])->name('cities.store');
    Route::post('/store-district', [\App\Http\Controllers\Admin\ShippingController::class, 'storeDistrict'])->name('districts.store');
    Route::post('/districts/update-shipping', [\App\Http\Controllers\Admin\ShippingController::class, 'updateShipping'])->name('districts.update.shipping');


    // //  Pricing routes // /// 
    // Routes for Rooms
    Route::get('/rooms', [\App\Http\Controllers\Admin\Pricing\RoomController::class, 'index'])->name('pricing.rooms.index');
    Route::get('/rooms/create', [\App\Http\Controllers\Admin\Pricing\RoomController::class, 'create'])->name('pricing.rooms.create');
    Route::post('/rooms', [\App\Http\Controllers\Admin\Pricing\RoomController::class, 'store'])->name('pricing.rooms.store');
    Route::get('/rooms/{id}/edit', [\App\Http\Controllers\Admin\Pricing\RoomController::class, 'edit'])->name('pricing.rooms.edit');
    Route::put('/rooms/{id}', [\App\Http\Controllers\Admin\Pricing\RoomController::class, 'update'])->name('pricing.rooms.update');
    Route::delete('/rooms/{id}', [\App\Http\Controllers\Admin\Pricing\RoomController::class, 'destroy'])->name('pricing.rooms.destroy');

    // Routes for Devices
    Route::get('/rooms/{room_id}/devices', [\App\Http\Controllers\Admin\Pricing\RoomDeviceController::class, 'index'])->name('pricing.devices.index');
    Route::get('/rooms/{room_id}/devices/create', [\App\Http\Controllers\Admin\Pricing\RoomDeviceController::class, 'create'])->name('pricing.devices.create');
    Route::post('/rooms/{room_id}/devices', [\App\Http\Controllers\Admin\Pricing\RoomDeviceController::class, 'store'])->name('pricing.devices.store');
    Route::delete('/devices/{id}', [\App\Http\Controllers\Admin\Pricing\RoomDeviceController::class, 'destroy'])->name('pricing.devices.destroy');
    Route::get('/room/devices/{id}/edit', [\App\Http\Controllers\Admin\Pricing\RoomDeviceController::class, 'edit'])->name('pricing.devices.edit');
    Route::put('/room/devices/{id}', [\App\Http\Controllers\Admin\Pricing\RoomDeviceController::class, 'update'])->name('pricing.devices.update');

});
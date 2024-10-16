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

Route::group([
    'prefix' => LaravelLocalization::setLocale(),
    'middleware' => [
        \Mcamara\LaravelLocalization\Middleware\LocaleSessionRedirect::class,
        \Mcamara\LaravelLocalization\Middleware\LaravelLocalizationRedirectFilter::class,
        \Mcamara\LaravelLocalization\Middleware\LaravelLocalizationViewPath::class,
        'auth:sanctum',
        config('jetstream.auth_session'),
        'verified',
    ]
], function () {
    Route::get('/', [\App\Http\Controllers\Admin\Dashboard::class, 'index'])->name('admin.dashboard');
    Route::resource('users', \App\Http\Controllers\Admin\UserController::class);
    Route::resource('projects', \App\Http\Controllers\Admin\ProjectController::class);
    Route::resource('sections', \App\Http\Controllers\Admin\SectionController::class);
    Route::resource('devices', \App\Http\Controllers\Admin\DeviceController::class);
    Route::get('/devices/{device}/add-components', [\App\Http\Controllers\Admin\ComponentController::class, 'addComponents'])->name('devices.add_components');
    Route::get('/devices/{device}/components', [\App\Http\Controllers\Admin\ComponentController::class, 'showComponents'])->name('devices.show_components');
    Route::post('/components/store-for-device/{device}', [\App\Http\Controllers\Admin\ComponentController::class, 'storeForDevice'])->name('components.store_for_device');  
    Route::post('/devices/{device}/update-order', [\App\Http\Controllers\Admin\ComponentController::class, 'updateOrderAndEdit'])->name('components.update_order_and_edit');
    Route::resource('components', \App\Http\Controllers\Admin\DeviceController::class);
    Route::resource('blogs', App\Http\Controllers\Admin\BlogController::class);

    Route::resource('device_types', \App\Http\Controllers\Admin\DeviceTypeController::class);
});
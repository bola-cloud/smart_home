<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use Illuminate\Support\Facades\Mail;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/


Route::get('/send-test-email', function () {
    Mail::raw('This is a test email', function ($message) {
        $message->to('bola.ishak41@gmail.com')
                ->subject('Test Email');
    });

    return 'Test email sent!';
});


Route::post('register', [AuthController::class, 'register']);
Route::post('login', [AuthController::class, 'login']);
Route::post('password/request-reset', [AuthController::class, 'requestPasswordReset']);
Route::post('password/reset', [AuthController::class, 'resetPassword']);

// Protected Routes (Require authentication)
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    // Logout route
    Route::post('logout', [AuthController::class, 'logout']);
});
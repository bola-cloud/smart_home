<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Auth\User\AuthController;
use App\Http\Controllers\Api\ConnectionController;
use App\Http\Controllers\Api\Blogs\BlogController;
use Illuminate\Support\Facades\Mail;
use App\Http\Controllers\Api\MqttController;
use App\Http\Controllers\Api\Projects\ProjectController;
use App\Http\Controllers\Api\Sections\SectionController;
use App\Http\Controllers\Api\Auth\Member\MemberController;
use App\Http\Controllers\Api\Devices\DeviceController;
use App\Http\Controllers\Api\Component\ComponentController;

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


// Route::get('/send-test-email', function () {
//     Mail::raw('This is a test email', function ($message) {
//         $message->to('bola.ishak41@gmail.com')
//                 ->subject('Test Email');
//     });

//     return 'Test email sent!';
// });


Route::post('register', [AuthController::class, 'register']);
Route::post('login', [AuthController::class, 'login']);
Route::post('password/request-reset', [AuthController::class, 'requestPasswordReset']);
Route::post('new-paswword', [AuthController::class, 'resetPassword']);


// Member login and logout routes
Route::post('/member/login', [MemberController::class, 'login']);
Route::post('/member/logout', [MemberController::class, 'logout'])->middleware('auth:sanctum');
Route::post('/member/password/request-reset', [MemberController::class, 'requestPasswordReset']);
Route::post('/member/password/reset', [MemberController::class, 'resetPassword']);


Route::post('/publish-device', [MqttController::class, 'publishToDevice']);

// Protected Routes (Require authentication)
Route::middleware(['auth:sanctum', 'identifyUserOrMember'])->group(function () {
    Route::get('/user', function (Request $request) {
        return $request->user();
    });
    // Connect Mobile route
    Route::post('/mobile/connection', [ConnectionController::class, 'connectMobile']);
    Route::post('/confirm/activation', [ConnectionController::class, 'confirmActivation']);
    // Blogs route
    Route::get('/blogs', [BlogController::class, 'index']);
    
    // User projects route
    Route::get('/user/projects', [ProjectController::class, 'userProjects']);  // No need for additional middleware here
    Route::get('/projects/sections', [ProjectController::class, 'getProjectSections']);
    Route::post('/projects', [ProjectController::class, 'store']);
    Route::get('/devices', [DeviceController::class, 'getDevices']);
    // Component retrieval routes for both users and members
    Route::get('/components', [ComponentController::class, 'getComponents']);

    Route::post('/members/add', [AuthController::class, 'addMemberWithPermissions']);

    // Create a section for a specific project
    Route::post('/projects/{project}/sections', [SectionController::class, 'store']);
    // Logout route
    Route::post('/logout', [AuthController::class, 'logout']);
});
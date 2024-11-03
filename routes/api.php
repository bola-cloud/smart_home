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
use App\Http\Controllers\Api\User\UserController;
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
    Route::get('/projects/sections', [SectionController::class, 'getAccessibleSections']);
    Route::post('/projects', [ProjectController::class, 'store']);
    Route::get('/devices', [DeviceController::class, 'getDevices']);
    // Component retrieval routes for both users and members
    Route::get('/components', [ComponentController::class, 'getComponents']);

    Route::post('/members/add', [MemberController::class, 'addMemberWithPermissions']);
    Route::put('/edit/user/profile', [UserController::class, 'updateProfile']);
    Route::get('/project/{project}/access', [ProjectController::class, 'getProjectAccessDetails']);
    
    //get component permissions with users
    Route::get('/project/get-users-with-component-permission', [MemberController::class, 'getUsersWithComponentPermission']);

    // Create a section for a specific project
    Route::post('/projects/{project}/sections', [SectionController::class, 'store']);
    //edit apis
    Route::put('/project/{project}/edit-name', [ProjectController::class, 'editProjectName']);
    Route::put('/section/{section}/edit-name', [SectionController::class, 'editSectionName']);
    Route::put('/component/{component}/edit-name', [ComponentController::class, 'editComponentName']);
    Route::put('/device/{device}/edit-name', [DeviceController::class, 'editDeviceName']);

    //delete apis
    Route::delete('/project/{project}/delete', [ProjectController::class, 'deleteProject']);
    Route::delete('/section/{section}/delete', [SectionController::class, 'deleteSection']);
    Route::delete('/device/{device}/delete', [DeviceController::class, 'deleteDevice']);
    Route::delete('/projects/remove-member', [MemberController::class, 'removeMember']);
    Route::post('/project/grant-full-access', [MemberController::class, 'grantFullAccessToMember']);

    // Logout route
    Route::post('/logout', [AuthController::class, 'logout']);
});
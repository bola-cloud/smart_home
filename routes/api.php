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
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\Conditions\ConditionsController;
use App\Http\Controllers\Api\IrCode\IrCodeController;
use App\Http\Controllers\Api\Products\ProductController;
use App\Http\Controllers\Api\Cart\CartController;

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
Route::post('/get-last-message', [MqttController::class, 'subscribeToTopic']);

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
    Route::get('/project/member-permissions', [MemberController::class, 'getMemberPermissions']);

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

    Route::get('/user/notifications', [NotificationController::class, 'getUserNotifications']);

    // Conditions
    Route::post('/conditions', [ConditionsController::class, 'store']);
    Route::get('/conditions/{project_id}', [ConditionsController::class, 'index']);
    Route::delete('/delete/{condition_id}/conditions', [ConditionsController::class, 'delete']);
    Route::delete('conditions/{conditionId}/case/{caseId}', [ConditionsController::class, 'deleteCase']);
    Route::post('/condition/add/case', [ConditionsController::class, 'addCase']);
    Route::post('/condition/edit/case', [ConditionsController::class, 'editCase']);
    Route::post('/inactivate-case', [ConditionsController::class, 'inactivateCase']);

    Route::post('/ir/attach/file', [IrCodeController::class, 'attachFilePaths']);
    Route::post('/create/device-file', [IrCodeController::class, 'createDeviceFile']);
    Route::post('/ir/deattach', [IrCodeController::class, 'deattachFilePaths']);

    Route::get('/products', [ProductController::class, 'index']);
    // Logout route
    Route::post('/logout', [AuthController::class, 'logout']);
});
Route::get('/device-types', [IrCodeController::class, 'getDeviceTypes']);
Route::get('/{deviceType}/brands', [IrCodeController::class, 'getBrands']);
Route::get('/{deviceType}/{brand}/files', [IrCodeController::class, 'getFiles']);
Route::get('/{deviceType}/{brand}/{filename}', [IrCodeController::class, 'getFileContent']);
// Route for retrieving all files with content
Route::get('/ircode/files/content/{deviceType}/{brand}', [IrCodeController::class, 'getAllFilesContent']);

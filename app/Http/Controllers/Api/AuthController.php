<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Support\Carbon;
use App\Mail\ResetPasswordMail;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'phone_number' => 'required',
        ]);
    
        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation errors',
                'status' => false,
                'data' => $validator->errors(),
            ], 422);
        }
    
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'phone_number' => $request->phone_number,
        ]);
    
        $token = $user->createToken('auth_token')->plainTextToken;
    
        return response()->json([
            'message' => 'User registered successfully',
            'status' => true,
            'data' => [
                'user' => $user,
                'access_token' => $token,
                'token_type' => 'Bearer',
            ],
        ], 201);
    }    

    public function login(Request $request)
    {
        $loginData = $request->only('login', 'password');
    
        // Determine if the login field is an email or a phone number
        $loginType = filter_var($loginData['login'], FILTER_VALIDATE_EMAIL) ? 'email' : 'phone_number';
    
        // Attempt to log the user in using the email or phone number
        if (!auth()->attempt([$loginType => $loginData['login'], 'password' => $loginData['password']])) {
            return response()->json([
                'message' => 'Invalid login details',
                'status' => false,
                'data' => null,
            ], 401);
        }
    
        // Retrieve the user
        $user = User::where($loginType, $loginData['login'])->firstOrFail();
        
        // Create token
        $token = $user->createToken('auth_token')->plainTextToken;
    
        return response()->json([
            'message' => 'Login successful',
            'status' => true,
            'data' => [
                'user' => $user,
                'access_token' => $token,
                'token_type' => 'Bearer',
            ],
        ], 200);
    }  

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
    
        return response()->json([
            'message' => 'Successfully logged out',
            'status' => true,
            'data' => null,
        ], 200);
    }

    // Request a password reset and send a reset code to the email
    public function requestPasswordReset(Request $request)
    {
        // Custom validation to return a more descriptive error if email doesn't exist
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
        ]);
    
        if ($validator->fails()) {
            return response()->json([
                'message' => 'The email field is required and must be a valid email address.',
                'status' => false,
                'errors' => $validator->errors(),
            ], 422);
        }
    
        // Check if the email exists in the users table
        $user = User::where('email', $request->email)->first();
    
        if (!$user) {
            return response()->json([
                'message' => 'The email address does not exist in our records.',
                'status' => false,
            ], 404);
        }
    
        // Generate a random 6-character reset code
        $resetCode = Str::random(6);
        $user->reset_code = $resetCode;
        $user->reset_code_expires_at = Carbon::now()->addMinutes(10); // Reset code valid for 30 minutes
        $user->save();
    
        // Send the reset code to the user via email with a professional format
        Mail::raw("Hello,
    
            You have requested a password reset for your Mazaya account. Please use the following code to reset your password:
    
            Reset Code: $resetCode
    
            This code will expire in 30 minutes. If you did not request a password reset, please contact our support team immediately at support@mazaya-aec.com.
    
            Thank you,
            Mazaya Team
    
            ---
    
            Mazaya | www.mazaya-aec.com | support@mazaya-aec.com", function ($message) use ($user) {
            $message->to($user->email)
                    ->subject('Mazaya - Your Password Reset Request');
        });
    
        // Return the reset code in the API response (for testing or development purposes)
        return response()->json([
            'message' => 'Reset code sent to your email address.',
            'status' => true,
            'data' => [
                'email' => $user->email,
                'reset_code' => $resetCode, // You can remove this in production for security
            ],
        ], 200);
    }       

    // Reset the password using the reset code
    public function resetPassword(Request $request)
    {
        // Validate the incoming request
        $request->validate([
            'email' => 'required|email|exists:users,email',
            'reset_code' => 'required|string',
            'password' => 'required|string|min:8|confirmed',
        ]);
    
        if ($validator->fails()) {
            return response()->json([
                'message' => 'The email field is required and must be a valid email address.',
                'status' => false,
                'errors' => $validator->errors(),
            ], 422);
        }
    
        // Check if the email exists in the users table
        $user = User::where('email', $request->email)->first();
    
        if (!$user) {
            return response()->json([
                'message' => 'The email address does not exist in our records.',
                'status' => false,
            ], 404);
        }
        // Check if the reset code exists and was sent
        if (is_null($user->reset_code) || is_null($user->reset_code_expires_at)) {
            return response()->json([
                'message' => 'No reset code has been sent to this email address.',
                'status' => false,
                'data' => null,
            ], 400);
        }
    
        // Validate reset code and expiry
        if ($user->reset_code !== $request->reset_code || Carbon::now()->isAfter($user->reset_code_expires_at)) {
            return response()->json([
                'message' => 'Invalid or expired reset code.',
                'status' => false,
                'data' => null,
            ], 400);
        }
    
        // Update password and clear reset code
        $user->password = Hash::make($request->password);
        $user->reset_code = null;
        $user->reset_code_expires_at = null;
        $user->save();
    
        return response()->json([
            'message' => 'Password reset successfully.',
            'status' => true,
            'data' => null,
        ], 200);
    }
    
}

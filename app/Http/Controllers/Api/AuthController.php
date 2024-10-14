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
            'phone' => 'nullable|string',
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
            'phone' => $request->phone,
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
        $request->validate([
            'email' => 'required|email|exists:users,email',
        ]);
    
        // Find the user by email
        $user = User::where('email', $request->email)->first();
    
        // Generate a random 6-character reset code
        $resetCode = Str::random(6);
        $user->reset_code = $resetCode;
        $user->reset_code_expires_at = Carbon::now()->addMinutes(30); // Reset code valid for 30 minutes
        $user->save();
    
        // Send the reset code to the user via email
        Mail::raw("Your password reset code is: $resetCode", function ($message) use ($user) {
            $message->to($user->email)
                    ->subject('Password Reset Code');
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
        $request->validate([
            'email' => 'required|email|exists:users,email',
            'reset_code' => 'required|string',
            'password' => 'required|string|min:8|confirmed',
        ]);

        // Find the user by email
        $user = User::where('email', $request->email)->first();

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

<?php

namespace App\Http\Controllers\Api\Auth\Member;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Member;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Support\Carbon;

class MemberController extends Controller
{
    public function login(Request $request)
    {
        // Validate the request data
        $request->validate([
            'login' => 'required|string',    // Accept email or phone number
            'password' => 'required|string', // Password
        ]);
    
        $loginData = $request->only('login', 'password');
    
        // Determine if the login field is an email or phone number
        $loginType = filter_var($loginData['login'], FILTER_VALIDATE_EMAIL) ? 'email' : 'phone_number';
    
        // Attempt to find the member by email or phone number
        $member = Member::where($loginType, $loginData['login'])->first();
    
        // Validate credentials
        if (! $member || ! Hash::check($loginData['password'], $member->password)) {
            return response()->json([
                'status' => false,
                'message' => 'Invalid login details',
            ], 401);
        }
    
        // Create a Sanctum token for the member
        $token = $member->createToken('member-token')->plainTextToken;
    
        return response()->json([
            'status' => 'success',
            'message' => 'Login successful',
            'data' => [
                'member' => $member,
                'access_token' => $token,
                'token_type' => 'Bearer',
            ],
        ], 200);
    }    

    // Member logout
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        
        return response()->json(['message' => 'Logged out successfully'], 200);
    }

    public function requestPasswordReset(Request $request)
    {
        // Validate the email field
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

        // Check if the email exists in the members table
        $member = Member::where('email', $request->email)->first();

        if (!$member) {
            return response()->json([
                'message' => 'The email address does not exist in our records.',
                'status' => false,
            ], 404);
        }

        // Generate a random 6-character reset code
        $resetCode = Str::random(6);
        $member->reset_code = $resetCode;
        $member->reset_code_expires_at = Carbon::now()->addMinutes(10); // Reset code valid for 10 minutes
        $member->save();

        // Send the reset code to the member via email
        Mail::raw("Hello,

            You have requested a password reset for your account. Please use the following code to reset your password:

            Reset Code: $resetCode

            This code will expire in 10 minutes. If you did not request a password reset, please contact our support team immediately.

            Thank you!", function ($message) use ($member) {
                $message->to($member->email)
                    ->from('info@mazaya-aec.com', 'Mazaya Smart Home')  // Authenticated email
                    ->replyTo('support@mazaya-aec.com', 'Mazaya Support')  // The reply-to email address
                    ->subject('Password Reset Request');
        });

        return response()->json([
            'message' => 'Reset code sent to your email address.',
            'status' => true,
            'data' => [
                'email' => $member->email,
                'reset_code' => $resetCode, // For testing, remove in production
            ],
        ], 200);
    }

    // Reset the password for a member using the reset code
    public function resetPassword(Request $request)
    {
        // Validate the request data
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|exists:members,email',
            'reset_code' => 'required|string',
            'password' => 'required|string|min:8|confirmed',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'status' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        // Retrieve the member
        $member = Member::where('email', $request->email)->first();

        if (!$member) {
            return response()->json([
                'message' => 'The email address does not exist in our records.',
                'status' => false,
            ], 404);
        }

        // Check if the reset code is valid and not expired
        if ($member->reset_code !== $request->reset_code || Carbon::now()->isAfter($member->reset_code_expires_at)) {
            return response()->json([
                'message' => 'Invalid or expired reset code.',
                'status' => false,
            ], 400);
        }

        // Update the member's password and clear the reset code
        $member->password = Hash::make($request->password);
        $member->reset_code = null;
        $member->reset_code_expires_at = null;
        $member->save();

        return response()->json([
            'message' => 'Password reset successfully.',
            'status' => true,
        ], 200);
    }
}

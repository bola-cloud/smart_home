<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Notification;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    /**
     * Retrieve notifications for the authenticated user.
     */
    public function getUserNotifications(Request $request)
    {
        // Get the authenticated user
        $user = Auth::user();

        // Check if the user is authenticated
        if (!$user) {
            return response()->json([
                'status' => false,
                'message' => 'User not authenticated',
            ], 401);
        }

        // Retrieve notifications for the user
        $notifications = Notification::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->get();

        // Decode JSON data for each notification
        $notifications = $notifications->map(function($notification) {
            $notification->data = json_decode($notification->data, true);
            return $notification;
        });

        return response()->json([
            'status' => true,
            'message' => 'Notifications retrieved successfully',
            'data' => $notifications,
        ], 200);
    }
}
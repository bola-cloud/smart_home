<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Checkout;
use App\Models\User;  // Add User model for filtering by user

class OrderController extends Controller
{
    // Show the list of orders
    public function index(Request $request)
    {
        // Get all users for the filter dropdown
        $users = User::all();

        // Start the query to fetch checkouts
        $checkouts = Checkout::query();

        // Filter by checkout code if provided
        if ($request->filled('search_code')) {
            $checkouts->where('code', 'like', '%' . $request->search_code . '%');
        }

        // Filter by user if provided
        if ($request->filled('user_id')) {
            $checkouts->where('user_id', $request->user_id);
        }

        // Paginate results
        $checkouts = $checkouts->paginate(10);

        return view('admin.orders.index', compact('checkouts', 'users'));
    }

    // Update order status to 'completed'

    public function updateStatus(Request $request, $checkoutId, $status)
    {
        // Find the checkout by ID
        $checkout = Checkout::find($checkoutId);
    
        if (!$checkout) {
            return redirect()->route('checkouts.index')->with('error', __('lang.error_checkout_not_found'));
        }
    
        // Update the checkout status
        $checkout->status = $status;
        $checkout->save();
    
        // Redirect with appropriate success or error message
        if ($status == 'completed') {
            return redirect()->route('checkouts.index')->with('success', __('lang.success_checkout_completed'));
        } elseif ($status == 'failed') {
            return redirect()->route('checkouts.index')->with('error', __('lang.success_checkout_failed'));
        }
    
        return redirect()->route('checkouts.index')->with('error', __('lang.error_invalid_status'));
    }    
}

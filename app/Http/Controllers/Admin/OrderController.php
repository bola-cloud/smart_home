<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Checkout;

class OrderController extends Controller
{
    // Show the list of orders
    public function index()
    {
        // Fetch all orders with their current status
        $checkouts  = Checkout::paginate(20);
        return view('admin.orders.index', compact('checkouts'));
    }

    // Update order status to 'completed'

    public function updateStatus(Request $request, $checkoutId, $status)
    {
        // Find the checkout by ID
        $checkout = Checkout::find($checkoutId);
    
        if (!$checkout) {
            return redirect()->route('checkouts.index')->with('error', __('messages.error_checkout_not_found'));
        }
    
        // Update the checkout status
        $checkout->status = $status;
        $checkout->save();
    
        // Redirect with appropriate success or error message
        if ($status == 'completed') {
            return redirect()->route('checkouts.index')->with('success', __('messages.success_checkout_completed'));
        } elseif ($status == 'failed') {
            return redirect()->route('checkouts.index')->with('error', __('messages.success_checkout_failed'));
        }
    
        return redirect()->route('checkouts.index')->with('error', __('messages.error_invalid_status'));
    }    
}

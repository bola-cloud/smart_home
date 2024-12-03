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

    public function updateStatus($checkoutId)
    {
        // Find the checkout by ID
        $checkout = Checkout::find($checkoutId);
    
        if (!$checkout) {
            return redirect()->route('checkouts.index')->with('error', __('lang.error_checkout_not_found'));
        }
    
        // Update the status to 'completed'
        $checkout->status = 'completed';
        $checkout->save();
    
        // Redirect back with success message
        return redirect()->route('checkouts.index')->with('success', __('lang.success_checkout_completed'));
    }
}

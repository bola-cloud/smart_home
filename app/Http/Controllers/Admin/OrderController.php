<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Order;

class OrderController extends Controller
{
    // Show the list of orders
    public function index()
    {
        // Fetch all orders with their current status
        $orders = Order::paginate(20);
        return view('admin.orders.index', compact('orders'));
    }

    // Update order status to 'completed'
    public function markAsCompleted($id)
    {
        $order = Order::findOrFail($id);
        $order->status = 'completed';
        $order->save();

        return redirect()->route('orders.index')->with('success', 'Order status updated to completed.');
    }
}

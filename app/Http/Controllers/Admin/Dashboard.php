<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Device;
use App\Models\Product;
use App\Models\User;
use App\Models\DeviceType;
use App\Models\CheckoutItem;

class Dashboard extends Controller
{
    public function index(Request $request)
    {
        // Date filters
        $fromDate = $request->input('from_date');
        $toDate = $request->input('to_date');
    
        // Base query for devices
        $devicesQuery = Device::query();
    
        if ($fromDate && $toDate) {
            $devicesQuery->whereBetween('created_at', [$fromDate, $toDate]);
        }
    
        // Clone the query to prevent overwriting conditions
        $totalDevices = (clone $devicesQuery)->count();
        $activeDevices = (clone $devicesQuery)->where('activation', true)->count();
        $inactiveDevices = (clone $devicesQuery)->where('activation', false)->count();
    
        // Purchased products
        $purchasedProductsQuery = CheckoutItem::query();
        if ($fromDate && $toDate) {
            $purchasedProductsQuery->whereBetween('created_at', [$fromDate, $toDate]);
        }
    
        $purchasedProducts = $purchasedProductsQuery->count();
        $totalIncome = $purchasedProductsQuery->sum('price');
    
        // User and device type statistics
        $usersCount = User::where('category', 'user')->count();
        $deviceTypesCount = DeviceType::count();
    
        return view('admin.dashboard', compact(
            'totalDevices', 
            'activeDevices', 
            'inactiveDevices', 
            'purchasedProducts', 
            'totalIncome', 
            'usersCount', 
            'deviceTypesCount',
            'fromDate',
            'toDate'
        ));
    }    
}

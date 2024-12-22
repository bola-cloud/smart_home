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

        // Filter logic for date range
        $devicesQuery = Device::query();
        $purchasedProductsQuery = CheckoutItem::query();
        
        if ($fromDate && $toDate) {
            $devicesQuery->whereBetween('created_at', [$fromDate, $toDate]);
            $purchasedProductsQuery->whereBetween('created_at', [$fromDate, $toDate]);
        }

        // Statistics
        $totalDevices = $devicesQuery->count();
        $activeDevices = $devicesQuery->where('activation', 1)->count();
        $inactiveDevices = $devicesQuery->where('activation', 0)->count();

        $purchasedProducts = $purchasedProductsQuery->count();
        $totalIncome = $purchasedProductsQuery->sum('price');

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

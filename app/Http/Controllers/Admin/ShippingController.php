<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\City;
use App\Models\DistrictLite;

class ShippingController extends Controller
{
    public function showShippingUpdateForm()
    {
        $cities = City::all();
        $districts = DistrictLite::all();

        return view('admin.shipping.shipping-update', compact('cities', 'districts'));
    }

    public function updateShippingValues(Request $request)
    {
        $request->validate([
            'cities.*.id' => 'required|exists:cities,id',
            'cities.*.shipping' => 'required|boolean',
            'districts_lite.*.id' => 'required|exists:districts_lite,id',
            'districts_lite.*.shipping' => 'required|boolean',
        ]);

        // Update Cities
        foreach ($request->cities as $city) {
            $cityModel = City::find($city['id']);
            $cityModel->shipping = $city['shipping'];
            $cityModel->save();
        }

        // Update Districts Lite
        foreach ($request->districts_lite as $district) {
            $districtModel = DistrictLite::find($district['id']);
            $districtModel->shipping = $district['shipping'];
            $districtModel->save();
        }

        return redirect()->back()->with('success', 'Shipping values updated successfully!');
    }
}

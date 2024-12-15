<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\City;
use App\Models\DistrictLite;

class ShippingController extends Controller
{
    // Display the form with Regions, Cities, and Districts
    public function index()
    {
        $regions = RegionLite::all();
        return view('regions-districts.update', compact('regions'));
    }

    // Fetch Cities Based on Selected Region (AJAX)
    public function fetchCities(Request $request)
    {
        $cities = CityLite::where('region_id', $request->region_id)->get();
        return response()->json(['cities' => $cities]);
    }

    // Fetch Districts Based on Selected City (AJAX)
    public function fetchDistricts(Request $request)
    {
        $districts = DistrictLite::where('city_id', $request->city_id)->get();
        return response()->json(['districts' => $districts]);
    }

    // Update Shipping Values for Cities and Districts
    public function update(Request $request)
    {
        $request->validate([
            'region_id' => 'required|exists:regions_lite,region_id',
            'city_id' => 'required|exists:cities_lite,city_id',
            'district_id' => 'required|exists:districts_lite,district_id',
        ]);

        try {
            // Update City Shipping if Exists
            $city = CityLite::findOrFail($request->city_id);
            $city->shipping = $request->input('shipping', 1); // Default to '1' (with shipping)
            $city->save();

            // Update District Shipping if Exists
            $district = DistrictLite::findOrFail($request->district_id);
            $district->shipping = $request->input('shipping', 1); // Default to '1' (with shipping)
            $district->save();

            return redirect()->back()->with('success', __('Shipping values updated successfully!'));
        } catch (\Exception $e) {
            return redirect()->back()->with('error', __('Failed to update shipping values.'));
        }
    }
}

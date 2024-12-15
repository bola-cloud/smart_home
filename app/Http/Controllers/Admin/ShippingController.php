<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\CityLite;
use App\Models\DistrictLite;
use App\Models\RegionLite;

class ShippingController extends Controller
{
    // Display the form with Regions, Cities, and Districts
    public function index()
    {
        $regions = RegionLite::all();
        return view('admin.regions-districts.update', compact('regions'));
    }

    // Fetch Cities Based on Selected Region (AJAX)
    public function fetchCities(Request $request)
    {
        $cities = CityLite::where('region_id', $request->region_id)->get(); // Filter by region_id
        return response()->json(['cities' => $cities]);
    }

    // Fetch Districts Based on Selected City (AJAX)
    public function fetchDistricts(Request $request)
    {
        $districts = DistrictLite::where('city_id', $request->city_id)->get(); // Filter by city_id
        return response()->json(['districts' => $districts]);
    }

    // Update Shipping Values for Selected City and District
    public function update(Request $request)
    {
        $request->validate([
            'region_id' => 'required|exists:regions_lite,region_id',
            'city_id' => 'required|exists:cities_lite,city_id',
            'district_id' => 'required|exists:districts_lite,district_id',
        ]);

        try {
            // Update Shipping in City
            $city = CityLite::findOrFail($request->city_id);
            $city->shipping = $request->input('shipping', 1); // Default to 'with shipping'
            $city->save();

            // Update Shipping in District
            $district = DistrictLite::findOrFail($request->district_id);
            $district->shipping = $request->input('shipping', 1); // Default to 'with shipping'
            $district->save();

            return redirect()->back()->with('success', __('Shipping values updated successfully!'));
        } catch (\Exception $e) {
            return redirect()->back()->with('error', __('Failed to update shipping values.'));
        }
    }
}

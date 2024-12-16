<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\CityLite;
use App\Models\DistrictLite;
use App\Models\RegionLite;

class ShippingController extends Controller
{
    public function index()
    {
        $regions = RegionLite::all();
        return view('admin.regions-districts.update', compact('regions'));
    }

    /**
     * Fetch cities based on region.
     */
    public function fetchCities(Request $request)
    {
        $request->validate([
            'region_id' => 'required|exists:regions_lite,region_id',
        ]);

        $cities = CityLite::where('region_id', $request->region_id)->get();

        return response()->json(['cities' => $cities]);
    }

    /**
     * Fetch districts based on city.
     */
    public function fetchDistricts(Request $request)
    {
        $request->validate([
            'city_id' => 'required|exists:cities_lite,city_id',
        ]);

        $districts = DistrictLite::where('city_id', $request->city_id)->get();

        return response()->json(['districts' => $districts]);
    }

    /**
     * Store a new region.
     */
    public function storeRegion(Request $request)
    {
        $request->validate([
            'name_en' => 'required|string|max:255',
            'name_ar' => 'required|string|max:255',
        ]);

        RegionLite::create([
            'name_en' => $request->name_en,
            'name_ar' => $request->name_ar,
            'capital_city_id' => $request->capital_city_id ?? 0, // Default to 0
        ]);

        return response()->json(['message' => 'Region created successfully!']);
    }

    /**
     * Store a new city.
     */
    public function storeCity(Request $request)
    {
        $request->validate([
            'region_id' => 'required|exists:regions_lite,region_id',
            'name_en' => 'required|string|max:255',
            'name_ar' => 'nullable|string|max:255',
        ]);
    
        $city = CityLite::create([
            'city_id' => $this->generateUniqueCityId(), // Generate unique city_id
            'region_id' => $request->region_id,
            'name_en' => $request->name_en,
            'name_ar' => $request->name_ar ?? '',
        ]);
    
        return response()->json([
            'message' => 'City created successfully!',
            'city' => $city,
        ]);
    }
    
    private function generateUniqueCityId()
    {
        return CityLite::max('city_id') + 1;
    }    

    /**
     * Store a new district.
     */
    public function storeDistrict(Request $request)
    {
        $request->validate([
            'city_id' => 'required|exists:cities_lite,city_id',
            'name_en' => 'required|string|max:255',
            'name_ar' => 'nullable|string|max:255',
        ]);
    
        // Fetch region_id from the city
        $city = CityLite::findOrFail($request->city_id);
    
        $district = DistrictLite::create([
            'district_id' => $this->generateUniqueId(), // Generate a unique ID
            'city_id' => $request->city_id,
            'region_id' => $city->region_id, // Set region_id based on the city
            'name_en' => $request->name_en,
            'name_ar' => $request->name_ar ?? '',
        ]);
    
        return response()->json([
            'message' => 'District created successfully!',
            'district' => $district,
        ]);
    }    
    
    private function generateUniqueId()
    {
        // Example of generating a unique ID
        return DistrictLite::max('district_id') + 1;
    }    
    
    public function updateDistrictShipping(Request $request)
    {
        $request->validate([
            'city_id' => 'required|exists:cities_lite,city_id',
            'shipping' => 'required|in:0,1',
        ]);
    
        $city = CityLite::findOrFail($request->city_id);
    
        // If district is not selected, check for districts
        if (!$request->filled('district_id')) {
            $district = DistrictLite::where('city_id', $city->city_id)->first();
    
            // If no district exists, create one with the city's name
            if (!$district) {
                $district = DistrictLite::create([
                    'district_id' => DistrictLite::max('district_id') + 1,
                    'city_id' => $city->city_id,
                    'region_id' => $city->region_id,
                    'name_en' => $city->name_en,
                    'name_ar' => $city->name_ar,
                    'shipping' => $request->shipping,
                ]);
            }
        } else {
            $district = DistrictLite::findOrFail($request->district_id);
            $district->update(['shipping' => $request->shipping]);
        }
    
        return response()->json(['message' => 'Shipping updated successfully!']);
    }
    
}

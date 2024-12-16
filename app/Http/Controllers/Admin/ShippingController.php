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
        dd($request->all());
        $request->validate([
            'region_id' => 'required|exists:regions_lite,region_id',
            'name_en' => 'required|string|max:255', // Validate name_en (English name)
            'name_ar' => 'nullable|string|max:255', // Optional Arabic name
        ]);
    
        $city = CityLite::create([
            'region_id' => $request->region_id,
            'name_en' => $request->name_en,
            'name_ar' => $request->name_ar, // Default to empty string if not provided
        ]);
    
        return response()->json([
            'message' => 'City created successfully!',
            'city' => $city,
        ]);
    }    

    /**
     * Store a new district.
     */
    public function storeDistrict(Request $request)
    {
        $request->validate([
            'city_id' => 'required|exists:cities_lite,city_id',
            'name_en' => 'required|string|max:255', // Validate name_en (English name)
            'name_ar' => 'nullable|string|max:255', // Optional Arabic name
        ]);
    
        $district = DistrictLite::create([
            'city_id' => $request->city_id,
            'name_en' => $request->name_en,
            'name_ar' => $request->name_ar, // Default to empty string if not provided
        ]);
    
        return response()->json([
            'message' => 'District created successfully!',
            'district' => $district,
        ]);
    }    
}

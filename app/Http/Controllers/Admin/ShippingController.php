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
            'city_name' => 'required|string|max:255',
        ]);

        CityLite::create([
            'region_id' => $request->region_id,
            'name_en' => $request->city_name,
        ]);

        return response()->json(['message' => 'City created successfully!']);
    }

    /**
     * Store a new district.
     */
    public function storeDistrict(Request $request)
    {
        $request->validate([
            'city_id' => 'required|exists:cities_lite,city_id',
            'district_name' => 'required|string|max:255',
        ]);

        DistrictLite::create([
            'city_id' => $request->city_id,
            'name_en' => $request->district_name,
        ]);

        return response()->json(['message' => 'District created successfully!']);
    }
}

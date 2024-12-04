<?php

namespace App\Http\Controllers\Api\Regions;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Governorate;
use App\Models\RegionLite;

class RegionsController extends Controller
{
    public function getGovernments()
    {
        $governments = Governorate::with('cities')->get();

        return response()->json([
            'status' => true,
            'data' => $governments,
        ]);
    }

    public function getRegions()
    {
        // Fetch all regions with their cities and optionally districts
        $regions = RegionLite::with('cities.districts')->get();

        // Return the data in the required format
        return response()->json([
            'status' => true,
            'data' => $regions,
        ]);
    }
}

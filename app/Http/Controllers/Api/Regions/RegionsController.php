<?php

namespace App\Http\Controllers\Api\Regions;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Governorate;

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
}

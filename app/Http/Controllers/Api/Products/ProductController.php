<?php

namespace App\Http\Controllers\Api\Products;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Product;

class ProductController extends Controller
{
    public function index()
    {
        $products = Product::all();

        $products = $products->map(function ($products) {
            // Generate full image URL using asset() helper
            $products->image = asset('storage/' . $products->image);
            return $products;
        });

        return response()->json([
            'success' => true,
            'data' => $products,
        ], 200);
    }
}

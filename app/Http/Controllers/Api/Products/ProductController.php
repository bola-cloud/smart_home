<?php

namespace App\Http\Controllers\Api\Products;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Product;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        // Determine the requested language from the `local` header
        $locale = $request->header('local', 'en'); // Default to 'en' if not provided

        // Fetch all products
        $products = Product::all();

        // Map products based on the requested language
        $products = $products->map(function ($product) use ($locale) {
            $localizedProduct = [
                'id' => $product->id,
                'title' => $locale === 'ar' ? $product->ar_title : $product->en_title,
                'small_description' => $locale === 'ar' ? $product->ar_small_description : $product->en_small_description,
                'description' => $locale === 'ar' ? $product->ar_description : $product->en_description,
                'image' => asset('storage/' . $product->image),
                'price' => $product->price,
            ];

            return $localizedProduct;
        });

        // Return the response as JSON
        return response()->json([
            'success' => true,
            'data' => $products,
        ], 200);
    }
}

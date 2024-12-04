<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function index()
    {
        // Eager load the prices relationship
        $products = Product::with('prices')->get();
        
        return view('admin.products.index', compact('products'));
    }    

    public function create()
    {
        return view('admin.products.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'ar_title' => 'required|string|max:255',
            'en_title' => 'required|string|max:255',
            'ar_small_description' => 'required|string|max:255',
            'en_small_description' => 'required|string|max:255',
            'ar_description' => 'nullable|string',
            'en_description' => 'nullable|string',
            'image' => 'required|image|max:2048',
            'egypt_price' => 'required|integer|min:0',
            'saudi_price' => 'required|integer|min:0',
            'quantity' => 'nullable|integer|min:0',
        ]);
    
        // Store the image in the 'products' folder within the public disk
        $imagePath = $request->file('image')->store('products', 'public');
    
        // Create the product
        $product = Product::create([
            'ar_title' => $request->input('ar_title'),
            'en_title' => $request->input('en_title'),
            'ar_small_description' => $request->input('ar_small_description'),
            'en_small_description' => $request->input('en_small_description'),
            'ar_description' => $request->input('ar_description'),
            'en_description' => $request->input('en_description'),
            'image' => $imagePath,
            'quantity' => $request->input('quantity'),
        ]);
    
        // Create dynamic prices for Egypt and Saudi Arabia
        $product->prices()->create([
            'country' => 'Egypt',
            'price' => $request->input('egypt_price'),
        ]);
    
        $product->prices()->create([
            'country' => 'Saudi',
            'price' => $request->input('saudi_price'),
        ]);
    
        return redirect()->route('products.index')->with('success', 'Product created successfully.');
    }
    

    public function edit(Product $product)
    {
        return view('admin.products.edit', compact('product'));
    }

    public function update(Request $request, Product $product)
    {
        $request->validate([
            'ar_title' => 'required|string|max:255',
            'en_title' => 'required|string|max:255',
            'ar_small_description' => 'required|string|max:255',
            'en_small_description' => 'required|string|max:255',
            'ar_description' => 'nullable|string',
            'en_description' => 'nullable|string',
            'image' => 'nullable|image|max:2048',
            'quantity' => 'nullable|integer|min:0',
            'egypt_price' => 'nullable|integer|min:0',
            'saudi_price' => 'nullable|integer|min:0',
        ]);
    
        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('products', 'public');
            $product->image = $imagePath;
        }
    
        // Update the main product fields
        $product->update([
            'ar_title' => $request->input('ar_title'),
            'en_title' => $request->input('en_title'),
            'ar_small_description' => $request->input('ar_small_description'),
            'en_small_description' => $request->input('en_small_description'),
            'ar_description' => $request->input('ar_description'),
            'en_description' => $request->input('en_description'),
            'quantity' => $request->input('quantity'),
        ]);
    
        // Update prices for Egypt and Saudi
        $egyptPrice = $request->input('egypt_price');
        $saudiPrice = $request->input('saudi_price');
    
        // Find or create CountryPrice entries for Egypt and Saudi
        $product->prices()->updateOrCreate(
            ['country' => 'Egypt'],
            ['price' => $egyptPrice]
        );
    
        $product->prices()->updateOrCreate(
            ['country' => 'Saudi'],
            ['price' => $saudiPrice]
        );
    
        return redirect()->route('products.index')->with('success', 'Product updated successfully.');
    }
    
    public function destroy(Product $product)
    {
        $product->delete();
        return redirect()->route('products.index')->with('success', 'Product deleted successfully.');
    }
}

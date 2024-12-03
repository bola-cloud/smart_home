<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function index()
    {
        $products = Product::all();
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
            'price' => 'nullable|integer|min:0',
            'quantity' => 'nullable|integer|min:0',
        ]);

        $imagePath = $request->file('image')->store('products', 'public');

        Product::create([
            'ar_title' => $request->input('ar_title'),
            'en_title' => $request->input('en_title'),
            'ar_small_description' => $request->input('ar_small_description'),
            'en_small_description' => $request->input('en_small_description'),
            'ar_description' => $request->input('ar_description'),
            'en_description' => $request->input('en_description'),
            'image' => $imagePath,
            'price' => $request->input('price'),
            'quantity' => $request->input('quantity'),
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
            'price' => 'nullable|integer|min:0',
            'quantity' => 'nullable|integer|min:0',
        ]);

        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('products', 'public');
            $product->image = $imagePath;
        }

        $product->update([
            'ar_title' => $request->input('ar_title'),
            'en_title' => $request->input('en_title'),
            'ar_small_description' => $request->input('ar_small_description'),
            'en_small_description' => $request->input('en_small_description'),
            'ar_description' => $request->input('ar_description'),
            'en_description' => $request->input('en_description'),
            'price' => $request->input('price'),
            'quantity' => $request->input('quantity'),
        ]);

        return redirect()->route('products.index')->with('success', 'Product updated successfully.');
    }

    public function destroy(Product $product)
    {
        $product->delete();
        return redirect()->route('products.index')->with('success', 'Product deleted successfully.');
    }
}

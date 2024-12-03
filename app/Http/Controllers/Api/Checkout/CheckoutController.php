<?php

namespace App\Http\Controllers\Api\Checkout;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Checkout;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;  // Keep it only at the top

class CheckoutController extends Controller
{
    public function processCheckout(Request $request)
    {
        // Validate the input
        $validated = $request->validate([
            'items' => 'required|array',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
            'address' => 'required|string|max:255', // Validate the address field
            'contact' => 'nullable|string|max:255', // Validate the mobile contact
        ]);
        
        // If validation fails, it will automatically return a response with errors.
        if (!$validated) {
            return response()->json([
                'error' => 'Validation failed',
                'message' => 'Product ID does not exist or other validation issues',
            ], 400);
        }
    
        // Get the user
        $user = Auth::user();
    
        // Initialize an array to store checkout items
        $checkoutItems = [];
        $totalAmount = 0;
    
        // Loop through the received product IDs and quantities
        foreach ($request->input('items') as $item) {
            // Retrieve the product from the database
            $product = Product::find($item['product_id']);
    
            // Check if the quantity requested is available
            if ($product->quantity < $item['quantity']) {
                return response()->json([
                    'error' => "Insufficient stock for product: {$product->name}",
                ], 400);
            }
    
            // Calculate the total price for this product
            $totalAmount += $product->price * $item['quantity'];
    
            // Add the item to the checkout array
            $checkoutItems[] = [
                'product_id' => $product->id,
                'quantity' => $item['quantity'],
                'price' => $product->price,
            ];
    
            // Optionally, reduce the product stock
            $product->decrement('quantity', $item['quantity']);
        }

        // Generate a unique code for the checkout (numeric only)
        $checkoutCode = $this->generateUniqueCode();

        // Create a checkout record with the address and generated code
        $checkout = Checkout::create([
            'user_id' => $user->id,
            'total_amount' => $totalAmount,
            'address' => $request->input('address'),
            'contact' => $request->input('contact'),
            'status' => 'pending', // or 'in-progress', depending on your flow
            'code' => $checkoutCode,  // Store the generated code
        ]);
    
        // Save the checkout items
        foreach ($checkoutItems as $checkoutItem) {
            $checkout->items()->create($checkoutItem);
        }
    
        return response()->json([
            'status' => true,
            'message' => 'Checkout successful',
            'data' => $checkout,
        ]);
    }

    /**
     * Generate a unique checkout code.
     *
     * @return string
     */
    private function generateUniqueCode()
    {
        do {
            // Generate a new code with 6 random digits
            $code = Str::random(6);  // Generates a random string like "123456"
            
            // Ensure the code is numeric
            $code = preg_replace('/[^0-9]/', '', $code);  // Removes non-numeric characters, if any
    
            // Ensure it's exactly 6 digits long
            if (strlen($code) < 6) {
                $code = str_pad($code, 6, '0', STR_PAD_LEFT); // Pads the code to 6 digits
            }
            
        } while (Checkout::where('code', $code)->exists());  // Check if the code already exists in the DB
        
        return $code;
    }    
}

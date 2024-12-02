<?php
namespace App\Http\Controllers\Api\Cart;

use App\Http\Controllers\Controller;
use Darryldecode\Cart\Facades\CartFacade as Cart;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;  // Make sure to import the session facade

class CartController extends Controller
{
    public function addToCart(Request $request)
    {
        try {
            // Ensure Cart session is tied to authenticated user
            Cart::session(Auth::id());
    
            // Validate request
            $validated = $request->validate([
                'product_id' => 'required|integer',
                'product_name' => 'required|string',
                'product_price' => 'required|numeric',
                'quantity' => 'required|integer|min:1',
            ]);
    
            // Log request data for debugging
            Log::debug('Validated Cart Data:', $validated);
    
            // Check if product already exists in cart
            $existingItem = Cart::get($validated['product_id']);
            if ($existingItem) {
                // Update the quantity or show a message
                Cart::update($existingItem->id, [
                    'quantity' => ['relative' => true, 'value' => $validated['quantity']],
                ]);
                return response()->json([
                    'message' => 'Product quantity updated in cart.',
                    'cart' => Cart::getContent(),
                ]);
            }
    
            // Add new product to cart
            Cart::add([
                'id' => $validated['product_id'],
                'name' => $validated['product_name'],
                'price' => $validated['product_price'],
                'quantity' => $validated['quantity'],
                'attributes' => [],
            ]);
    
            return response()->json([
                'message' => 'Product added to cart successfully.',
                'cart' => Cart::getContent(),
            ]);
        } catch (\Exception $e) {
            // Log error details for debugging
            Log::error('Error adding to cart: ' . $e->getMessage());
            return response()->json([
                'message' => 'An error occurred while adding the product to the cart.',
            ], 500);
        }
    }      

    /**
     * View the cart items.
     */
    public function viewCart()
    {
        // Ensure the cart is unique for the authenticated user
        Cart::session(Auth::id());
    
        // Log the session data correctly as an array
        Log::debug('Current User ID:', ['user_id' => Auth::id()]);  // Correct logging with array context
        Log::debug('Current Session ID:', ['session_id' => session()->getId()]);  // Correct logging with array context
        Log::debug('Cart Content:', ['cart_content' => Cart::getContent()->toArray()]);  // Log the cart content as an array
    
        $cartItems = Cart::getContent();
    
        if ($cartItems->isEmpty()) {
            return response()->json(['message' => 'Your cart is empty.'], 404);
        }
    
        return response()->json([
            'message' => 'Cart items fetched successfully.',
            'cart' => $cartItems
        ]);
    }     

    /**
     * Update the quantity of a product in the cart.
     */
    public function updateCart(Request $request)
    {
        // Ensure the cart is unique for the authenticated user
        Cart::session(Auth::id());

        // Log request data for debugging
        Log::debug('Update Cart Request:', $request->all());

        // Validate request
        $validated = $request->validate([
            'rowId' => 'required|string', // Cart row ID
            'quantity' => 'required|integer|min:1',
        ]);

        // Log validated data
        Log::debug('Validated Data for Update:', $validated);

        // Update product quantity
        Cart::update($validated['rowId'], [
            'quantity' => ['relative' => false, 'value' => $validated['quantity']]
        ]);

        // Log the cart content after update
        Log::debug('Cart Content After Update:', Cart::getContent()->toArray());

        return response()->json([
            'message' => 'Cart updated successfully.',
            'cart' => Cart::getContent()
        ]);
    }

    /**
     * Remove a product from the cart.
     */
    public function removeFromCart($id)
    {
        // Ensure the cart is unique for the authenticated user
        Cart::session(Auth::id());

        // Log the product ID to be removed
        Log::debug('Remove Product from Cart:', ['product_id' => $id]);

        Cart::remove($id);

        // Log the cart content after removal
        Log::debug('Cart Content After Removal:', Cart::getContent()->toArray());

        return response()->json([
            'message' => 'Product removed from cart successfully.',
            'cart' => Cart::getContent()
        ]);
    }

    /**
     * Clear the cart.
     */
    public function clearCart()
    {
        // Ensure the cart is unique for the authenticated user
        Cart::session(Auth::id());

        // Log before clearing the cart
        Log::debug('Clear Cart Request:', ['user_id' => Auth::id()]);

        Cart::clear();

        // Log after clearing the cart
        Log::debug('Cart Content After Clearing:', Cart::getContent()->toArray());

        return response()->json([
            'message' => 'Cart cleared successfully.',
        ]);
    }
}

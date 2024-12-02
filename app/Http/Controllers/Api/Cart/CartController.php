<?php

namespace App\Http\Controllers\Api\Cart;

use App\Http\Controllers\Controller;
use Darryldecode\Cart\Facades\CartFacade as Cart;
use Illuminate\Http\Request;

class CartController extends Controller
{
    public function addToCart(Request $request)
    {
        // Validate request
        $validated = $request->validate([
            'product_id' => 'required|integer',
            'product_name' => 'required|string',
            'product_price' => 'required|numeric',
            'quantity' => 'required|integer|min:1',
        ]);

        // Add product to cart
        Cart::add([
            'id' => $validated['product_id'],
            'name' => $validated['product_name'],
            'price' => $validated['product_price'],
            'quantity' => $validated['quantity'],
            'attributes' => [], // Add any other attributes here if needed
        ]);

        return response()->json([
            'message' => 'Product added to cart successfully.',
            'cart' => Cart::getContent()
        ]);
    }

    /**
     * View the cart items.
     */
    public function viewCart()
    {
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
        // Validate request
        $validated = $request->validate([
            'rowId' => 'required|string', // Cart row ID
            'quantity' => 'required|integer|min:1',
        ]);

        // Update product quantity
        Cart::update($validated['rowId'], [
            'quantity' => ['relative' => false, 'value' => $validated['quantity']]
        ]);

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
        Cart::remove($id);

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
        Cart::clear();

        return response()->json([
            'message' => 'Cart cleared successfully.',
        ]);
    }
}

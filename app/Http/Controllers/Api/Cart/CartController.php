<?php

namespace App\Http\Controllers\Api\Cart;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Darryldecode\Cart\Facades\CartFacade as Cart;
use App\Models\Product;

class CartController extends Controller
{
    // Add product to cart
    public function addToCart(Request $request, $productId)
    {
        // Fetch the product by ID
        $product = Product::find($productId);
        
        // Check if the product exists
        if (!$product) {
            return response()->json([
                'success' => false,
                'message' => 'Product not found.',
            ], 404);
        }

        // Get quantity from the request, default to 1
        $quantity = $request->input('quantity', 1);

        // Add product to cart
        Cart::session(auth()->id())->add([
            'id' => $product->id,
            'name' => $product->en_title, // Assuming English title
            'price' => $product->price,
            'quantity' => $quantity,
            'attributes' => [
                'image' => asset('storage/' . $product->image),
            ],
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Product added to cart.',
            'cart' => Cart::session(auth()->id())->getContent(),
        ], 200);
    }

    // Get cart details
    public function getCart()
    {
        // Get cart content for the authenticated user
        $cartItems = Cart::session(auth()->id())->getContent();

        return response()->json([
            'success' => true,
            'cart' => $cartItems,
        ], 200);
    }

    // Update product quantity in the cart
    public function updateCart(Request $request, $productId)
    {
        // Fetch the product by ID
        $product = Product::find($productId);
        
        if (!$product) {
            return response()->json([
                'success' => false,
                'message' => 'Product not found.',
            ], 404);
        }

        // Get quantity from the request
        $quantity = $request->input('quantity', 1);

        // Update cart item quantity
        Cart::session(auth()->id())->update($productId, [
            'quantity' => $quantity,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Cart updated.',
            'cart' => Cart::session(auth()->id())->getContent(),
        ], 200);
    }

    // Remove product from cart
    public function removeFromCart($productId)
    {
        // Remove product from cart
        Cart::session(auth()->id())->remove($productId);

        return response()->json([
            'success' => true,
            'message' => 'Product removed from cart.',
            'cart' => Cart::session(auth()->id())->getContent(),
        ], 200);
    }

    // Clear the cart
    public function clearCart()
    {
        // Clear cart for the authenticated user
        Cart::session(auth()->id())->clear();

        return response()->json([
            'success' => true,
            'message' => 'Cart cleared.',
        ], 200);
    }

    // Get cart total price
    public function getCartTotal()
    {
        $total = Cart::session(auth()->id())->getTotal();

        return response()->json([
            'success' => true,
            'total' => $total,
        ], 200);
    }
}

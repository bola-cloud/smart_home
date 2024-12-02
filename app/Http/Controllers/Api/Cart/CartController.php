<?php

namespace App\Http\Controllers\Api\Cart;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Darryldecode\Cart\Facades\CartFacade as Cart;
use App\Models\Product;
use Illuminate\Support\Facades\Session;

class CartController extends Controller
{
    public function addToCart(Request $request, $productId)
    {
        $product = Product::find($productId);
        if (!$product) {
            return response()->json(['success' => false, 'message' => 'Product not found.'], 404);
        }

        $quantity = $request->input('quantity', 1);

        Cart::session(auth()->id())->add([
            'id' => $product->id,
            'name' => $product->en_title,
            'price' => $product->price,
            'quantity' => $quantity,
            'attributes' => ['image' => asset('storage/' . $product->image)],
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Product added to cart.',
            'cart' => Cart::session(auth()->id())->getContent(),
        ]);
    }

    public function getCart()
    {
        $cartItems = Cart::session(auth()->id())->getContent();
        return response()->json(['success' => true, 'cart' => $cartItems]);
    }

    public function updateCart(Request $request, $productId)
    {
        $product = Product::find($productId);
        if (!$product) {
            return response()->json(['success' => false, 'message' => 'Product not found.'], 404);
        }

        $quantity = $request->input('quantity', 1);
        Cart::session(auth()->id())->update($productId, ['quantity' => $quantity]);

        return response()->json([
            'success' => true,
            'message' => 'Cart updated.',
            'cart' => Cart::session(auth()->id())->getContent(),
        ]);
    }

    public function removeFromCart($productId)
    {
        Cart::session(auth()->id())->remove($productId);
        return response()->json([
            'success' => true,
            'message' => 'Product removed from cart.',
            'cart' => Cart::session(auth()->id())->getContent(),
        ]);
    }

    public function clearCart()
    {
        Cart::session(auth()->id())->clear();
        return response()->json(['success' => true, 'message' => 'Cart cleared.']);
    }

    public function getCartTotal()
    {
        $total = Cart::session(auth()->id())->getTotal();
        return response()->json(['success' => true, 'total' => $total]);
    }
}

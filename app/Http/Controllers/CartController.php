<?php

namespace App\Http\Controllers;

use App\Models\Cart;
use Illuminate\Http\Request;

class CartController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $cartItems = Cart::where('user_id', $user->id)
            ->with('product')
            ->get();

        return response()->json($cartItems);
    }

    public function store(Request $request)
    {
        $user = $request->user();

        $this->validate($request, [
            'product_id' => 'required|integer|exists:products,id',
            'quantity' => 'nullable|integer|min:1'
        ]);

        $existingCart = Cart::where('user_id', $user->id)
            ->where('product_id', $request->product_id)
            ->first();

        if ($existingCart) {
            $existingCart->quantity += $request->quantity ?? 1;
            $existingCart->save();
            return response()->json($existingCart, 200);
        }

        $cart = Cart::create([
            'user_id' => $user->id,
            'product_id' => $request->product_id,
            'quantity' => $request->quantity ?? 1,
        ]);

        return response()->json($cart, 201);
    }

    public function update(Request $request, $id)
    {
        $user = $request->user();

        $this->validate($request, [
            'quantity' => 'required|integer|min:1'
        ]);

        $cart = Cart::where('id', $id)
            ->where('user_id', $user->id)
            ->firstOrFail();

        $cart->update(['quantity' => $request->quantity]);

        return response()->json($cart);
    }

    public function destroy(Request $request, $id)
    {
        $user = $request->user();

        $cart = Cart::where('id', $id)
            ->where('user_id', $user->id)
            ->firstOrFail();

        $cart->delete();

        return response()->json(['message' => 'Item removed successfully']);
    }
}

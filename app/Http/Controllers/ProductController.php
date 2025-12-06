<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user(); // user login

        $products = Product::with('category')->get()->map(function ($product) use ($user) {

            // tambahkan is_favorite ke setiap produk
            $product->is_favorite = $user
                ? $user->favorites()->where('product_id', $product->id)->exists()
                : false;

            return $product;
        });

        return response()->json($products);
    }

    public function store(Request $request)
    {
        $product = Product::create($request->all());
        return response()->json($product, 201);
    }

    public function show(Request $request, $id)
    {
        $user = $request->user();
        $product = Product::with('category')->findOrFail($id);

        // is_favorite juga di detail
        $product->is_favorite = $user
            ? $user->favorites()->where('product_id', $product->id)->exists()
            : false;

        return response()->json($product);
    }

    public function update(Request $request, $id)
    {
        $product = Product::findOrFail($id);
        $product->update($request->all());
        return response()->json($product);
    }

    public function destroy($id)
    {
        Product::findOrFail($id)->delete();
        return response()->json(null, 204);
    }
}

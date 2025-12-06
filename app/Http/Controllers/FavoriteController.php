<?php
namespace App\Http\Controllers;

use App\Models\Favorite;
use Illuminate\Http\Request;

class FavoriteController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $favorites = Favorite::where('user_id', $user->id)
            ->with('product')
            ->get();

        return response()->json($favorites);
    }

    public function store(Request $request)
    {
        $user = $request->user();

        $validated = $this->validate($request, [
            'product_id' => 'required|integer|exists:products,id'
        ]);

        // Hindari duplikat
        $existing = Favorite::where('user_id', $user->id)
            ->where('product_id', $validated['product_id'])
            ->first();

        if ($existing) {
            return response()->json(['message' => 'Already in favorites'], 409);
        }

        $fav = Favorite::create([
            'user_id' => $user->id,
            'product_id' => $validated['product_id']
        ]);

        return response()->json($fav, 201);
    }

    // Hapus favorit
    public function destroy(Request $request, $productId)
{
    $user = $request->user();

    $fav = Favorite::where('user_id', $user->id)
        ->where('product_id', $productId)
        ->first();

    if (!$fav) {
        return response()->json(['message' => 'Not found'], 404);
    }

    $fav->delete();

    return response()->json(['message' => 'Removed']);
}

}

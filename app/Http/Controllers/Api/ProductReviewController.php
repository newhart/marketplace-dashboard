<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Review;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ProductReviewController extends Controller
{
    /**
     * List reviews for a product
     */
    public function index(Product $product)
    {
        $reviews = $product->reviews()
            ->with('user:id,name')
            ->latest()
            ->paginate(10);

        return response()->json([
            'success' => true,
            'data' => $reviews,
        ]);
    }

    /**
     * Store a new review for a product (customer only)
     */
    public function store(Request $request, Product $product)
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json(['error' => 'Utilisateur non authentifié'], 401);
        }

        $validated = $request->validate([
            'rating'  => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string|max:1000',
        ]);

        // Optionnel : empêcher plusieurs avis du même client sur le même produit
        $existing = Review::where('user_id', $user->id)
            ->where('product_id', $product->id)
            ->first();

        if ($existing) {
            $existing->update($validated);
            $review = $existing->fresh('user');
        } else {
            $review = Review::create([
                'user_id'    => $user->id,
                'product_id' => $product->id,
                'rating'     => $validated['rating'],
                'comment'    => $validated['comment'] ?? null,
            ]);
            $review->load('user');
        }

        return response()->json([
            'success' => true,
            'message' => 'Avis enregistré avec succès',
            'data'    => $review,
        ], 201);
    }
}



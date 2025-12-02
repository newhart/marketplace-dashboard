<?php

namespace App\Http\Controllers\Merchant;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use App\Http\Resources\ProductCollection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class ProductController extends Controller
{
    /**
     * Display a listing of the merchant's products
     */
    public function index(Request $request)
    {
        $perPage = (int) $request->get('per_page', 15);
        $perPage = max(1, min($perPage, 50));

        $products = Product::where('user_id', Auth::id())
            ->with(['category', 'images'])
            ->latest()
            ->paginate($perPage);

        return new ProductCollection($products);
    }

    /**
     * Show the form for creating a new product
     */
    public function create()
    {
        $categories = Category::all();
        
        return response()->json([
            'categories' => $categories
        ]);
    }

    /**
     * Store a newly created product in storage
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'price_promo' => 'nullable|numeric|min:0',
            'category_id' => 'required|exists:categories,id',
            'origin' => 'nullable|string|max:255',
            'unity' => 'required|string',
            'stock' => 'nullable|integer|min:0',
            'image' => 'nullable|image|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        // Create product
        $product = Product::create([
            'name' => $request->name,
            'description' => $request->description,
            'price' => $request->price,
            'price_promo' => $request->price_promo,
            'category_id' => $request->category_id,
            'user_id' => Auth::id(),
            'stock' => $request->stock ?? 0,
            'origin' => $request->origin,
            'unity' => $request->unity,
        ]);

        // Handle image upload
        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('products', 'public');
            // Create image record
            $product->images()->create([
                'path' => $path,
                'is_main' => true
            ]);
        }

        return response()->json([
            'message' => 'Produit créé avec succès',
            'product' => $product->load('images', 'category')
        ], 201);
    }

    /**
     * Display the specified product
     */
    public function show($id)
    {
        $product = Product::where('user_id', Auth::id())
            ->where('id', $id)
            ->with(['category', 'images'])
            ->firstOrFail();
            
        return response()->json($product);
    }

    /**
     * Show the form for editing the specified product
     */
    public function edit($id)
    {
        $product = Product::where('user_id', Auth::id())
            ->where('id', $id)
            ->with(['category', 'images'])
            ->firstOrFail();
            
        $categories = Category::all();
        
        return response()->json([
            'product' => $product,
            'categories' => $categories
        ]);
    }

    /**
     * Update the specified product in storage
     */
    public function update(Request $request, $id)
    {
        $product = Product::where('user_id', Auth::id())
            ->where('id', $id)
            ->firstOrFail();
            
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'price_promo' => 'nullable|numeric|min:0',
            'category_id' => 'required|exists:categories,id',
            'origin' => 'nullable|string|max:255',
            'unity' => 'required|string',
            'stock' => 'nullable|integer|min:0',
            'image' => 'nullable|image|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        // Update product
        $product->update([
            'name' => $request->name,
            'description' => $request->description,
            'price' => $request->price,
            'price_promo' => $request->price_promo,
            'category_id' => $request->category_id,
            'stock' => $request->stock ?? 0,
            'origin' => $request->origin,
            'unity' => $request->unity,
        ]);

        // Handle image upload
        if ($request->hasFile('image')) {
            // Delete existing main image if exists
            $mainImage = $product->images()->where('is_main', true)->first();
            if ($mainImage) {
                Storage::disk('public')->delete($mainImage->path);
                $mainImage->delete();
            }
            
            // Upload new image
            $path = $request->file('image')->store('products', 'public');
            
            // Create new image record
            $product->images()->create([
                'path' => $path,
                'is_main' => true
            ]);
        }

        return response()->json([
            'message' => 'Produit mis à jour avec succès',
            'product' => $product->load('images', 'category')
        ]);
    }

    /**
     * Remove the specified product from storage
     */
    public function destroy($id)
    {
        $product = Product::where('user_id', Auth::id())
            ->where('id', $id)
            ->firstOrFail();
            
        // Delete product images from storage
        foreach ($product->images as $image) {
            Storage::disk('public')->delete($image->path);
        }
        
        // Delete the product
        $product->delete();
        
        return response()->json([
            'message' => 'Produit supprimé avec succès'
        ]);
    }
}

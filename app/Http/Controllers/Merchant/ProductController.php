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

        $query = Product::where('user_id', Auth::id())
            ->with(['category', 'images'])
            ->latest();

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%");
            });
        }

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        $products = $query->paginate($perPage);

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
            'barcode' => 'nullable|string|max:255',
            'images' => 'nullable|array|max:5', // Tableau de max 5 images
            'images.*' => 'image|max:2048', // Chaque image: max 2MB
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
            'barcode' => $request->barcode,
        ]);

        // Handle multiple images upload
        if ($request->hasFile('images')) {
            $isMain = true;
            foreach ($request->file('images') as $image) {
                $path = $image->store('products', 'public');
                // Create image record
                $product->images()->create([
                    'path' => $path,
                    'is_main' => $isMain
                ]);
                $isMain = false; // Seulement la première est main
            }
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
            'barcode' => 'nullable|string|max:255',
            'images' => 'nullable|array|max:5', // Tableau de max 5 images
            'images.*' => 'image|max:2048', // Chaque image: max 2MB
            'images_to_delete' => 'nullable|array', // IDs des images à supprimer
            'images_to_delete.*' => 'integer|exists:images,id', // Chaque ID doit exister
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
            'barcode' => $request->barcode,
        ]);

        // Charger les images existantes
        $existingImagesCount = $product->images()->count();
        $newImagesCount = $request->hasFile('images') ? count($request->file('images')) : 0;
        $imagesToDeleteCount = $request->has('images_to_delete') ? count($request->images_to_delete) : 0;
        $totalImagesAfterUpdate = $existingImagesCount - $imagesToDeleteCount + $newImagesCount;

        // Vérifier que le total ne dépasse pas 5 images
        if ($totalImagesAfterUpdate > 5) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => [
                    'images' => ['Le nombre total d\'images ne peut pas dépasser 5. Actuellement: ' . $existingImagesCount . ', nouvelles: ' . $newImagesCount . ', à supprimer: ' . $imagesToDeleteCount . ', total après mise à jour: ' . $totalImagesAfterUpdate]
                ]
            ], 422);
        }

        // Supprimer les images demandées
        if ($request->has('images_to_delete') && is_array($request->images_to_delete)) {
            foreach ($request->images_to_delete as $imageId) {
                $image = $product->images()->find($imageId);
                if ($image) {
                    // Supprimer le fichier du stockage
                    if ($image->path && Storage::disk('public')->exists($image->path)) {
                        Storage::disk('public')->delete($image->path);
                    }
                    // Supprimer l'enregistrement
                    $image->delete();
                }
            }

            // Si l'image principale a été supprimée, définir la première image restante comme principale
            $hasMainImage = $product->images()->where('is_main', true)->exists();
            if (!$hasMainImage) {
                $firstImage = $product->images()->orderBy('created_at', 'asc')->first();
                if ($firstImage) {
                    $firstImage->update(['is_main' => true]);
                }
            }
        }

        // Ajouter les nouvelles images
        if ($request->hasFile('images')) {
            // Vérifier s'il y a déjà une image principale
            $hasMainImage = $product->images()->where('is_main', true)->exists();
            $isMain = !$hasMainImage; // La première nouvelle image sera principale si aucune n'existe

            foreach ($request->file('images') as $image) {
                $path = $image->store('products', 'public');
                
                // Créer l'enregistrement de l'image
                $product->images()->create([
                    'path' => $path,
                    'is_main' => $isMain
                ]);
                
                $isMain = false; // Seulement la première nouvelle image peut être principale
            }
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
        // if ($product->images->isNotEmpty()) {
        //     foreach ($product->images as $image) {
        //         if ($image->path && Storage::disk('public')->exists($image->path)) {
        //             Storage::disk('public')->delete($image->path);
        //         }
        //     }
        // }

        // Delete the product
        $product->delete();

        return response()->json([
            'message' => 'Produit supprimé avec succès'
        ]);
    }
}

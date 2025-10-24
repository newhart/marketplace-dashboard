<?php

namespace App\Services;

use App\Http\Resources\ProductCollection;
use App\Http\Resources\ProductResource;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Request;

class ProductService
{
    public function show(Product $product, Request $request = null) : array
    {
        // Charger les relations nécessaires
        $product->load(['category', 'images', 'reviews']);
        
        // Déterminer le type de produits similaires à inclure
        $similarType = $request?->get('similar_type', 'category'); // category, related, price
        $similarLimit = min($request?->get('similar_limit', 6), 12); // Maximum 12 produits
        
        // Récupérer les produits similaires selon le type demandé
        $similarProducts = match($similarType) {
            'related' => $product->getRelatedProducts($similarLimit),
            'price' => $product->getRecommendedByPriceRange($similarLimit),
            default => $product->getSimilarProducts($similarLimit)
        };
        
        return [
            'product' => new ProductResource($product),
            'similar_products' => ProductResource::collection($similarProducts),
            'similar_type' => $similarType,
            'similar_count' => $similarProducts->count()
        ];
    }

    public function suggestProduct() : array
    {
        $products = Product::with(['category'])
        ->limit(5)
        ->get(); 

        return [
            'data' => ProductResource::collection($products)
        ] ; 
    }

    public function index(Request $request) : ProductCollection
    {
        $products =  Product::with(['category'])
        ->latest()
        ->paginate(10); 

        return new ProductCollection($products); 
    }

    public function getByCategory(Category $category) : ProductCollection
    {
        $products = Product::where('category_id' , $category->id)
        ->with(['category'])
        ->latest()
        ->paginate(10); 

        return new ProductCollection($products); 
    }
    public function searchProduct(Request $request) : ProductCollection{
        $products = Product::where('name', 'like', '%' . $request->keyWord . '%')
        ->orWhere('description', 'like', '%' . $request->keyWord . '%')
        ->with(['category'])
        ->latest()
        ->paginate(10); 

        return new ProductCollection($products);
    }
}

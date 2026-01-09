<?php

namespace App\Services;

use App\Http\Resources\ProductCollection;
use App\Http\Resources\ProductResource;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Request;

class ProductService
{
    public function show(Product $product, Request $request = null): array
    {
        // Charger les relations nécessaires avec limite de 5 images maximum
        $product->load([
            'category',
            'images' => function ($query) {
                $query->limit(5)->orderBy('is_main', 'desc')->orderBy('created_at', 'asc');
            },
            'reviews'
        ]);

        // Déterminer le type de produits similaires à inclure
        $similarType = $request?->get('similar_type', 'category'); // category, related, price
        $similarLimit = min($request?->get('similar_limit', 6), 12); // Maximum 12 produits

        // Récupérer les produits similaires selon le type demandé
        $similarProducts = match ($similarType) {
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

    public function suggestProduct(): array
    {
        $products = Product::with(['category'])
            ->limit(5)
            ->get();

        return [
            'data' => ProductResource::collection($products)
        ];
    }

    public function index(Request $request): ProductCollection
    {
        $products =  Product::with(['category', 'images'])
            ->latest()
            ->paginate(10);

        return new ProductCollection($products);
    }

    public function getByCategory(Category $category): ProductCollection
    {
        $products = Product::where('category_id', $category->id)
            ->with(['category'])
            ->latest()
            ->paginate(10);

        return new ProductCollection($products);
    }
    public function searchProduct(Request $request): ProductCollection
    {
        $keyword = $request->keyWord;

        $products = Product::query()
            ->where(function ($q) use ($keyword) {
                $q->where('name', 'like', "%{$keyword}%")
                    ->orWhereHas('category', function ($query) use ($keyword) {
                        $query->where('name', 'like', "%{$keyword}%");
                    });
            })
            ->with(['category', 'images'])
            // 1. Prioriser les catégories qui matchent
            ->orderByRaw("
            CASE 
                WHEN EXISTS (
                    SELECT 1 FROM categories 
                    WHERE categories.id = products.category_id 
                    AND categories.name LIKE ?
                ) THEN 0
                ELSE 1
            END
        ", ["%{$keyword}%"])
            // 2. Ensuite prioriser le nom du produit qui match
            ->orderByRaw("
            CASE 
                WHEN products.name LIKE ? THEN 0
                ELSE 1
            END
        ", ["%{$keyword}%"])
            // 3. Enfin trier par date de création
            ->latest()
            ->paginate(10);

        return new ProductCollection($products);
    }
}

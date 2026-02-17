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
        $product->load([
            'category',
            'images' => function ($query) {
                $query->limit(5)->orderBy('is_main', 'desc')->orderBy('created_at', 'asc');
            },
            'reviews'
        ]);

        $similarType = $request?->get('similar_type', 'category'); 
        $similarLimit = min($request?->get('similar_limit', 6), 12); 

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
    /**
     * Recherche de produits avec score de pertinence.
     * Champs recherchés : name, short_description, description, nom de catégorie.
     * Ordre : nom exact > nom commence par > nom contient > catégorie > description, puis par date.
     */
    public function searchProduct(Request $request): ProductCollection
    {
        $keyword = $request->input('keyWord', $request->input('keyword', ''));
        $keyword = trim(preg_replace('/\s+/', ' ', (string) $keyword));

        if ($keyword === '') {
            $products = Product::with(['category', 'images'])->latest()->paginate(10);
            return new ProductCollection($products);
        }

        $term = '%' . $keyword . '%';
        $termStart = $keyword . '%';

        $products = Product::query()
            ->where(function ($q) use ($term, $termStart) {
                $q->where('name', 'like', $term)
                    ->orWhere('short_description', 'like', $term)
                    ->orWhere('description', 'like', $term)
                    ->orWhereHas('category', function ($query) use ($term) {
                        $query->where('name', 'like', $term);
                    });
            })
            ->with(['category', 'images'])
            ->orderByRaw("
                CASE
                    WHEN products.name = ? THEN 0
                    WHEN products.name LIKE ? THEN 1
                    WHEN products.name LIKE ? THEN 2
                    WHEN EXISTS (
                        SELECT 1 FROM categories
                        WHERE categories.id = products.category_id AND categories.name LIKE ?
                    ) THEN 3
                    WHEN products.short_description LIKE ? OR products.description LIKE ? THEN 4
                    ELSE 5
                END
            ", [$keyword, $termStart, $term, $term, $term, $term])
            ->latest()
            ->paginate(10);

        return new ProductCollection($products);
    }
}

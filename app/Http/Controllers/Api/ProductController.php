<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProductResource;
use App\Models\Category;
use App\Models\Product;
use App\Services\ProductService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProductController extends Controller
{
    public function __construct(public ProductService $productService) {}
    public function show(Product $product, Request $request)
    {
        return response()->json($this->productService->show($product, $request));
    }

    public function suggestProduct(): JsonResponse
    {
        return response()->json($this->productService->suggestProduct());
    }

    public function index(Request $request)
    {
        return response()->json($this->productService->index($request));
    }

    public function getByCategory(Category $category)
    {
        return response()->json($this->productService->getByCategory($category));
    }
    public function searchProduct(Request $request)
    {
        return response()->json($this->productService->searchProduct($request));
    }

    /**
     * Get similar products for a specific product
     */
    public function getSimilarProducts(Product $product, Request $request): JsonResponse
    {
        $request->validate([
            'type' => 'sometimes|in:category,related,price',
            'limit' => 'sometimes|integer|min:1|max:12'
        ]);

        $type = $request->get('type', 'category');
        $limit = $request->get('limit', 6);

        $similarProducts = match($type) {
            'related' => $product->getRelatedProducts($limit),
            'price' => $product->getRecommendedByPriceRange($limit),
            default => $product->getSimilarProducts($limit)
        };

        return response()->json([
            'product_id' => $product->id,
            'similar_type' => $type,
            'similar_products' => ProductResource::collection($similarProducts),
            'count' => $similarProducts->count()
        ]);
    }

    /**
     * Get the boutique associated with a product
     *
     * @param Product $product
     * @return JsonResponse
     */
    public function getBoutique(Product $product): JsonResponse
    {
        try {
            // Charger les relations nécessaires
            $product->load('user.merchant.boutiques');

            // Vérifier si le produit a un utilisateur
            if (!$product->user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Ce produit n\'a pas de vendeur associé.'
                ], 404);
            }

            // Vérifier si l'utilisateur est un marchand
            if (!$product->user->merchant) {
                return response()->json([
                    'success' => false,
                    'message' => 'Le vendeur de ce produit n\'est pas un marchand.'
                ], 404);
            }

            // Récupérer les boutiques du marchand
            $boutiques = $product->user->merchant->boutiques;

            if ($boutiques->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Aucune boutique trouvée pour ce marchand.'
                ], 404);
            }

            // Retourner la première boutique active, ou la première si aucune active
            $boutique = $boutiques->where('is_active', true)->first() ?? $boutiques->first();

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $boutique->id,
                    'name' => $boutique->name,
                    'description' => $boutique->description,
                    'category' => $boutique->category,
                    'photo' => $boutique->photo ? asset('storage/' . $boutique->photo) : null,
                    'latitude' => $boutique->latitude,
                    'longitude' => $boutique->longitude,
                    'city' => $boutique->city,
                    'postal_code' => $boutique->postal_code,
                    'postal_box' => $boutique->postal_box,
                    'opening_hours' => $boutique->opening_hours,
                    'is_active' => $boutique->is_active,
                    'merchant' => [
                        'id' => $product->user->merchant->id,
                        'business_name' => $product->user->name,
                        'business_address' => $product->user->merchant->business_address,
                        'business_city' => $product->user->merchant->business_city,
                        'business_postal_code' => $product->user->merchant->business_postal_code,
                    ],
                ],
                'meta' => [
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'total_boutiques' => $boutiques->count(),
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération de la boutique',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function store(Request $request): JsonResponse
    {
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'required|string',
            'unity' => 'required|string',
            'price' => 'required|numeric|min:0',
            'category_id' => 'required|exists:categories,id',
            'images' => 'nullable|array',
            'images.*' => 'required|string' 
        ]);

        $product = Product::create($validatedData);

        if (isset($validatedData['images'])) {
            foreach ($validatedData['images'] as $base64Image) {
                $this->storeBase64Image($product, $base64Image);
            }
        }

        return response()->json([
            'message' => 'Product created successfully',
            'product' => $product->load('images')
        ], 201);
    }

    private function storeBase64Image(Product $product, string $base64Image): void
    {
        $imageData = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $base64Image));
        $fileName = uniqid() . '.png';
        $path = 'products/' . $fileName;
        Storage::disk('public')->put($path, $imageData);
        $product->images()->create([
            'path' => $path
        ]);
    }

    public function update(Request $request, Product $product): JsonResponse
    {
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'required|string',
            'price' => 'required|numeric|min:0',
            'category_id' => 'required|exists:categories,id',
            'stock' => 'required|integer|min:0',
            'status' => 'required|in:draft,published'
        ]);
        $product->update($validatedData);

        return response()->json([
            'message' => 'Product updated successfully',
            'product' => $product
        ]);
    }

    public function destroy(Product $product): JsonResponse
    {
        $product->delete();

        return response()->json([
            'message' => 'Product deleted successfully'
        ]);
    }
}

<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use App\Services\ProductService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProductController extends Controller
{
    public function __construct(public ProductService $productService) {}
    public function show(Product $product)
    {
        return  response()->json($this->productService->show($product));
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

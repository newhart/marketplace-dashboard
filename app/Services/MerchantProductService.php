<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class MerchantProductService
{
    /**
     * Get all products for the authenticated merchant
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getAllProducts()
    {
        return Product::where('user_id', Auth::id())
            ->with(['category', 'images'])
            ->latest()
            ->get();
    }

    /**
     * Get all categories for product creation/editing
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getCategories()
    {
        return Category::all();
    }

    /**
     * Validate product data
     *
     * @param Request $request
     * @return array
     * @throws ValidationException
     */
    public function validateProductData(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'price_promo' => 'nullable|numeric|min:0',
            'category_id' => 'required|exists:categories,id',
            'origin' => 'nullable|string|max:255',
            'unit' => 'required|string',
            'stock' => 'nullable|integer|min:0',
            'photo' => 'nullable|image|max:2048',
        ]);

        if ($validator->fails()) {
            throw ValidationException::withMessages($validator->errors()->toArray());
        }

        return $validator->validated();
    }

    /**
     * Create a new product for the merchant
     *
     * @param Request $request
     * @return Product
     */
    public function createProduct(Request $request)
    {
        $validatedData = $this->validateProductData($request);
        
        // Create product
        $product = Product::create([
            'name' => $validatedData['name'],
            'description' => $validatedData['description'] ?? null,
            'price' => $validatedData['price'],
            'price_promo' => $validatedData['price_promo'] ?? null,
            'category_id' => $validatedData['category_id'],
            'user_id' => Auth::id(),
            'stock' => $validatedData['stock'] ?? 0,
            'origin' => $validatedData['origin'] ?? null,
            'unit' => $validatedData['unit'],
        ]);

        // Handle image upload
        $this->handleProductImage($request, $product);

        return $product->load('images', 'category');
    }

    /**
     * Get a specific product by ID for the authenticated merchant
     *
     * @param int $id
     * @return Product
     */
    public function getProduct($id)
    {
        return Product::where('user_id', Auth::id())
            ->where('id', $id)
            ->with(['category', 'images'])
            ->firstOrFail();
    }

    /**
     * Update an existing product
     *
     * @param Request $request
     * @param int $id
     * @return Product
     */
    public function updateProduct(Request $request, $id)
    {
        $product = $this->getProduct($id);
        $validatedData = $this->validateProductData($request);
        
        // Update product
        $product->update([
            'name' => $validatedData['name'],
            'description' => $validatedData['description'] ?? null,
            'price' => $validatedData['price'],
            'price_promo' => $validatedData['price_promo'] ?? null,
            'category_id' => $validatedData['category_id'],
            'stock' => $validatedData['stock'] ?? 0,
            'origin' => $validatedData['origin'] ?? null,
            'unit' => $validatedData['unit'],
        ]);

        // Handle image upload
        $this->handleProductImage($request, $product);

        return $product->load('images', 'category');
    }

    /**
     * Delete a product
     *
     * @param int $id
     * @return bool
     */
    public function deleteProduct($id)
    {
        $product = $this->getProduct($id);
        
        // Delete product images from storage
        foreach ($product->images as $image) {
            Storage::disk('public')->delete($image->path);
        }
        
        // Delete the product
        return $product->delete();
    }

    /**
     * Handle product image upload
     *
     * @param Request $request
     * @param Product $product
     * @return void
     */
    private function handleProductImage(Request $request, Product $product)
    {
        if ($request->hasFile('photo')) {
            // Delete existing main image if exists when updating
            $mainImage = $product->images()->where('is_main', true)->first();
            if ($mainImage) {
                Storage::disk('public')->delete($mainImage->path);
                $mainImage->delete();
            }
            
            // Upload new image
            $path = $request->file('photo')->store('products', 'public');
            
            // Create image record
            $product->images()->create([
                'path' => $path,
                'is_main' => true
            ]);
        }
    }

    /**
     * Get merchant dashboard statistics
     *
     * @return array
     */
    public function getDashboardStats()
    {
        $products = $this->getAllProducts();
        
        return [
            'total_products' => $products->count(),
            'pending_orders' => 0, // To be implemented
            'completed_orders' => 0, // To be implemented
            'total_revenue' => 0, // To be implemented
        ];
    }
}

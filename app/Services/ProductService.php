<?php

namespace App\Services;

use App\Http\Resources\ProductCollection;
use App\Http\Resources\ProductResource;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Request;

class ProductService
{
    public function show(Product $product) : ProductResource
    {
        return new ProductResource($product->load(['category'])); 
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
}

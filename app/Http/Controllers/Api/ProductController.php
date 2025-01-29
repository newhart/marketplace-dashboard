<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use App\Services\ProductService;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function __construct(public ProductService $productService)
    {
        
    }
    public function show(Product $product)
    {
        return  response()->json($this->productService->show($product));
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
}

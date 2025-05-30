<?php

namespace App\Http\Controllers\Merchant;

use App\Http\Controllers\Controller;
use App\Models\Merchant;
use App\Services\MerchantProductService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class DashboardController extends Controller
{
    protected $productService;

    /**
     * Constructor to inject dependencies
     */
    public function __construct(MerchantProductService $productService)
    {
        $this->productService = $productService;
    }

    /**
     * Display merchant dashboard
     */
    public function index()
    {
        $merchant = Auth::user()->merchant;
        $products = $this->productService->getAllProducts();
        
        return response()->json([
            'merchant' => $merchant,
            'products' => $products,
            'stats' => $this->productService->getDashboardStats()
        ]);
    }

    /**
     * Get all products for the merchant
     */
    public function products()
    {
        $products = $this->productService->getAllProducts();
        return response()->json($products);
    }

    /**
     * Show form for creating a new product
     */
    public function createProduct()
    {
        $categories = $this->productService->getCategories();
        
        return response()->json([
            'categories' => $categories
        ]);
    }

    /**
     * Store a newly created product
     */
    public function storeProduct(Request $request)
    {
        try {
            $product = $this->productService->createProduct($request);
            
            return response()->json([
                'message' => 'Produit créé avec succès',
                'product' => $product
            ], 201);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        }
    }

    /**
     * Show the form for editing the specified product
     */
    public function editProduct($id)
    {
        $product = $this->productService->getProduct($id);
        $categories = $this->productService->getCategories();
        
        return response()->json([
            'product' => $product,
            'categories' => $categories
        ]);
    }

    /**
     * Update the specified product
     */
    public function updateProduct(Request $request, $id)
    {
        try {
            $product = $this->productService->updateProduct($request, $id);
            
            return response()->json([
                'message' => 'Produit mis à jour avec succès',
                'product' => $product
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        }
    }

    /**
     * Remove the specified product
     */
    public function destroyProduct($id)
    {
        $this->productService->deleteProduct($id);
        
        return response()->json([
            'message' => 'Produit supprimé avec succès'
        ]);
    }
}

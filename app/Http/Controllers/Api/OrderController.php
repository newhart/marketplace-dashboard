<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\InvoiceService;
use App\Services\OrderService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class OrderController extends Controller
{
    protected $orderService;
    protected $invoiceService;

    /**
     * Constructor
     *
     * @param OrderService $orderService
     * @param InvoiceService $invoiceService
     */
    public function __construct(OrderService $orderService, InvoiceService $invoiceService)
    {
        $this->orderService = $orderService;
        $this->invoiceService = $invoiceService;
    }

    /**
     * Store a new order
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        // Validate the request
        $validator = Validator::make($request->all(), [
            'items' => 'required|array',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
            'total_amount' => 'required|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            // Get the authenticated user
            $user = Auth::user();
            
            if (!$user) {
                return response()->json(['error' => 'Utilisateur non authentifié'], 401);
            }

            // Create the order
            $order = $this->orderService->store($request->all(), $user);

            return response()->json([
                'success' => true,
                'message' => 'Commande créée avec succès',
                'data' => [
                    'order_id' => $order->id,
                    'status' => $order->status,
                    'total_amount' => $order->total_amount,
                ]
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la création de la commande',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Get orders for the authenticated customer
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function customerOrders()
    {
        try {
            $user = Auth::user();
            
            if (!$user) {
                return response()->json(['error' => 'Utilisateur non authentifié'], 401);
            }
            
            $orders = $user->orders()->with('items.product')->latest()->get();
            
            return response()->json([
                'success' => true,
                'data' => $orders
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des commandes',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Get order details for a customer
     *
     * @param int $orderId
     * @return \Illuminate\Http\JsonResponse
     */
    public function customerOrderDetail($orderId)
    {
        try {
            $user = Auth::user();
            
            if (!$user) {
                return response()->json(['error' => 'Utilisateur non authentifié'], 401);
            }
            
            $order = $user->orders()->with(['items.product', 'user'])->findOrFail($orderId);
            
            return response()->json([
                'success' => true,
                'data' => $order
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des détails de la commande',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Get orders for merchant products
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function merchantOrders()
    {
        try {
            $user = Auth::user();
            
            if (!$user || !$user->isMerchant()) {
                return response()->json(['error' => 'Accès non autorisé'], 403);
            }
            
            // Get all orders that contain products owned by this merchant
            $orders = $this->orderService->getMerchantOrders($user);
            
            return response()->json([
                'success' => true,
                'data' => $orders
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des commandes',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Get order details for a merchant
     *
     * @param int $orderId
     * @return \Illuminate\Http\JsonResponse
     */
    public function merchantOrderDetail($orderId)
    {
        try {
            $user = Auth::user();
            
            if (!$user || !$user->isMerchant()) {
                return response()->json(['error' => 'Accès non autorisé'], 403);
            }
            
            // Get order details with only the items related to this merchant's products
            $orderDetails = $this->orderService->getMerchantOrderDetail($orderId, $user);
            
            if (!$orderDetails) {
                return response()->json(['error' => 'Commande non trouvée ou ne contient pas vos produits'], 404);
            }
            
            return response()->json([
                'success' => true,
                'data' => $orderDetails
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des détails de la commande',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Cancel an order (merchant only)
     *
     * @param int $orderId
     * @return \Illuminate\Http\JsonResponse
     */
    public function cancelOrder($orderId)
    {
        try {
            $user = Auth::user();
            
            if (!$user || !$user->isMerchant()) {
                return response()->json(['error' => 'Accès non autorisé'], 403);
            }
            
            $result = $this->orderService->cancelMerchantOrder($orderId, $user);
            
            if (!$result) {
                return response()->json(['error' => 'Commande non trouvée ou ne contient pas vos produits'], 404);
            }
            
            return response()->json([
                'success' => true,
                'message' => 'Commande annulée avec succès'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'annulation de la commande',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Generate invoice for an order
     *
     * @param int $orderId
     * @return \Illuminate\Http\JsonResponse
     */
    public function generateInvoice($orderId)
    {
        try {
            $user = Auth::user();
            
            if (!$user) {
                return response()->json(['error' => 'Utilisateur non authentifié'], 401);
            }
            
            // Check if user is customer or merchant and has access to this order
            if ($user->isMerchant()) {
                $orderAccess = $this->orderService->merchantHasAccessToOrder($orderId, $user);
            } else {
                $orderAccess = $user->orders()->where('id', $orderId)->exists();
            }
            
            if (!$orderAccess) {
                return response()->json(['error' => 'Accès non autorisé à cette commande'], 403);
            }
            
            // Generate invoice
            $invoiceUrl = $this->invoiceService->generateInvoice($orderId, $user->isMerchant());
            
            return response()->json([
                'success' => true,
                'message' => 'Facture générée avec succès',
                'data' => [
                    'invoice_url' => $invoiceUrl
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la génération de la facture',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}

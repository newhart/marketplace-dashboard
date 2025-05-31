<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\OrderService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class OrderController extends Controller
{
    protected $orderService;

    /**
     * Constructor
     *
     * @param OrderService $orderService
     */
    public function __construct(OrderService $orderService)
    {
        $this->orderService = $orderService;
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
}

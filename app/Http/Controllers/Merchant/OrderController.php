<?php

namespace App\Http\Controllers\Merchant;

use App\Http\Controllers\Controller;
use App\Models\Address;
use App\Models\Order;
use App\Services\OrderService;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    protected $orderService;

    public function __construct(OrderService $orderService)
    {
        $this->orderService = $orderService;
    }

    public function index()
    {
        $orders = Order::whereHas('items.product', function ($query) {
            $query->where('user_id', auth()->id());
        })->with(['items' => function ($query) {
            $query->whereHas('product', function ($q) {
                $q->where('user_id', auth()->id());
            })->with('product');
        }, 'user'])->latest()->paginate(10);

        return response()->json([
            'orders' => $orders
        ]);
    }

    public function show($id)
    {
        $order = Order::whereHas('items.product', function ($query) {
            $query->where('user_id', auth()->id());
        })->with([
            'items' => function ($query) {
                $query->whereHas('product', function ($q) {
                    $q->where('user_id', auth()->id());
                })->with('product');
            },
            'user.addresses' => function ($query) {
                $query->where('type', 'shipping')
                    ->orderBy('is_default', 'desc')
                    ->orderBy('created_at', 'desc');
            }
        ])->findOrFail($id);

        // Recalculer le montant total basé sur les items filtrés
        $calculatedTotal = $order->items->sum(function ($item) {
            return $item->price * $item->quantity;
        });

        // Récupérer le numéro de téléphone du client
        $customerPhone = $order->user ? $order->user->phone : null;

        // Charger les adresses de livraison de l'utilisateur si l'utilisateur existe
        $shippingAddress = null;
        if ($order->user) {
            // Charger les adresses de livraison en faisant une requête directe
            $shippingAddress = Address::where('user_id', $order->user->id)
                ->where('type', 'shipping')
                ->orderBy('is_default', 'desc')
                ->orderBy('created_at', 'desc')
                ->first();
        }

        $shippingAddressData = null;
        if ($shippingAddress) {
            $shippingAddressData = [
                'id' => $shippingAddress->id,
                'title' => $shippingAddress->title,
                'first_name' => $shippingAddress->first_name,
                'last_name' => $shippingAddress->last_name,
                'company' => $shippingAddress->company,
                'address_line_1' => $shippingAddress->address_line_1,
                'address_line_2' => $shippingAddress->address_line_2,
                'city' => $shippingAddress->city,
                'state' => $shippingAddress->state,
                'postal_code' => $shippingAddress->postal_code,
                'country' => $shippingAddress->country,
                'phone' => $shippingAddress->phone,
                'full_address' => $shippingAddress->full_address,
                'is_default' => $shippingAddress->is_default,
            ];
        }

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $order->id,
                'user_id' => $order->user_id,
                'status' => $order->status,
                'total_amount' => $calculatedTotal,
                'created_at' => $order->created_at,
                'updated_at' => $order->updated_at,
                'items' => $order->items,
                'user' => $order->user,
                'customer_phone' => $customerPhone,
                'shipping_address' => $shippingAddressData
            ]
        ]);
    }

    /**
     * Valider un item de commande
     *
     * @param Request $request
     * @param int $orderId
     * @return \Illuminate\Http\JsonResponse
     */
    public function validateItem(Request $request, $orderId)
    {
        $merchant = auth()->user();

        // Valider et récupérer l'ID de l'item depuis le body de la requête
        $request->validate([
            'order_item_id' => 'required|integer|exists:order_items,id'
        ]);
        
        $orderItemId = $request->input('order_item_id');

        // Vérifier que l'item appartient à cette commande
        $order = Order::whereHas('items.product', function ($query) use ($merchant) {
            $query->where('user_id', $merchant->id);
        })->findOrFail($orderId);

        // Vérifier que l'item appartient à cette commande et au commerçant
        $orderItem = $order->items()
            ->whereHas('product', function ($query) use ($merchant) {
                $query->where('user_id', $merchant->id);
            })
            ->findOrFail($orderItemId);

        // Valider l'item de commande
        $validatedOrderItem = $this->orderService->validateOrderItem($orderItemId, $merchant);

        if (!$validatedOrderItem) {
            return response()->json([
                'success' => false,
                'message' => 'Item de commande non trouvé ou vous n\'avez pas l\'autorisation de valider cet item.'
            ], 404);
        }

        // Recharger la commande pour obtenir le statut à jour
        $order->refresh();

        return response()->json([
            'success' => true,
            'message' => 'Item de commande validé avec succès.',
            'data' => [
                'order_item' => $validatedOrderItem,
                'order' => $order,
                'is_order_complete' => $order->status === 'validated'
            ]
        ]);
    }
}

<?php

namespace App\Http\Controllers\Merchant;

use App\Http\Controllers\Controller;
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
        $customerPhone = $order->user->phone ?? null;

        // Récupérer l'adresse de livraison (par défaut ou première disponible)
        $shippingAddress = $order->user->addresses
            ->where('type', 'shipping')
            ->sortByDesc('is_default')
            ->first();

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
            'order' => $order,
            'total_amount' => $calculatedTotal,
            'customer_phone' => $customerPhone,
            'shipping_address' => $shippingAddressData
        ]);
    }

    /**
     * Valider un item de commande
     *
     * @param Request $request
     * @param int $orderId
     * @param int|null $orderItemId
     * @return \Illuminate\Http\JsonResponse
     */
    public function validateItem(Request $request, $orderId, $orderItemId = null)
    {
        $merchant = auth()->user();

        // Récupérer l'ID de l'item depuis l'URL ou le body
        if ($orderItemId === null) {
            $request->validate([
                'order_item_id' => 'required|integer|exists:order_items,id'
            ]);
            $orderItemId = $request->input('order_item_id');
        }

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

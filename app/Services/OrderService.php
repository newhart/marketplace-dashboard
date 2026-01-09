<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use App\Notifications\NewOrderNotification;
use App\Notifications\MerchantOrderNotification;
use App\Notifications\OrderCancelledNotification;
use App\Notifications\OrderValidatedNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

class OrderService
{
    /**
     * Store a new order in the database
     *
     * @param array $orderData
     * @param User $user
     * @return Order
     */
    public function store(array $orderData, User $user)
    {
        return DB::transaction(function () use ($orderData, $user) {
            // Create the order
            $order = Order::create([
                'user_id' => $user->id,
                'status' => 'pending',
                'total_amount' => $orderData['total_amount'],
            ]);

            // Create order items
            foreach ($orderData['items'] as $item) {
                $product = Product::findOrFail($item['product_id']);
                
                $orderItem = OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $product->id,
                    'quantity' => $item['quantity'],
                    'price' => $product->price,
                ]);
                
                // Notify the merchant who owns this product
                $merchant = $product->user;
                if ($merchant) {
                    $merchant->notify(new MerchantOrderNotification($order, $product));
                }
            }
            
            // Notify admin users about the new order
            $admins = User::where('type', 'admin')->get();
            Notification::send($admins, new NewOrderNotification($order));
            
            return $order;
        });
    }
    
    /**
     * Get all orders containing products owned by a merchant
     *
     * @param User $merchant
     * @return \Illuminate\Pagination\LengthAwarePaginator
     */
    public function getMerchantOrders(User $merchant)
    {
        // Get products owned by this merchant
        $productIds = $merchant->products()->pluck('id');
        
        // Get orders containing these products
        return Order::whereHas('items', function($query) use ($productIds) {
            $query->whereIn('product_id', $productIds);
        })
        ->with(['items' => function($query) use ($productIds) {
            $query->whereIn('product_id', $productIds);
            $query->with('product');
        }, 'user'])
        ->orderBy('created_at', 'desc')
        ->paginate(10);
    }
    
    /**
     * Get order details for a merchant (only showing their products)
     *
     * @param int $orderId
     * @param User $merchant
     * @return Order|null
     */
    public function getMerchantOrderDetail($orderId, User $merchant)
    {
        // Get products owned by this merchant
        $productIds = $merchant->products()->pluck('id');
        
        // Check if order contains any of merchant's products
        $order = Order::with(['items' => function($query) use ($productIds) {
            $query->whereIn('product_id', $productIds);
            $query->with('product');
        }, 'user'])
        ->whereHas('items', function($query) use ($productIds) {
            $query->whereIn('product_id', $productIds);
        })
        ->find($orderId);
        
        return $order;
    }
    
    /**
     * Check if merchant has access to an order
     *
     * @param int $orderId
     * @param User $merchant
     * @return bool
     */
    public function merchantHasAccessToOrder($orderId, User $merchant)
    {
        // Get products owned by this merchant
        $productIds = $merchant->products()->pluck('id');
        
        // Check if order contains any of merchant's products
        return Order::whereHas('items', function($query) use ($productIds) {
            $query->whereIn('product_id', $productIds);
        })->where('id', $orderId)->exists();
    }
    
    /**
     * Cancel an order for a merchant
     *
     * @param int $orderId
     * @param User $merchant
     * @return bool
     */
    public function cancelMerchantOrder($orderId, User $merchant)
    {
        // Get products owned by this merchant
        $productIds = $merchant->products()->pluck('id');
        
        // Find the order
        $order = Order::with(['items' => function($query) use ($productIds) {
            $query->whereIn('product_id', $productIds);
        }, 'user'])
        ->whereHas('items', function($query) use ($productIds) {
            $query->whereIn('product_id', $productIds);
        })
        ->find($orderId);
        
        if (!$order) {
            return false;
        }
        
        // Cancel the order
        return DB::transaction(function () use ($order, $merchant, $productIds) {
            // Update order status
            $order->status = 'cancelled';
            $order->save();
            
            // Notify the customer
            $order->user->notify(new OrderCancelledNotification($order, $merchant));
            
            // Notify admin users
            $admins = User::where('type', 'admin')->get();
            foreach ($admins as $admin) {
                $admin->notify(new OrderCancelledNotification($order, $merchant));
            }
            
            return true;
        });
    }

    /**
     * Valider un OrderItem par un commerçant
     *
     * @param int $orderItemId
     * @param User $merchant
     * @return OrderItem|null
     */
    public function validateOrderItem($orderItemId, User $merchant)
    {
        // Récupérer l'item de commande
        $orderItem = OrderItem::with(['order', 'product'])->find($orderItemId);
        
        if (!$orderItem) {
            return null;
        }

        // Vérifier que le produit appartient au commerçant
        if ($orderItem->product->user_id !== $merchant->id) {
            return null;
        }

        // Vérifier que l'item n'est pas déjà validé
        if ($orderItem->isValidated()) {
            return $orderItem;
        }

        return DB::transaction(function () use ($orderItem) {
            // Marquer l'item comme validé
            $orderItem->validated_at = now();
            $orderItem->save();

            // Vérifier si tous les items de la commande sont validés
            $this->checkAndUpdateOrderStatus($orderItem->order);

            return $orderItem->fresh(['order', 'product']);
        });
    }

    /**
     * Vérifier si tous les items d'une commande sont validés et mettre à jour le statut
     *
     * @param Order $order
     * @return void
     */
    public function checkAndUpdateOrderStatus(Order $order)
    {
        // Recharger la commande avec tous ses items
        $order->load('items');

        // Vérifier si tous les items sont validés
        $allItemsValidated = $order->items->every(function ($item) {
            return $item->isValidated();
        });

        // Si tous les items sont validés et que la commande est encore en "pending"
        if ($allItemsValidated && $order->status === 'pending') {
            $order->status = 'validated';
            $order->save();

            // Envoyer une notification au client
            $order->user->notify(new OrderValidatedNotification($order));
        }
    }
}

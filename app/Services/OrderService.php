<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use App\Notifications\NewOrderNotification;
use App\Notifications\MerchantOrderNotification;
use App\Notifications\OrderCancelledNotification;
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
}

<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use App\Notifications\NewOrderNotification;
use App\Notifications\MerchantOrderNotification;
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
                    'unit_price' => $product->price,
                    'total_price' => $product->price * $item['quantity'],
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
}

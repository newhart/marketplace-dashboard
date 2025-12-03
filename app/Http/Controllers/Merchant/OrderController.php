<?php

namespace App\Http\Controllers\Merchant;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;

class OrderController extends Controller
{
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
            'user'
        ])->findOrFail($id);

        // Recalculer le montant total basé sur les items filtrés
        $calculatedTotal = $order->items->sum(function ($item) {
            return $item->price * $item->quantity;
        });

        return response()->json([
            'order' => $order,
            'total_amount' => $calculatedTotal
        ]);
    }
}

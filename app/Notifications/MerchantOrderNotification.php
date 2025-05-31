<?php

namespace App\Notifications;

use App\Models\Order;
use App\Models\Product;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class MerchantOrderNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $order;
    protected $product;

    /**
     * Create a new notification instance.
     *
     * @param Order $order
     * @param Product $product
     * @return void
     */
    public function __construct(Order $order, Product $product)
    {
        $this->order = $order;
        $this->product = $product;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param mixed $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        return ['mail', 'database'];
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param mixed $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {
        $orderItem = $this->order->items()->where('product_id', $this->product->id)->first();
        $quantity = $orderItem ? $orderItem->quantity : 0;
        
        return (new MailMessage)
            ->subject('Nouvelle commande pour votre produit')
            ->greeting('Bonjour ' . $notifiable->name . '!')
            ->line('Un client a commandu00e9 l\'un de vos produits.')
            ->line('Produit: ' . $this->product->name)
            ->line('Quantitu00e9: ' . $quantity)
            ->line('Commande #' . $this->order->id . ' pour un montant total de ' . $orderItem->total_price . ' F.')
            ->action('Voir les du00e9tails', url('/merchant/orders/' . $this->order->id))
            ->line('Merci d\'utiliser notre plateforme!');
    }

    /**
     * Get the array representation of the notification.
     *
     * @param mixed $notifiable
     * @return array
     */
    public function toArray($notifiable)
    {
        $orderItem = $this->order->items()->where('product_id', $this->product->id)->first();
        
        return [
            'order_id' => $this->order->id,
            'product_id' => $this->product->id,
            'product_name' => $this->product->name,
            'quantity' => $orderItem ? $orderItem->quantity : 0,
            'total_price' => $orderItem ? $orderItem->total_price : 0,
            'user_id' => $this->order->user_id,
            'user_name' => $this->order->user->name,
            'message' => 'Nouvelle commande pour votre produit ' . $this->product->name,
        ];
    }
}

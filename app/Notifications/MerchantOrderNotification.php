<?php

namespace App\Notifications;

use App\Models\Order;
use App\Models\Product;
use App\Services\FirebaseNotificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class MerchantOrderNotification extends Notification
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
        return ['firebase', 'mail', 'database', 'broadcast'];
    }

    /**
     * Send Firebase push notification
     *
     * @param object $notifiable
     * @return void
     */
    public function toFirebase(object $notifiable): void
    {
        $firebaseService = app(FirebaseNotificationService::class);
        
        $orderItem = $this->order->items()->where('product_id', $this->product->id)->first();
        $quantity = $orderItem ? $orderItem->quantity : 0;
        $totalPrice = $orderItem ? ($orderItem->price * $orderItem->quantity) : 0;
        
        $title = 'Nouvelle commande pour votre produit';
        $body = "Commande #{$this->order->id} - {$this->product->name} (Quantité: {$quantity}) - Montant: {$totalPrice} F";
        
        $data = [
            'type' => 'merchant_order',
            'order_id' => (string) $this->order->id,
            'product_id' => (string) $this->product->id,
            'product_name' => $this->product->name,
            'quantity' => (string) $quantity,
            'total_price' => (string) $totalPrice,
            'user_id' => (string) $this->order->user_id,
            'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
        ];

        $firebaseService->sendToUser($notifiable, $title, $body, $data);
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
        $totalPrice = $orderItem ? ($orderItem->price * $orderItem->quantity) : 0;

        return (new MailMessage)
            ->subject('Nouvelle commande pour votre produit')
            ->greeting('Bonjour ' . $notifiable->name . '!')
            ->line('Un client a commandé l\'un de vos produits.')
            ->line('Produit: ' . $this->product->name)
            ->line('Quantité: ' . $quantity)
            ->line('Commande #' . $this->order->id . ' pour un montant total de ' . $totalPrice . ' F.')
            ->action('Voir les détails', url('/merchant/orders/' . $this->order->id))
            ->line('Merci d\'utiliser notre plateforme!');
    }

    /**
     * Get the broadcastable representation of the notification.
     *
     * @param mixed $notifiable
     * @return array
     */
    public function toBroadcast($notifiable)
    {
        $orderItem = $this->order->items()->where('product_id', $this->product->id)->first();
        $totalPrice = $orderItem ? ($orderItem->price * $orderItem->quantity) : 0;

        return [
            'order_id' => $this->order->id,
            'product_id' => $this->product->id,
            'product_name' => $this->product->name,
            'quantity' => $orderItem ? $orderItem->quantity : 0,
            'total_price' => $totalPrice,
            'user_id' => $this->order->user_id,
            'user_name' => $this->order->user->name,
            'message' => 'Nouvelle commande pour votre produit ' . $this->product->name,
            'type' => 'merchant_order',
        ];
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
        $totalPrice = $orderItem ? ($orderItem->price * $orderItem->quantity) : 0;

        return [
            'order_id' => $this->order->id,
            'product_id' => $this->product->id,
            'product_name' => $this->product->name,
            'quantity' => $orderItem ? $orderItem->quantity : 0,
            'total_price' => $totalPrice,
            'user_id' => $this->order->user_id,
            'user_name' => $this->order->user->name,
            'message' => 'Nouvelle commande pour votre produit ' . $this->product->name,
        ];
    }
}

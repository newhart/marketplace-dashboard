<?php

namespace App\Notifications;

use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Collection;

/**
 * Notification envoyée à chaque commerçant concerné : uniquement les produits qui lui appartiennent
 * dans la commande livrée (pas tous les produits de la commande).
 */
class OrderDeliveredNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * @param  Order  $order
     * @param  Collection<int, OrderItem>  $myOrderItems  Lignes de la commande dont le produit appartient à ce commerçant
     */
    public function __construct(
        protected Order $order,
        protected Collection $myOrderItems
    ) {}

    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable): MailMessage
    {
        $mail = (new MailMessage)
            ->subject('Livraison effectuée – Commande #' . $this->order->id)
            ->greeting('Bonjour ' . $notifiable->name . ',')
            ->line('La livraison de la commande #' . $this->order->id . ' a été effectuée.')
            ->line('Le client a remis le code de livraison au transporteur ; la commande est maintenant marquée comme livrée.');

        $myOrderItems = $this->myOrderItems->load('product');
        $subtotal = 0;
        foreach ($myOrderItems as $item) {
            $lineTotal = $item->price * $item->quantity;
            $subtotal += $lineTotal;
            $mail->line('• ' . ($item->product?->name ?? 'Produit') . ' x ' . $item->quantity . ' = ' . $lineTotal . ' F');
        }
        $mail->line('Montant de vos produits dans cette commande : ' . $subtotal . ' F.');

        return $mail->line('Merci de votre confiance.');
    }

    public function toArray($notifiable): array
    {
        $myOrderItems = $this->myOrderItems->load('product');
        $itemsSummary = $myOrderItems->map(fn (OrderItem $item) => [
            'product_id' => $item->product_id,
            'product_name' => $item->product?->name,
            'quantity' => $item->quantity,
            'price' => $item->price,
            'subtotal' => $item->price * $item->quantity,
        ])->values()->all();

        return [
            'order_id' => $this->order->id,
            'type' => 'order_delivered',
            'message' => 'La livraison de la commande #' . $this->order->id . ' a été effectuée (vos produits).',
            'my_items' => $itemsSummary,
        ];
    }
}

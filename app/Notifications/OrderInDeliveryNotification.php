<?php

namespace App\Notifications;

use App\Models\Order;
use App\Services\FirebaseNotificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Envoyée au client lorsque le transporteur a pris la commande en charge (acceptation / en cours de livraison).
 */
class OrderInDeliveryNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        protected Order $order
    ) {}

    public function via(object $notifiable): array
    {
        return ['firebase', 'mail', 'database'];
    }

    public function toFirebase(object $notifiable): void
    {
        $firebaseService = app(FirebaseNotificationService::class);
        $title = 'Votre commande est en cours de livraison';
        $body = "Le transporteur a pris en charge votre commande #{$this->order->id}. Elle est en route vers vous.";
        $data = [
            'type' => 'order_in_delivery',
            'order_id' => (string) $this->order->id,
            'status' => $this->order->status,
            'total_amount' => (string) $this->order->total_amount,
        ];
        $firebaseService->sendToUser($notifiable, $title, $body, $data);
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Votre commande est en cours de livraison')
            ->greeting('Bonjour ' . $notifiable->name . ' !')
            ->line('Le transporteur a pris en charge votre commande. Elle est maintenant en cours de livraison.')
            ->line('Commande #' . $this->order->id . ' – Montant : ' . $this->order->total_amount . ' F.')
            ->line('Vous recevrez votre colis prochainement. Pensez à remettre votre code de livraison au transporteur à la réception.')
            ->action('Suivre ma commande', url('/customer/orders/' . $this->order->id))
            ->line('Merci de votre confiance !');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'order_id' => $this->order->id,
            'type' => 'order_in_delivery',
            'status' => $this->order->status,
            'message' => 'Votre commande #' . $this->order->id . ' est en cours de livraison.',
        ];
    }
}

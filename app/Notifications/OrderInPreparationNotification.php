<?php

namespace App\Notifications;

use App\Models\Order;
use App\Services\FirebaseNotificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Envoyée au client lorsque le transporteur a validé tous les articles de la commande (en cours de préparation / livraison).
 */
class OrderInPreparationNotification extends Notification implements ShouldQueue
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
        $title = 'Votre commande est en cours de préparation';
        $body = "Le transporteur a validé tous les articles de votre commande #{$this->order->id}. Elle est en cours de livraison.";
        $data = [
            'type' => 'order_in_preparation',
            'order_id' => (string) $this->order->id,
            'status' => $this->order->status,
            'total_amount' => (string) $this->order->total_amount,
        ];
        $firebaseService->sendToUser($notifiable, $title, $body, $data);
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Votre commande est en cours de préparation')
            ->greeting('Bonjour ' . $notifiable->name . ' !')
            ->line('Le transporteur a validé tous les articles de votre commande. Elle est maintenant en cours de préparation et sera livrée prochainement.')
            ->line('Commande #' . $this->order->id . ' – Montant : ' . $this->order->total_amount . ' F.')
            ->line('Pensez à remettre votre code de livraison au transporteur à la réception.')
            ->action('Suivre ma commande', url('/customer/orders/' . $this->order->id))
            ->line('Merci de votre confiance !');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'order_id' => $this->order->id,
            'type' => 'order_in_preparation',
            'status' => $this->order->status,
            'message' => 'Votre commande #' . $this->order->id . ' est en cours de préparation.',
        ];
    }
}

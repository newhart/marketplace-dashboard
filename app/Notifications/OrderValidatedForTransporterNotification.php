<?php

namespace App\Notifications;

use App\Models\Order;
use App\Services\FirebaseNotificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class OrderValidatedForTransporterNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected Order $order;

    /**
     * Create a new notification instance.
     */
    public function __construct(Order $order)
    {
        $this->order = $order;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['firebase'];
    }

    /**
     * Send Firebase push notification
     */
    public function toFirebase(object $notifiable): void
    {
        $firebaseService = app(FirebaseNotificationService::class);
        
        $title = 'Nouvelle commande disponible';
        $body = "La commande #{$this->order->id} a été validée et est prête pour la livraison. Montant: {$this->order->total_amount} F";
        
        $data = [
            'type' => 'order_validated',
            'order_id' => (string) $this->order->id,
            'total_amount' => (string) $this->order->total_amount,
            'status' => $this->order->status,
            'user_id' => (string) $this->order->user_id,
            'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
        ];

        $firebaseService->sendToUser($notifiable, $title, $body, $data);
    }
}

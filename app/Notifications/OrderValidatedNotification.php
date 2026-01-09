<?php

namespace App\Notifications;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OrderValidatedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $order;

    /**
     * Create a new notification instance.
     *
     * @param Order $order
     * @return void
     */
    public function __construct(Order $order)
    {
        $this->order = $order;
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
        return (new MailMessage)
            ->subject('Votre commande a été validée')
            ->greeting('Bonjour ' . $notifiable->name . '!')
            ->line('Nous avons le plaisir de vous informer que tous les commerçants ont validé votre commande.')
            ->line('Commande #' . $this->order->id . ' pour un montant total de ' . $this->order->total_amount . ' F.')
            ->line('Votre commande est maintenant en cours de préparation et sera expédiée sous peu.')
            ->action('Voir ma commande', url('/customer/orders/' . $this->order->id))
            ->line('Merci de votre confiance!');
    }

    /**
     * Get the array representation of the notification.
     *
     * @param mixed $notifiable
     * @return array
     */
    public function toArray($notifiable)
    {
        return [
            'order_id' => $this->order->id,
            'user_id' => $this->order->user_id,
            'total_amount' => $this->order->total_amount,
            'status' => $this->order->status,
            'message' => 'Votre commande #' . $this->order->id . ' a été validée par tous les commerçants',
        ];
    }
}

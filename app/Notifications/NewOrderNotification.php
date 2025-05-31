<?php

namespace App\Notifications;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewOrderNotification extends Notification implements ShouldQueue
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
            ->subject('Nouvelle commande reçue')
            ->greeting('Bonjour!')
            ->line('Une nouvelle commande a été passée sur la plateforme.')
            ->line('Commande #' . $this->order->id . ' pour un montant total de ' . $this->order->total_amount . ' F.')
            ->action('Voir la commande', url('/admin/orders/' . $this->order->id))
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
        return [
            'order_id' => $this->order->id,
            'user_id' => $this->order->user_id,
            'user_name' => $this->order->user->name,
            'total_amount' => $this->order->total_amount,
            'status' => $this->order->status,
            'message' => 'Nouvelle commande #' . $this->order->id . ' reçue',
        ];
    }
}

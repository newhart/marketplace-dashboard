<?php

namespace App\Notifications;

use App\Models\Order;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OrderCancelledNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $order;
    protected $merchant;

    /**
     * Create a new notification instance.
     *
     * @param Order $order
     * @param User $merchant
     * @return void
     */
    public function __construct(Order $order, User $merchant)
    {
        $this->order = $order;
        $this->merchant = $merchant;
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
        $isAdmin = $notifiable->type === 'admin';
        $isCustomer = $notifiable->id === $this->order->user_id;
        
        $message = (new MailMessage)
            ->subject('Commande annulée')
            ->greeting('Bonjour ' . $notifiable->name . '!');
            
        if ($isCustomer) {
            $message->line('Votre commande #' . $this->order->id . ' a été annulée par le marchand ' . $this->merchant->name . '.')
                   ->line('Montant total: ' . $this->order->total_amount . ' F.')
                   ->action('Voir les détails', url('/orders/' . $this->order->id));
        } else if ($isAdmin) {
            $message->line('La commande #' . $this->order->id . ' a été annulée par le marchand ' . $this->merchant->name . '.')
                   ->line('Client: ' . $this->order->user->name)
                   ->line('Montant total: ' . $this->order->total_amount . ' F.')
                   ->action('Voir les détails', url('/admin/orders/' . $this->order->id));
        }
        
        $message->line('Merci d\'utiliser notre plateforme!');
        
        return $message;
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
            'merchant_id' => $this->merchant->id,
            'merchant_name' => $this->merchant->name,
            'user_id' => $this->order->user_id,
            'user_name' => $this->order->user->name,
            'total_amount' => $this->order->total_amount,
            'message' => 'Commande #' . $this->order->id . ' annulée par le marchand ' . $this->merchant->name,
        ];
    }
}

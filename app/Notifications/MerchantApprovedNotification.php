<?php

namespace App\Notifications;

use App\Models\Merchant;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class MerchantApprovedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $merchant;

    /**
     * Create a new notification instance.
     */
    public function __construct(Merchant $merchant)
    {
        $this->merchant = $merchant;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Votre compte marchand a été approuvé')
            ->greeting('Bonjour ' . $this->merchant->manager_firstname . ' ' . $this->merchant->manager_lastname . ',')
            ->line('Nous sommes heureux de vous informer que votre compte marchand a été approuvé.')
            ->line('Vous pouvez maintenant accéder à votre tableau de bord et commencer à ajouter vos produits.')
            ->action('Accéder au tableau de bord', url('/merchant/dashboard'))
            ->line('Merci de faire partie de notre marketplace!');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'merchant_id' => $this->merchant->id,
            'message' => 'Votre compte marchand a été approuvé',
        ];
    }
}

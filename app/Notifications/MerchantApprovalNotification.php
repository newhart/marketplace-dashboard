<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class MerchantApprovalNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected string $status;
    protected ?string $rejectionReason;

    /**
     * Create a new notification instance.
     */
    public function __construct(string $status, ?string $rejectionReason = null)
    {
        $this->status = $status;
        $this->rejectionReason = $rejectionReason;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $mail = new MailMessage;
        
        if ($this->status === 'approved') {
            return $mail
                ->subject('Votre compte commerçant a été approuvé')
                ->greeting('Bonjour ' . $notifiable->name . ',')
                ->line('Nous sommes heureux de vous informer que votre compte commerçant a été approuvé.')
                ->line('Vous pouvez maintenant vous connecter et commencer à vendre sur notre plateforme.')
                ->action('Se connecter', url('/login'));
        } else {
            return $mail
                ->subject('Votre compte commerçant a été refusé')
                ->greeting('Bonjour ' . $notifiable->name . ',')
                ->line('Nous regrettons de vous informer que votre demande de compte commerçant a été refusée.')
                ->line('Raison: ' . $this->rejectionReason)
                ->line('Vous pouvez nous contacter pour plus d\'informations ou soumettre une nouvelle demande.')
                ->action('Nous contacter', url('/contact'));
        }
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'status' => $this->status,
            'rejection_reason' => $this->rejectionReason,
        ];
    }
}

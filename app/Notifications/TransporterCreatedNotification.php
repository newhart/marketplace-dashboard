<?php

namespace App\Notifications;

use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TransporterCreatedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected User $user;
    protected string $password;

    /**
     * Create a new notification instance.
     */
    public function __construct(User $user, string $password)
    {
        $this->user = $user;
        $this->password = $password;
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
            ->subject('Votre compte transporteur a été créé')
            ->greeting('Bonjour ' . $this->user->name . ',')
            ->line('Votre compte transporteur a été créé avec succès sur notre plateforme.')
            ->line('Voici vos identifiants de connexion :')
            ->line('**Email :** ' . $this->user->email)
            ->line('**Mot de passe :** ' . $this->password)
            ->line('⚠️ **Important :** Pour des raisons de sécurité, nous vous recommandons fortement de changer votre mot de passe après votre première connexion.')
            ->action('Se connecter', Filament::getPanel('transporteur')->getLoginUrl() ?? url('/transporteur/login'))
            ->line('Si vous avez des questions, n\'hésitez pas à nous contacter.')
            ->line('Bienvenue sur notre plateforme !');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'user_id' => $this->user->id,
            'message' => 'Votre compte transporteur a été créé',
        ];
    }
}

<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Log;
use Kreait\Laravel\Firebase\Facades\Firebase;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification as FirebaseNotification;

class FirebaseNotificationService
{
    /**
     * Envoyer une notification push à un utilisateur
     *
     * @param User $user
     * @param string $title
     * @param string $body
     * @param array $data
     * @return bool
     */
    public function sendToUser(User $user, string $title, string $body, array $data = []): bool
    {
        if (empty($user->fcm_token)) {
            Log::warning("L'utilisateur {$user->id} n'a pas de token FCM");
            return false;
        }

        try {
            $messaging = Firebase::messaging();
            
            $notification = FirebaseNotification::create($title, $body);
            
            $message = CloudMessage::withTarget('token', $user->fcm_token)
                ->withNotification($notification)
                ->withData($data);

            $messaging->send($message);
            
            Log::info("Notification push envoyée avec succès à l'utilisateur {$user->id}");
            return true;
        } catch (\Exception $e) {
            Log::error("Erreur lors de l'envoi de la notification push", [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return false;
        }
    }

    /**
     * Envoyer une notification push à plusieurs utilisateurs
     *
     * @param array $users
     * @param string $title
     * @param string $body
     * @param array $data
     * @return int Nombre de notifications envoyées avec succès
     */
    public function sendToUsers(array $users, string $title, string $body, array $data = []): int
    {
        $successCount = 0;
        
        foreach ($users as $user) {
            if ($this->sendToUser($user, $title, $body, $data)) {
                $successCount++;
            }
        }
        
        return $successCount;
    }

    /**
     * Envoyer une notification push à tous les transporteurs actifs
     *
     * @param string $title
     * @param string $body
     * @param array $data
     * @return int Nombre de notifications envoyées avec succès
     */
    public function sendToAllTransporters(string $title, string $body, array $data = []): int
    {
        $transporters = User::where('type', User::TYPE_TRANSPORTER)
            ->where('is_active', true)
            ->whereNotNull('fcm_token')
            ->get();

        return $this->sendToUsers($transporters->toArray(), $title, $body, $data);
    }
}

<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\URL;
use Kreait\Laravel\Firebase\Facades\Firebase;
use Kreait\Firebase\Messaging\CloudMessage;

class FirebaseNotificationService
{
    /**
     * Obtenir l'URL complète du logo
     *
     * @return string
     */
    protected function getLogoUrl(): string
    {
        return URL::asset('images/logo.png');
    }

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
            
            // Obtenir l'URL complète du logo
            $logoUrl = $this->getLogoUrl();
            
            // Ajouter l'image dans les données pour compatibilité avec toutes les plateformes
            $data['image'] = $logoUrl;
            $data['image_url'] = $logoUrl;
            
            // Construire le message avec CloudMessage::fromArray pour inclure l'image
            $messageArray = [
                'token' => $user->fcm_token,
                'notification' => [
                    'title' => $title,
                    'body' => $body,
                    'image' => $logoUrl,
                ],
                'data' => $data,
                'android' => [
                    'notification' => [
                        'image' => $logoUrl,
                    ],
                ],
                'webpush' => [
                    'notification' => [
                        'image' => $logoUrl,
                    ],
                ],
                'apns' => [
                    'payload' => [
                        'aps' => [
                            'mutable-content' => 1,
                        ],
                    ],
                    'fcm_options' => [
                        'image' => $logoUrl,
                    ],
                ],
            ];

            $message = CloudMessage::fromArray($messageArray);
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
     * @param array|\Illuminate\Support\Collection $users
     * @param string $title
     * @param string $body
     * @param array $data
     * @return int Nombre de notifications envoyées avec succès
     */
    public function sendToUsers($users, string $title, string $body, array $data = []): int
    {
        $successCount = 0;
        
        foreach ($users as $user) {
            // S'assurer que $user est un objet User
            if (!($user instanceof User)) {
                Log::warning("Élément invalide dans la liste des utilisateurs", [
                    'type' => gettype($user),
                    'value' => is_object($user) ? get_class($user) : $user
                ]);
                continue;
            }
            
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

        return $this->sendToUsers($transporters, $title, $body, $data);
    }
}

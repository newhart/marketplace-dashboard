<?php

namespace App\Filament\Resources\TransporteurResource\Pages;

use App\Filament\Resources\TransporteurResource;
use App\Models\User;
use App\Notifications\TransporterCreatedNotification;
use Filament\Resources\Pages\CreateRecord;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class CreateTransporteur extends CreateRecord
{
    protected static string $resource = TransporteurResource::class;

    protected ?string $plainPassword = null;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // S'assurer que le type et le rôle permettent l'accès au panel transporteur
        $data['type'] = User::TYPE_TRANSPORTER;
        $data['role'] = 'transporter';
        
        // Récupérer le mot de passe depuis le formulaire (car dehydrated=false)
        $passwordFromForm = $this->form->getState()['password'] ?? null;
        
        // Stocker le mot de passe en clair avant le hashage
        if (!empty($passwordFromForm)) {
            $this->plainPassword = $passwordFromForm;
        } else {
            // Générer un mot de passe si non fourni
            $this->plainPassword = Str::random(12);
        }
        
        // Hasher le mot de passe pour la base de données
        $data['password'] = Hash::make($this->plainPassword);

        // S'assurer que is_active est défini
        if (!isset($data['is_active'])) {
            $data['is_active'] = true;
        }

        return $data;
    }

    protected function afterCreate(): void
    {
        $user = $this->record;
        
        // Utiliser le mot de passe en clair stocké
        $plainPassword = $this->plainPassword;
        
        if (empty($plainPassword)) {
            // Si pour une raison quelconque le mot de passe n'est pas défini, en générer un
            $plainPassword = Str::random(12);
            $user->password = Hash::make($plainPassword);
            $user->save();
        }

        // Envoyer l'email avec les identifiants immédiatement
        try {
            // Rafraîchir l'utilisateur pour s'assurer qu'il est à jour
            $user->refresh();
            
            // Envoyer la notification (sans queue pour un envoi immédiat)
            $user->notify(new TransporterCreatedNotification($user, $plainPassword));
            
            Notification::make()
                ->title('Transporteur créé avec succès')
                ->success()
                ->body("Le transporteur {$user->name} a été créé et un email avec ses identifiants a été envoyé à {$user->email}.")
                ->send();
        } catch (\Exception $e) {
            // Logger l'erreur pour le débogage
            Log::error('Erreur lors de l\'envoi de l\'email au transporteur', [
                'user_id' => $user->id,
                'email' => $user->email,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            Notification::make()
                ->title('Transporteur créé mais email non envoyé')
                ->warning()
                ->body("Le transporteur {$user->name} a été créé mais l'email n'a pas pu être envoyé. Erreur : " . $e->getMessage())
                ->send();
        }
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}

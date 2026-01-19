<?php

namespace App\Filament\Resources\TransporteurResource\Pages;

use App\Filament\Resources\TransporteurResource;
use App\Models\User;
use App\Notifications\TransporterCreatedNotification;
use Filament\Resources\Pages\CreateRecord;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class CreateTransporteur extends CreateRecord
{
    protected static string $resource = TransporteurResource::class;

    protected ?string $plainPassword = null;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // S'assurer que le type est bien 'transporter'
        $data['type'] = User::TYPE_TRANSPORTER;
        
        // Stocker le mot de passe en clair avant le hashage
        if (!empty($data['password'])) {
            $this->plainPassword = $data['password'];
        } else {
            // Générer un mot de passe si non fourni
            $this->plainPassword = Str::random(12);
            $data['password'] = $this->plainPassword;
        }

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
        $plainPassword = $this->plainPassword ?? Str::random(12);
        
        // Si le mot de passe n'était pas fourni, mettre à jour avec le nouveau généré
        if (!$this->plainPassword) {
            $user->password = Hash::make($plainPassword);
            $user->save();
        }

        // Envoyer l'email avec les identifiants
        try {
            $user->notify(new TransporterCreatedNotification($user, $plainPassword));
            
            Notification::make()
                ->title('Transporteur créé avec succès')
                ->success()
                ->body("Le transporteur {$user->name} a été créé et un email avec ses identifiants a été envoyé.")
                ->send();
        } catch (\Exception $e) {
            Notification::make()
                ->title('Transporteur créé mais email non envoyé')
                ->warning()
                ->body("Le transporteur {$user->name} a été créé mais l'email n'a pas pu être envoyé : " . $e->getMessage())
                ->send();
        }
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}

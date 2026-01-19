<?php

namespace App\Filament\Resources\TransporteurResource\Pages;

use App\Filament\Resources\TransporteurResource;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Hash;

class EditTransporteur extends EditRecord
{
    protected static string $resource = TransporteurResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Récupérer le mot de passe depuis le formulaire (car dehydrated=false)
        $passwordFromForm = $this->form->getState()['password'] ?? null;
        
        // Si un nouveau mot de passe est fourni, le hasher
        if (!empty($passwordFromForm)) {
            $data['password'] = Hash::make($passwordFromForm);
        } else {
            // Sinon, retirer le champ pour ne pas écraser le mot de passe existant
            unset($data['password']);
        }

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}

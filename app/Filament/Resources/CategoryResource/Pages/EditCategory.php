<?php

namespace App\Filament\Resources\CategoryResource\Pages;

use App\Filament\Resources\CategoryResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditCategory extends EditRecord
{
    protected static string $resource = CategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function getSaveFormAction(): Actions\Action
    {
        return parent::getSaveFormAction()
            ->requiresConfirmation()
            ->modalHeading('Sauvegarder les modifications')
            ->modalDescription('Êtes-vous sûr de vouloir sauvegarder ces modifications ?')
            ->modalSubmitActionLabel('Oui, sauvegarder');
    }
}

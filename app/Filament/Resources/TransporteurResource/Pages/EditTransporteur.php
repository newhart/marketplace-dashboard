<?php

namespace App\Filament\Resources\TransporteurResource\Pages;

use App\Filament\Resources\TransporteurResource;
use Filament\Resources\Pages\EditRecord;

class EditTransporteur extends EditRecord
{
    protected static string $resource = TransporteurResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}

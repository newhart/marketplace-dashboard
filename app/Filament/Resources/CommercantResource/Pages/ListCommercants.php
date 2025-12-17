<?php

namespace App\Filament\Resources\CommercantResource\Pages;

use App\Filament\Resources\CommercantResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListCommercants extends ListRecords
{
    protected static string $resource = CommercantResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}

<?php

namespace App\Filament\Resources\PromotionalBannerResource\Pages;

use App\Filament\Resources\PromotionalBannerResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListPromotionalBanners extends ListRecords
{
    protected static string $resource = PromotionalBannerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}

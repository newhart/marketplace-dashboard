<?php

namespace App\Filament\MarchantPanel\Resources\OrderResource\Pages;

use App\Filament\MarchantPanel\Resources\OrderResource;
use Filament\Resources\Pages\ListRecords;

class ListOrders extends ListRecords
{
    protected static string $resource = OrderResource::class;
}

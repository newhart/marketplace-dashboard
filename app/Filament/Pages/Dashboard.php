<?php

namespace App\Filament\Pages;

use Filament\Forms\Components\Select;
use Filament\Forms\Form;
use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Pages\Dashboard\Concerns\HasFiltersForm;
use App\Models\User;

class Dashboard extends BaseDashboard
{
    use HasFiltersForm;

    public function filtersForm(Form $form): Form
    {
        return $form
            ->schema([
                Select::make('merchant_id')
                    ->label('Commerçant')
                    ->options(User::where('type', 'merchant')->pluck('name', 'id'))
                    ->searchable()
                    ->preload()
                    ->placeholder('Tous les commerçants (Global)'),
            ]);
    }
}

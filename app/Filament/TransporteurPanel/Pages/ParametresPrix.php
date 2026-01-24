<?php

namespace App\Filament\TransporteurPanel\Pages;

use App\Models\TransporterPriceSetting;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class ParametresPrix extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-currency-dollar';

    protected static ?string $navigationLabel = 'Paramètres de prix';

    protected static ?string $title = 'Paramètres de prix (distance / km)';

    protected static ?string $slug = 'parametres-prix';

    protected static string $view = 'filament.transporteur-panel.pages.parametres-prix';

    public ?array $data = [];

    public function mount(): void
    {
        $setting = auth()->user()->transporterPriceSetting;
        $this->form->fill([
            'price_per_km' => $setting?->price_per_km ?? 0,
            'minimum_amount' => $setting?->minimum_amount,
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Tarification selon la distance')
                    ->description('Définissez votre prix au kilomètre et un éventuel montant minimum de livraison.')
                    ->schema([
                        TextInput::make('price_per_km')
                            ->label('Prix par kilomètre (XPF)')
                            ->numeric()
                            ->minValue(0)
                            ->required()
                            ->suffix('XPF/km')
                            ->helperText('Montant facturé pour chaque kilomètre parcouru'),
                        TextInput::make('minimum_amount')
                            ->label('Montant minimum de livraison (XPF)')
                            ->numeric()
                            ->minValue(0)
                            ->suffix('XPF')
                            ->helperText('Optionnel : montant minimum même pour les courtes distances'),
                    ])
                    ->columns(1),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $data = $this->form->getState();

        TransporterPriceSetting::updateOrCreate(
            ['user_id' => auth()->id()],
            [
                'price_per_km' => $data['price_per_km'],
                'minimum_amount' => $data['minimum_amount'] ?: null,
            ]
        );

        Notification::make()
            ->title('Paramètres enregistrés')
            ->success()
            ->send();
    }
}

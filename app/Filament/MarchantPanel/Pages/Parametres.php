<?php

namespace App\Filament\MarchantPanel\Pages;

use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Pages\Page;

class Parametres extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static ?string $navigationLabel = 'Paramètres';

    protected static ?string $title = 'Paramètres';

    protected static ?string $slug = 'parametres';

    protected static string $view = 'filament.marchant-panel.pages.parametres';

    public ?array $data = [];

    public function mount(): void
    {
        $user = auth()->user();
        $this->form->fill([
            'name' => $user->name,
            'email' => $user->email,
            'company_name' => $user->company_name ?? '',
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Informations du compte')
                    ->schema([
                        TextInput::make('name')->label('Nom')->required(),
                        TextInput::make('email')->label('Email')->email()->required(),
                        TextInput::make('company_name')->label('Nom de l\'entreprise'),
                    ])
                    ->columns(1),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $data = $this->form->getState();
        $user = auth()->user();
        $user->update([
            'name' => $data['name'],
            'email' => $data['email'],
            'company_name' => $data['company_name'] ?? $user->company_name,
        ]);

        \Filament\Notifications\Notification::make()
            ->title('Paramètres enregistrés')
            ->success()
            ->send();
    }
}

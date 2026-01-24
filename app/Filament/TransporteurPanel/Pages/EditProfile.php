<?php

namespace App\Filament\TransporteurPanel\Pages;

use Filament\Forms\Components\Section;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Pages\Auth\EditProfile as BaseEditProfile;

class EditProfile extends BaseEditProfile
{
    public static function getLabel(): string
    {
        return 'Mon profil';
    }

    protected function getForms(): array
    {
        return [
            'form' => $this->form(
                $this->makeForm()
                    ->schema([
                        Section::make('Informations du compte')
                            ->schema([
                                $this->getNameFormComponent(),
                                $this->getEmailFormComponent(),
                                $this->getPasswordFormComponent(),
                                $this->getPasswordConfirmationFormComponent(),
                            ])
                            ->columns(1),
                        Section::make('Informations transporteur')
                            ->schema([
                                TextInput::make('phone')
                                    ->label('Téléphone')
                                    ->tel()
                                    ->maxLength(20),
                                TextInput::make('company_name')
                                    ->label('Nom de l\'entreprise')
                                    ->maxLength(255),
                                TextInput::make('siret')
                                    ->label('SIRET')
                                    ->maxLength(14)
                                    ->helperText('14 chiffres'),
                                TextInput::make('vehicle_type')
                                    ->label('Type de véhicule')
                                    ->maxLength(255)
                                    ->helperText('Ex: Camion, Van, Véhicule léger'),
                                TextInput::make('license_number')
                                    ->label('Numéro de permis')
                                    ->maxLength(255),
                                TextInput::make('insurance_number')
                                    ->label('Numéro d\'assurance')
                                    ->maxLength(255),
                                Textarea::make('address')
                                    ->label('Adresse')
                                    ->rows(3)
                                    ->columnSpanFull(),
                            ])
                            ->columns(2),
                    ])
                    ->operation('edit')
                    ->model($this->getUser())
                    ->statePath('data')
                    ->inlineLabel(! static::isSimple()),
            ),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        unset($data['password'], $data['remember_token']);

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        unset($data['passwordConfirmation']);

        return parent::mutateFormDataBeforeSave($data);
    }
}

<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TransporteurResource\Pages;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Hash;
use Filament\Tables\Actions\Action;
use Filament\Notifications\Notification;

class TransporteurResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $modelLabel = 'Transporteur';
    protected static ?string $pluralModelLabel = 'Transporteurs';
    protected static ?string $navigationLabel = 'Transporteurs';
    protected static ?string $navigationIcon = 'heroicon-o-truck';
    protected static ?string $slug = 'transporteurs';

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('type', User::TYPE_TRANSPORTER);
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Informations Compte')
                    ->schema([
                        TextInput::make('name')
                            ->label('Nom d\'utilisateur')
                            ->required(),
                        TextInput::make('email')
                            ->email()
                            ->required()
                            ->unique(ignoreRecord: true),
                        TextInput::make('phone')
                            ->label('Téléphone')
                            ->tel()
                            ->maxLength(20),
                        TextInput::make('password')
                            ->password()
                            ->dehydrateStateUsing(fn($state) => Hash::make($state))
                            ->dehydrated(fn($state) => filled($state))
                            ->required(fn(string $context): bool => $context === 'create')
                            ->helperText('Laissez vide pour conserver le mot de passe actuel lors de la modification'),
                        Select::make('type')
                            ->options([
                                'transporter' => 'Transporteur',
                            ])
                            ->default('transporter')
                            ->disabled()
                            ->dehydrated()
                            ->required(),
                        Toggle::make('is_active')
                            ->label('Compte Actif')
                            ->default(true)
                            ->helperText('Désactiver ce compte empêchera le transporteur de se connecter.'),
                    ])->columns(2),

                Section::make('Informations Transporteur')
                    ->schema([
                        TextInput::make('company_name')
                            ->label('Nom de l\'entreprise')
                            ->maxLength(255),
                        TextInput::make('siret')
                            ->label('SIRET')
                            ->maxLength(14)
                            ->helperText('Numéro SIRET de l\'entreprise (14 chiffres)'),
                        TextInput::make('vehicle_type')
                            ->label('Type de véhicule')
                            ->maxLength(255)
                            ->helperText('Ex: Camion, Van, Véhicule léger...'),
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
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Nom')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('email')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('phone')
                    ->label('Téléphone')
                    ->searchable(),
                TextColumn::make('company_name')
                    ->label('Entreprise')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('vehicle_type')
                    ->label('Type de véhicule')
                    ->searchable(),
                IconColumn::make('is_active')
                    ->label('Actif')
                    ->boolean()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label('Créé le')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\Filter::make('is_active')
                    ->label('Compte actif')
                    ->query(fn (Builder $query): Builder => $query->where('is_active', true)),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Action::make('toggleActive')
                    ->label(fn(User $record) => $record->is_active ? 'Désactiver' : 'Activer')
                    ->color(fn(User $record) => $record->is_active ? 'danger' : 'success')
                    ->icon(fn(User $record) => $record->is_active ? 'heroicon-o-x-circle' : 'heroicon-o-check-circle')
                    ->action(function (User $record) {
                        $record->is_active = !$record->is_active;
                        $record->save();

                        Notification::make()
                            ->title($record->is_active ? 'Compte activé' : 'Compte désactivé')
                            ->success()
                            ->send();
                    })
                    ->requiresConfirmation(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTransporteurs::route('/'),
            'create' => Pages\CreateTransporteur::route('/create'),
            'edit' => Pages\EditTransporteur::route('/{record}/edit'),
        ];
    }
}

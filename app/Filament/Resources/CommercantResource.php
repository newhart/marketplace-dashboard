<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CommercantResource\Pages;
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
use Illuminate\Support\Facades\DB;
use App\Notifications\MerchantApprovedNotification; // Assuming this exists as seen in UserFilamentResource
use Filament\Notifications\Notification;

class CommercantResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $modelLabel = 'Commerçant';
    protected static ?string $pluralModelLabel = 'Commerçants';
    protected static ?string $navigationLabel = 'Commerçants';
    protected static ?string $navigationIcon = 'heroicon-o-building-storefront';
    protected static ?string $slug = 'commercants';

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('type', User::TYPE_MERCHANT);
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
                            ->required(),
                        TextInput::make('password')
                            ->password()
                            ->dehydrateStateUsing(fn($state) => Hash::make($state))
                            ->dehydrated(fn($state) => filled($state))
                            ->required(fn(string $context): bool => $context === 'create'),
                        Select::make('type')
                            ->options([
                                'merchant' => 'Marchand',
                            ])
                            ->default('merchant')
                            ->disabled()
                            ->dehydrated()
                            ->required(),
                        Toggle::make('is_active')
                            ->label('Compte Actif')
                            ->default(true)
                            ->helperText('Désactiver ce compte empêchera le commerçant de se connecter.'),
                    ])->columns(2),

                Section::make('Informations Boutique')
                    ->relationship('merchant')
                    ->schema([
                        TextInput::make('manager_lastname')
                            ->label('Nom du gérant')
                            ->required(),
                        TextInput::make('manager_firstname')
                            ->label('Prénom du gérant')
                            ->required(),
                        TextInput::make('mobile_phone')
                            ->label('Téléphone mobile')
                            ->required(),
                        TextInput::make('landline_phone')
                            ->label('Téléphone fixe'),
                        TextInput::make('business_address')
                            ->label('Adresse')
                            ->required(),
                        TextInput::make('business_city')
                            ->label('Ville')
                            ->required(),
                        TextInput::make('business_postal_code')
                            ->label('Code Postal')
                            ->required(),
                        TextInput::make('business_type')
                            ->label('Type d\'entreprise'),
                        Textarea::make('business_description')
                            ->label('Description')
                            ->columnSpanFull(),
                        Select::make('approval_status')
                            ->label('Statut d\'approbation')
                            ->options([
                                'pending' => 'En attente',
                                'approved' => 'Approuvé',
                                'rejected' => 'Rejeté',
                            ])
                            ->default('pending'),
                        Textarea::make('rejection_reason')
                            ->label('Raison du rejet')
                            ->visible(fn(Forms\Get $get) => $get('approval_status') === 'rejected')
                            ->columnSpanFull(),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Utilisateur')
                    ->searchable(),
                TextColumn::make('email')
                    ->searchable(),
                TextColumn::make('merchant.manager_lastname')
                    ->label('Gérant')
                    ->formatStateUsing(fn($state, $record) => $record->merchant ? $record->merchant->manager_firstname . ' ' . $record->merchant->manager_lastname : '-'),
                TextColumn::make('merchant.business_city')
                    ->label('Ville')
                    ->searchable(),
                IconColumn::make('is_active')
                    ->label('Actif')
                    ->boolean()
                    ->action(function ($record, $column) {
                        $record->is_active = !$record->is_active;
                        $record->save();
                    }),
                TextColumn::make('merchant.approval_status')
                    ->label('Approbation')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'approved' => 'success',
                        'rejected' => 'danger',
                        default => 'warning',
                    }),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
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
            'index' => Pages\ListCommercants::route('/'),
            'create' => Pages\CreateCommercant::route('/create'),
            'edit' => Pages\EditCommercant::route('/{record}/edit'),
        ];
    }
}

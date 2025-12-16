<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PromotionalBannerResource\Pages;
use App\Filament\Resources\PromotionalBannerResource\RelationManagers;
use App\Models\PromotionalBanner;
use Filament\Forms;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class PromotionalBannerResource extends Resource
{
    protected static ?string $model = PromotionalBanner::class;

    protected static ?string $navigationIcon = 'heroicon-o-megaphone';

    protected static ?string $navigationLabel = 'Bannières Promotionnelles';

    protected static ?string $modelLabel = 'Bannière';

    protected static ?string $pluralModelLabel = 'Bannières Promotionnelles';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make()
                    ->extraAttributes(['class' => 'max-w-6xl mx-auto'])
                    ->schema([
                        TextInput::make('title')
                            ->label('Titre')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('Ex: Du 10 au 20 mars')
                            ->columnSpanFull(),

                        TextInput::make('subtitle')
                            ->label('Sous-titre')
                            ->maxLength(255)
                            ->placeholder('Ex: Promotion sur les fruits de saison')
                            ->columnSpanFull(),

                        FileUpload::make('image')
                            ->label('Image de la bannière')
                            ->image()
                            ->directory('promotional-banners')
                            ->required()
                            ->maxSize(5120) // 5MB
                            ->helperText('Format: JPG, PNG, GIF. Taille max: 5MB. Dimensions recommandées: 1200x400px')
                            ->columnSpanFull(),

                        Forms\Components\Select::make('product_id')
                            ->label('Produit lié')
                            ->relationship('product', 'name')
                            ->searchable()
                            ->preload()
                            ->helperText('Rediriger vers ce produit lors du clic sur la bannière')
                            ->columnSpanFull(),

                        DatePicker::make('start_date')
                            ->label('Date de début')
                            ->nullable()
                            ->helperText('Laisser vide pour une promotion sans date de début'),

                        DatePicker::make('end_date')
                            ->label('Date de fin')
                            ->nullable()
                            ->helperText('Laisser vide pour une promotion sans date de fin'),

                        TextInput::make('display_order')
                            ->label('Ordre d\'affichage')
                            ->numeric()
                            ->default(0)
                            ->required()
                            ->helperText('Les bannières avec un ordre inférieur s\'affichent en premier'),

                        Toggle::make('is_active')
                            ->label('Actif')
                            ->default(true)
                            ->helperText('Désactiver pour masquer temporairement la bannière'),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('image')
                    ->label('Image')
                    ->size(100),

                TextColumn::make('title')
                    ->label('Titre')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                TextColumn::make('subtitle')
                    ->label('Sous-titre')
                    ->searchable()
                    ->limit(40)
                    ->color('secondary'),

                TextColumn::make('start_date')
                    ->label('Début')
                    ->date('d/m/Y')
                    ->sortable()
                    ->placeholder('Aucune'),

                TextColumn::make('end_date')
                    ->label('Fin')
                    ->date('d/m/Y')
                    ->sortable()
                    ->placeholder('Aucune'),

                TextColumn::make('display_order')
                    ->label('Ordre')
                    ->sortable()
                    ->badge()
                    ->color('info'),

                IconColumn::make('is_active')
                    ->label('Statut')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger'),

                TextColumn::make('created_at')
                    ->label('Créée le')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('display_order', 'asc')
            ->filters([
                SelectFilter::make('is_active')
                    ->label('Statut')
                    ->options([
                        true => 'Actif',
                        false => 'Inactif',
                    ]),

                Filter::make('current')
                    ->label('Promotions en cours')
                    ->query(fn(Builder $query): Builder => $query->current()),

                Filter::make('upcoming')
                    ->label('Promotions à venir')
                    ->query(fn(Builder $query): Builder => $query->whereDate('start_date', '>', now())),
            ])
            ->actions([
                Action::make('toggle_active')
                    ->label('')
                    ->tooltip('Activer/Désactiver')
                    ->icon('heroicon-o-arrow-path')
                    ->iconButton()
                    ->color(fn(PromotionalBanner $record): string => $record->is_active ? 'danger' : 'success')
                    ->action(function (PromotionalBanner $record): void {
                        $record->is_active = !$record->is_active;
                        $record->save();
                    }),
                Tables\Actions\EditAction::make()
                    ->iconButton()
                    ->tooltip('Modifier'),
                Tables\Actions\DeleteAction::make()
                    ->iconButton()
                    ->tooltip('Supprimer'),
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
            'index' => Pages\ListPromotionalBanners::route('/'),
            'create' => Pages\CreatePromotionalBanner::route('/create'),
            'edit' => Pages\EditPromotionalBanner::route('/{record}/edit'),
        ];
    }
}

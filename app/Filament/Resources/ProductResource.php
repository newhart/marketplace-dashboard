<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProductResource\Pages;
use App\Models\Product;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists\Components as InfolistComponents;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ProductResource extends Resource
{
    protected static ?string $model = Product::class;

    protected static ?string $navigationIcon = 'heroicon-o-shopping-bag';

    protected static ?string $navigationGroup = 'Catalogue';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informations du produit')
                    ->extraAttributes(['class' => 'max-w-6xl mx-auto'])
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Nom')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\Textarea::make('description')
                            ->label('Description')
                            ->columnSpanFull(),
                        Forms\Components\TextInput::make('price')
                            ->label('Prix')
                            ->numeric()
                            ->required()
                            ->prefix('XPF'),
                        Forms\Components\Select::make('category_id')
                            ->label('Catégorie')
                            ->relationship('category', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),
                        Forms\Components\Select::make('user_id')
                            ->label('Vendeur')
                            ->relationship('user', 'name')
                            ->searchable()
                            ->preload(),
                    ])
                    ->columns(2),

            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('image')
                    ->label('Image')
                    ->getStateUsing(fn($record) => $record->image ?: optional($record->firstImage)->path)
                    ->square(),
                Tables\Columns\TextColumn::make('name')
                    ->label('Nom')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('category.name')
                    ->label('Catégorie')
                    ->sortable(),
                Tables\Columns\TextColumn::make('price')
                    ->label('Prix')
                    ->money('XPF')
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Créé le')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('category')
                    ->label('Catégorie')
                    ->relationship('category', 'name'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->paginated([10, 25, 50, 100]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                InfolistComponents\Section::make('Détails du produit')
                    ->schema([
                        InfolistComponents\ImageEntry::make('image')
                            ->label('Image')
                            ->getStateUsing(fn($record) => $record->image ?: optional($record->firstImage)->path)
                            ->height(160)
                            ->columnSpanFull(),
                        InfolistComponents\Grid::make(3)
                            ->schema([
                                InfolistComponents\TextEntry::make('name')
                                    ->label('Nom')
                                    ->size('lg')
                                    ->weight('bold')
                                    ->columnSpan(2),
                                InfolistComponents\TextEntry::make('price')
                                    ->label('Prix')
                                    ->money('XPF')
                                    ->color('primary')
                                    ->size('lg')
                                    ->weight('bold'),
                            ]),
                        InfolistComponents\TextEntry::make('description')
                            ->label('Description')
                            ->columnSpanFull(),
                        InfolistComponents\Grid::make(3)
                            ->schema([
                                InfolistComponents\TextEntry::make('category.name')
                                    ->label('Catégorie'),
                                InfolistComponents\TextEntry::make('user.name')
                                    ->label('Vendeur'),
                                InfolistComponents\TextEntry::make('created_at')
                                    ->label('Créé le')
                                    ->dateTime('d/m/Y H:i'),
                            ]),
                    ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            // Relations (avis, promotions, etc.) pourront être ajoutées ici plus tard
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProducts::route('/'),
            'create' => Pages\CreateProduct::route('/create'),
            'view' => Pages\ViewProduct::route('/{record}'),
            'edit' => Pages\EditProduct::route('/{record}/edit'),
        ];
    }
}

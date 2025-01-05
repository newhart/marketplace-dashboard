<?php

namespace App\Filament\MarchantPanel\Resources;

use App\Filament\MarchantPanel\Resources\ProductResource\Pages;
use App\Filament\MarchantPanel\Resources\ProductResource\RelationManagers;
use App\Models\Category;
use App\Models\Product;
use Filament\Forms;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ProductResource extends Resource
{
    protected static ?string $model = Product::class;

    protected static ?string $navigationIcon = 'heroicon-o-shopping-cart';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('name')->required(),
                TextInput::make('price')->required(),
                RichEditor::make('description')->required(),
                FileUpload::make('image')
                    ->image()
                    ->required(),
                Select::make('category_id')
                    ->options(
                        Category::all()
                            ->where('user_id', auth()->user()->id)
                            ->pluck('name', 'id')
                    )
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->query(fn() => Product::where('user_id', auth()->user()->id))
            ->columns([
                TextColumn::make('name')->searchable(),
                TextColumn::make('price'),
                ImageColumn::make('image'),
                TextColumn::make('category.name')->searchable(),
            ])
            ->filters([
                SelectFilter::make('category_id')
                    ->options(
                        Category::all()
                            ->where('user_id', auth()->user()->id)
                            ->pluck('name', 'id')
                    )
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ;
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
            'index' => Pages\ListProducts::route('/'),
            'create' => Pages\CreateProduct::route('/create'),
            'edit' => Pages\EditProduct::route('/{record}/edit'),
        ];
    }
}

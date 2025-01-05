<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserFilamentResource\Pages;
use App\Filament\Resources\UserFilamentResource\RelationManagers;
use App\Models\User;
use App\Models\UserFilament;
use Filament\Forms;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class UserFilamentResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-user';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('name'),
                TextInput::make('email'),
                Select::make('role')
                    ->options([
                        'marchant' => 'Marchant',
                        'user' => 'User',
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                 TextColumn::make('name'),
                 TextColumn::make('email'),
                 TextColumn::make('role'),
            ])
            ->filters([
                SelectFilter::make('role')
                    ->options([
                        'marchant' => 'Marchant',
                        'user' => 'User',
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
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
            'index' => Pages\ListUserFilaments::route('/'),
            'create' => Pages\CreateUserFilament::route('/create'),
            'edit' => Pages\EditUserFilament::route('/{record}/edit'),
        ];
    }
}

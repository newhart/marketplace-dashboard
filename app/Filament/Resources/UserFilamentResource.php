<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserFilamentResource\Pages;
use App\Filament\Resources\UserFilamentResource\RelationManagers;
use App\Models\User;
use App\Models\Merchant;
use App\Models\UserFilament;
use App\Notifications\MerchantApprovedNotification;
use Filament\Forms;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\DB;

class UserFilamentResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-user';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('name')
                    ->required(),
                TextInput::make('email')
                    ->email()
                    ->required(),
                Select::make('type')
                    ->options([
                        'customer' => 'Client',
                        'merchant' => 'Marchand',
                        'seller' => 'Vendeur',
                        'transporter' => 'Transporteur',
                    ])
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable(),
                TextColumn::make('email')
                    ->searchable(),
                TextColumn::make('type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'merchant' => 'warning',
                        'admin' => 'danger',
                        default => 'success',
                    }),
                IconColumn::make('is_approved')
                    ->boolean()
                    ->label('Approuvé')
                    ->getStateUsing(function (User $record): bool {
                        if (!$record->isMerchant()) {
                            return true; // Non-marchands sont toujours considérés comme approuvés
                        }
                        
                        $merchant = $record->merchant;
                        return $merchant && $merchant->approval_status === 'approved';
                    }),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('type')
                    ->options([
                        'merchant' => 'Marchand',
                        'customer' => 'Client',
                        'seller' => 'Vendeur',
                        'transporter' => 'Transporteur',
                    ]),
                SelectFilter::make('approval_status')
                    ->label('Statut d\'approbation')
                    ->options([
                        'pending' => 'En attente',
                        'approved' => 'Approuvé',
                        'rejected' => 'Rejeté',
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['value'],
                                fn (Builder $query, $value): Builder => $query->whereHas(
                                    'merchant',
                                    fn (Builder $query): Builder => $query->where('approval_status', $value)
                                )
                            );
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
                Action::make('approuveMarchand')
                    ->label('Approuver')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (User $record): bool => 
                        $record->isMerchant() && 
                        $record->merchant && 
                        $record->merchant->approval_status === 'pending'
                    )
                    ->action(function (User $record): void {
                        DB::transaction(function () use ($record) {
                            $merchant = $record->merchant;
                            $merchant->approval_status = 'approved';
                            $merchant->save();
                            
                            // Mettre à jour le statut d'approbation de l'utilisateur
                            $record->is_approved = true;
                            $record->save();
                            
                            // Envoyer une notification par email
                            $record->notify(new MerchantApprovedNotification($merchant));
                            
                            Notification::make()
                                ->title('Marchand approuvé')
                                ->success()
                                ->body("Le marchand {$record->name} a été approuvé et notifié par email.")
                                ->send();
                        });
                    }),
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

<?php

namespace App\Filament\MarchantPanel\Widgets;

use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class MerchantLatestOrders extends BaseWidget
{
    protected int|string|array $columnSpan = 'full';

    protected static ?string $heading = 'Dernières commandes (avec vos produits)';

    public function table(Table $table): Table
    {
        $merchantId = auth()->id();

        return $table
            ->query(
                \App\Models\Order::query()
                    ->whereHas('items.product', fn ($q) => $q->where('user_id', $merchantId))
                    ->latest()
                    ->limit(10)
            )
            ->columns([
                Tables\Columns\TextColumn::make('id')->label('N°')->sortable(),
                Tables\Columns\TextColumn::make('user.name')->label('Client')->searchable(),
                Tables\Columns\TextColumn::make('status')
                    ->label('Statut')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'warning',
                        'processing' => 'info',
                        'completed' => 'success',
                        'cancelled' => 'danger',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('total_amount')->label('Total')->money('XPF')->sortable(),
                Tables\Columns\TextColumn::make('created_at')->label('Date')->dateTime('d/m/Y H:i')->sortable(),
            ])
            ->actions([
                Tables\Actions\Action::make('view')
                    ->label('Voir')
                    ->url(fn ($record) => \App\Filament\MarchantPanel\Resources\OrderResource::getUrl('view', ['record' => $record])),
            ])
            ->paginated(false);
    }
}

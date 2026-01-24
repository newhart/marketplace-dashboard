<?php

namespace App\Filament\MarchantPanel\Resources\OrderResource\Pages;

use App\Filament\MarchantPanel\Resources\OrderResource;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\ViewRecord;

class ViewOrder extends ViewRecord
{
    protected static string $resource = OrderResource::class;

    protected function getItemsDetailForMerchant(): string
    {
        if ($this->record === null) {
            return 'Aucun de vos produits dans cette commande.';
        }
        $merchantId = auth()->id();
        $items = $this->record->items()
            ->whereHas('product', fn ($q) => $q->where('user_id', $merchantId))
            ->with('product')
            ->get();
        if ($items->isEmpty()) {
            return 'Aucun de vos produits dans cette commande.';
        }
        $lines = $items->map(fn ($i) => '- ' . $i->product->name . ' x' . $i->quantity . ' à ' . number_format($i->price, 0, ',', ' ') . ' XPF');
        $subtotal = $items->sum(fn ($i) => $i->quantity * $i->price);

        return implode("\n", $lines->toArray()) . "\n\n**Sous-total (mes produits) : " . number_format($subtotal, 0, ',', ' ') . ' XPF**';
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Section::make('Commande')
                    ->schema([
                        TextEntry::make('id')->label('N° commande'),
                        TextEntry::make('user.name')->label('Client'),
                        TextEntry::make('status')
                            ->label('Statut')
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'pending' => 'warning',
                                'processing' => 'info',
                                'completed' => 'success',
                                'cancelled' => 'danger',
                                default => 'gray',
                            }),
                        TextEntry::make('created_at')->label('Date')->dateTime('d/m/Y H:i'),
                    ])
                    ->columns(2),
                Section::make('Mes produits dans cette commande')
                    ->schema([
                        TextEntry::make('items_detail')
                            ->label('')
                            ->state(fn () => $this->getItemsDetailForMerchant())
                            ->markdown(),
                    ]),
            ]);
    }
}

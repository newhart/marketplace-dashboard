<?php

namespace App\Filament\TransporteurPanel\Widgets;

use App\Models\TransporterPriceSetting;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class TransporterStats extends BaseWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $user = auth()->user();
        $priceSetting = $user->transporterPriceSetting;

        return [
            Stat::make('Prix au km', $priceSetting ? number_format($priceSetting->price_per_km, 0, ',', ' ') . ' XPF' : 'Non configuré')
                ->description('Paramètres de prix')
                ->descriptionIcon('heroicon-m-currency-dollar')
                ->color($priceSetting ? 'success' : 'warning'),

            Stat::make('Montant minimum', $priceSetting && $priceSetting->minimum_amount
                ? number_format($priceSetting->minimum_amount, 0, ',', ' ') . ' XPF'
                : 'Non défini')
                ->description('Seuil minimum de livraison')
                ->descriptionIcon('heroicon-m-truck')
                ->color('gray'),
        ];
    }
}

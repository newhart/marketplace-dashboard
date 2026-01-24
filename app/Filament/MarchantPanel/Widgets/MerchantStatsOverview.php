<?php

namespace App\Filament\MarchantPanel\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;

class MerchantStatsOverview extends BaseWidget
{
    protected function getStats(): array
    {
        $merchantId = auth()->id();

        $revenue = \App\Models\OrderItem::whereHas('product', fn ($q) => $q->where('user_id', $merchantId))
            ->sum(DB::raw('price * quantity'));

        $ordersCount = \App\Models\Order::whereHas('items.product', fn ($q) => $q->where('user_id', $merchantId))
            ->count();

        $productsCount = \App\Models\Product::where('user_id', $merchantId)->count();

        return [
            Stat::make('Chiffre d\'affaires', number_format($revenue, 0, ',', ' ') . ' XPF')
                ->description('Ventes de vos produits')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('success'),

            Stat::make('Commandes', $ordersCount)
                ->description('Avec vos produits')
                ->descriptionIcon('heroicon-m-shopping-bag')
                ->color('primary'),

            Stat::make('Produits', $productsCount)
                ->description('Dans votre catalogue')
                ->descriptionIcon('heroicon-m-cube')
                ->color('info'),
        ];
    }
}

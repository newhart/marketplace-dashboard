<?php

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Illuminate\Support\Facades\DB;

class StatsOverview extends BaseWidget
{
    use InteractsWithPageFilters;

    protected function getStats(): array
    {
        $merchantId = $this->filters['merchant_id'] ?? null;

        $revenue = $merchantId
            ? \App\Models\OrderItem::whereHas('product', fn($q) => $q->where('user_id', $merchantId))->sum(DB::raw('price * quantity'))
            : \App\Models\Order::sum('total_amount');

        $ordersCount = $merchantId
            ? \App\Models\Order::whereHas('items.product', fn($q) => $q->where('user_id', $merchantId))->count()
            : \App\Models\Order::count();

        return [
            Stat::make('Chiffre d\'affaires', number_format($revenue, 0, ',', ' ') . ' XPF')
                ->description('Total des ventes')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('success')
                ->chart([7, 2, 10, 3, 15, 4, 17]),

            Stat::make('Commandes', $ordersCount)
                ->description('Nombre total de commandes')
                ->descriptionIcon('heroicon-m-shopping-bag')
                ->color('primary'),

            Stat::make('Nouveaux Clients', \App\Models\User::where('type', 'customer')->count())
                ->description('Clients enregistrés')
                ->descriptionIcon('heroicon-m-user-group')
                ->color('info'),

            Stat::make('Marchands en attente', \App\Models\User::whereHas('merchant', function ($query) {
                $query->where('approval_status', 'pending');
            })->count())
                ->description('Nécessitent une validation')
                ->descriptionIcon('heroicon-m-clock')
                ->color('warning'),
        ];
    }
}

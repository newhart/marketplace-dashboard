<?php

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverview extends BaseWidget
{
    protected function getStats(): array
    {
        return [
            Stat::make('Chiffre d\'affaires', number_format(\App\Models\Order::sum('total_amount'), 0, ',', ' ') . ' XPF')
                ->description('Total des ventes')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('success')
                ->chart([7, 2, 10, 3, 15, 4, 17]),

            Stat::make('Commandes', \App\Models\Order::count())
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

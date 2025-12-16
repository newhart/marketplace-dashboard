<?php

namespace App\Filament\Widgets;

use Filament\Widgets\ChartWidget;

class SalesChart extends ChartWidget
{
    protected static ?string $heading = 'Évolution du C.A. (HT)';

    protected function getData(): array
    {
        $data = \Flowframe\Trend\Trend::model(\App\Models\Order::class)
            ->between(
                start: now()->startOfYear(),
                end: now()->endOfYear(),
            )
            ->perMonth()
            ->sum('total_amount');

        return [
            'datasets' => [
                [
                    'label' => 'Chiffre d\'affaires',
                    'data' => $data->map(fn(\Flowframe\Trend\TrendValue $value) => $value->aggregate),
                    'borderColor' => '#02C6B0',
                ],
            ],
            'labels' => $data->map(fn(\Flowframe\Trend\TrendValue $value) => $value->date),
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}

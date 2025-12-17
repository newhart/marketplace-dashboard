<?php

namespace App\Filament\Widgets;

use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Illuminate\Support\Facades\DB;

class SalesChart extends ChartWidget
{
    use InteractsWithPageFilters;

    protected static ?string $heading = 'Évolution du C.A. (HT)';

    protected function getData(): array
    {
        $merchantId = $this->filters['merchant_id'] ?? null;

        if ($merchantId) {
            $subQuery = \App\Models\OrderItem::query()
                ->select(DB::raw('price * quantity as revenue, created_at'))
                ->whereHas('product', fn($q) => $q->where('user_id', $merchantId));

            $data = \Flowframe\Trend\Trend::query(
                \App\Models\OrderItem::query()->fromSub($subQuery, 'sub')
            )
                ->dateColumn('created_at')
                ->between(
                    start: now()->startOfYear(),
                    end: now()->endOfYear(),
                )
                ->perMonth()
                ->sum('revenue');
        } else {
            $data = \Flowframe\Trend\Trend::model(\App\Models\Order::class)
                ->between(
                    start: now()->startOfYear(),
                    end: now()->endOfYear(),
                )
                ->perMonth()
                ->sum('total_amount');
        }

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

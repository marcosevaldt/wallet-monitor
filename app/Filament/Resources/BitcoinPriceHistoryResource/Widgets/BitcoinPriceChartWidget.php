<?php

namespace App\Filament\Resources\BitcoinPriceHistoryResource\Widgets;

use Filament\Widgets\ChartWidget;
use App\Models\BitcoinPriceHistory;
use Carbon\Carbon;

class BitcoinPriceChartWidget extends ChartWidget
{
    protected static ?string $heading = 'Histórico do Preço do Bitcoin (OHLC)';

    protected static ?int $sort = -2; // Para aparecer acima da tabela

    protected int | string | array $columnSpan = 'full';

    public ?string $filter = '30_days'; // Definir filtro padrão

    protected function getFilters(): ?array
    {
        return [
            '7_days' => 'Últimos 7 dias',
            '30_days' => 'Últimos 30 dias',
            '90_days' => 'Últimos 90 dias',
            '1_year' => 'Último 1 ano',
            'all' => 'Todo o histórico',
        ];
    }

    protected function getData(): array
    {
        $query = BitcoinPriceHistory::orderBy('timestamp', 'asc')
            ->where('currency', 'usd')
            ->where('is_daily', true);

        $now = Carbon::now();

        switch ($this->filter) {
            case '7_days':
                $query->where('timestamp', '>=', $now->subDays(7));
                break;
            case '30_days':
                $query->where('timestamp', '>=', $now->subDays(30));
                break;
            case '90_days':
                $query->where('timestamp', '>=', $now->subDays(90));
                break;
            case '1_year':
                $query->where('timestamp', '>=', $now->subYear());
                break;
            case 'all':
                break;
        }

        $data = $query->get()
            ->map(function ($record) {
                return [
                    'date' => Carbon::parse($record->timestamp)->format('d/m/Y'),
                    'open' => (float) ($record->open ?? $record->price),
                    'high' => (float) ($record->high ?? $record->price),
                    'low' => (float) ($record->low ?? $record->price),
                    'close' => (float) ($record->close ?? $record->price),
                ];
            });

        $dates = $data->pluck('date')->toArray();
        $openPrices = $data->pluck('open')->toArray();
        $highPrices = $data->pluck('high')->toArray();
        $lowPrices = $data->pluck('low')->toArray();
        $closePrices = $data->pluck('close')->toArray();

        return [
            'datasets' => [
                [
                    'label' => 'Abertura (USD)',
                    'data' => $openPrices,
                    'fill' => false,
                    'borderColor' => 'rgb(255, 99, 132)',
                    'backgroundColor' => 'rgba(255, 99, 132, 0.2)',
                    'tension' => 0.1,
                    'borderWidth' => 2,
                ],
                [
                    'label' => 'Máximo (USD)',
                    'data' => $highPrices,
                    'fill' => false,
                    'borderColor' => 'rgb(54, 162, 235)',
                    'backgroundColor' => 'rgba(54, 162, 235, 0.2)',
                    'tension' => 0.1,
                    'borderWidth' => 2,
                ],
                [
                    'label' => 'Mínimo (USD)',
                    'data' => $lowPrices,
                    'fill' => false,
                    'borderColor' => 'rgb(255, 205, 86)',
                    'backgroundColor' => 'rgba(255, 205, 86, 0.2)',
                    'tension' => 0.1,
                    'borderWidth' => 2,
                ],
                [
                    'label' => 'Fechamento (USD)',
                    'data' => $closePrices,
                    'fill' => false,
                    'borderColor' => 'rgb(75, 192, 192)',
                    'backgroundColor' => 'rgba(75, 192, 192, 0.2)',
                    'tension' => 0.1,
                    'borderWidth' => 3,
                ],
            ],
            'labels' => $dates,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}

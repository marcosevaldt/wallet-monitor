<?php

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;

class BitcoinPriceWidget extends StatsOverviewWidget
{
    protected static ?string $pollingInterval = '5m';

    protected function getStats(): array
    {
        // Buscar dados da API com cache de 5 minutos
        $data = Cache::remember('bitcoin_overview', 300, function () {
            try {
                $stats = [];
                
                // Taxa de câmbio USD
                $ratesResponse = Http::timeout(10)->get('https://blockchain.info/ticker');
                if ($ratesResponse->successful()) {
                    $rates = $ratesResponse->json();
                    if (isset($rates['USD'])) {
                        $stats['usd_price'] = $rates['USD']['last'];
                    }
                }
                
                return $stats;
            } catch (\Exception $e) {
                return [];
            }
        });

        return [
            Stat::make('Preço Bitcoin (USD)', 
                isset($data['usd_price']) ? '$' . number_format($data['usd_price'], 2) : 'N/A'
            )
                ->description('Preço atual do Bitcoin')
                ->descriptionIcon('heroicon-m-currency-dollar')
                ->color('success')
                ->chart([7, 2, 10, 3, 15, 4, 17])
                ->extraAttributes([
                    'class' => 'cursor-pointer',
                ]),
        ];
    }
} 
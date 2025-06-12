<?php

namespace App\Filament\Resources\BitcoinPriceHistoryResource\Pages;

use App\Filament\Resources\BitcoinPriceHistoryResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListBitcoinPriceHistories extends ListRecords
{
    protected static string $resource = BitcoinPriceHistoryResource::class;
    
    protected static ?string $title = 'Histórico do Preço BTC';

    protected function getHeaderActions(): array
    {
        return [
            // Removido CreateAction pois é somente leitura
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            BitcoinPriceHistoryResource\Widgets\BitcoinPriceChartWidget::class,
        ];
    }
}

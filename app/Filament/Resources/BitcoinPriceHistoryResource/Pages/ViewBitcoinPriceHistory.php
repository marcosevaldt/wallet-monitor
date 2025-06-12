<?php

namespace App\Filament\Resources\BitcoinPriceHistoryResource\Pages;

use App\Filament\Resources\BitcoinPriceHistoryResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components\TextEntry;
use Illuminate\Support\Carbon;

class ViewBitcoinPriceHistory extends ViewRecord
{
    protected static string $resource = BitcoinPriceHistoryResource::class;
    
    protected static ?string $title = 'Visualizar Histórico do Preço BTC';

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('back')
                ->label('Voltar à Lista')
                ->url(static::getResource()::getUrl('index'))
                ->color('gray')
                ->icon('heroicon-o-arrow-left'),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                TextEntry::make('timestamp')
                    ->label('Data/Hora (UTC)')
                    ->formatStateUsing(function ($state) {
                        return Carbon::parse($state)->format('d/m/Y H:i:s');
                    }),
                TextEntry::make('price')
                    ->label('Preço BTC (USD)')
                    ->formatStateUsing(function ($state) {
                        return '$' . number_format($state, 2, ',', '.');
                    })
                    ->color('success')
                    ->weight('bold'),
                TextEntry::make('currency')
                    ->label('Moeda')
                    ->formatStateUsing(function ($state) {
                        return strtoupper($state);
                    })
                    ->badge()
                    ->color('primary'),
            ]);
    }
} 
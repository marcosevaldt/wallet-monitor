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
                TextEntry::make('timestamp_br')
                    ->label('Data/Hora (Brasil)')
                    ->formatStateUsing(function () {
                        return Carbon::parse($this->record->timestamp)
                            ->setTimezone('America/Sao_Paulo')
                            ->format('d/m/Y H:i:s');
                    }),
                TextEntry::make('price')
                    ->label('Preço BTC (USD)')
                    ->formatStateUsing(function ($state) {
                        return '$' . number_format($state, 2, ',', '.');
                    })
                    ->color('success')
                    ->weight('bold'),
                TextEntry::make('volume')
                    ->label('Volume 24h')
                    ->formatStateUsing(function ($state) {
                        if (!$state) return '-';
                        if ($state >= 1000000000) {
                            return '$' . number_format($state / 1000000000, 2, ',', '.') . ' bilhões';
                        } elseif ($state >= 1000000) {
                            return '$' . number_format($state / 1000000, 2, ',', '.') . ' milhões';
                        } elseif ($state >= 1000) {
                            return '$' . number_format($state / 1000, 2, ',', '.') . ' mil';
                        }
                        return '$' . number_format($state, 2, ',', '.');
                    })
                    ->color('info'),
                TextEntry::make('market_cap')
                    ->label('Market Cap')
                    ->formatStateUsing(function ($state) {
                        if (!$state) return '-';
                        if ($state >= 1000000000000) {
                            return '$' . number_format($state / 1000000000000, 2, ',', '.') . ' trilhões';
                        } elseif ($state >= 1000000000) {
                            return '$' . number_format($state / 1000000000, 2, ',', '.') . ' bilhões';
                        } elseif ($state >= 1000000) {
                            return '$' . number_format($state / 1000000, 2, ',', '.') . ' milhões';
                        }
                        return '$' . number_format($state, 2, ',', '.');
                    })
                    ->color('warning'),
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
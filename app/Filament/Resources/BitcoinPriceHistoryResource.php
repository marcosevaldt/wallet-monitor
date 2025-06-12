<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BitcoinPriceHistoryResource\Pages;
use App\Models\BitcoinPriceHistory;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Filters\Filter;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Actions\ActionGroup;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

class BitcoinPriceHistoryResource extends Resource
{
    protected static ?string $model = BitcoinPriceHistory::class;

    protected static ?string $navigationIcon = 'heroicon-o-currency-dollar';
    protected static ?string $navigationLabel = 'Histórico do Preço BTC';
    protected static ?string $navigationGroup = 'Mercado';
    protected static ?string $slug = 'bitcoin-price-history';
    protected static ?int $navigationSort = 99;
    protected static ?string $recordTitleAttribute = 'timestamp';
    
    protected static ?string $modelLabel = 'Histórico do Preço BTC';
    protected static ?string $pluralModelLabel = 'Histórico do Preço BTC';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\DateTimePicker::make('timestamp')
                    ->label('Data/Hora (UTC)')
                    ->disabled(),
                Forms\Components\TextInput::make('close')
                    ->label('Preço de Fechamento (USD)')
                    ->prefix('$')
                    ->disabled(),
                Forms\Components\TextInput::make('open')
                    ->label('Preço de Abertura (USD)')
                    ->prefix('$')
                    ->disabled(),
                Forms\Components\TextInput::make('high')
                    ->label('Preço Máximo (USD)')
                    ->prefix('$')
                    ->disabled(),
                Forms\Components\TextInput::make('low')
                    ->label('Preço Mínimo (USD)')
                    ->prefix('$')
                    ->disabled(),
                Forms\Components\TextInput::make('currency')
                    ->label('Moeda')
                    ->disabled(),
                Forms\Components\Toggle::make('is_daily')
                    ->label('Registro Diário')
                    ->disabled(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query
                ->where('is_daily', true)
                ->orderBy('timestamp', 'desc')
            )
            ->columns([
                TextColumn::make('timestamp')
                    ->label('Data (UTC)')
                    ->formatStateUsing(function ($state) {
                        return Carbon::parse($state)->format('d/m/Y');
                    })
                    ->sortable()
                    ->searchable()
                    ->tooltip('Data em UTC (sem conversão de fuso horário)'),
                TextColumn::make('close')
                    ->label('Fechamento')
                    ->formatStateUsing(function ($state) {
                        return '$' . number_format($state, 2, ',', '.');
                    })
                    ->color('success')
                    ->weight('bold')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('open')
                    ->label('Abertura')
                    ->formatStateUsing(function ($state) {
                        return '$' . number_format($state, 2, ',', '.');
                    })
                    ->color('info')
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('high')
                    ->label('Máximo')
                    ->formatStateUsing(function ($state) {
                        return '$' . number_format($state, 2, ',', '.');
                    })
                    ->color('success')
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('low')
                    ->label('Mínimo')
                    ->formatStateUsing(function ($state) {
                        return '$' . number_format($state, 2, ',', '.');
                    })
                    ->color('danger')
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('currency')
                    ->label('Moeda')
                    ->formatStateUsing(function ($state) {
                        return strtoupper($state);
                    })
                    ->badge()
                    ->color('primary')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('currency')
                    ->label('Moeda de Cotação')
                    ->options(fn () => BitcoinPriceHistory::query()->where('is_daily', true)->distinct()->pluck('currency', 'currency')->toArray())
                    ->indicateUsing(function (array $data): ?string {
                        if ($data['value'] ?? null) {
                            return 'Moeda: ' . strtoupper($data['value']);
                        }
                        return null;
                    }),
                
                Tables\Filters\Filter::make('price_range')
                    ->label('Faixa de Preço de Fechamento (USD)')
                    ->form([
                        Forms\Components\Grid::make(6)
                            ->schema([
                                Forms\Components\TextInput::make('price_min')
                                    ->label('Preço Mínimo de Fechamento (USD)')
                                    ->numeric()
                                    ->step(0.01)
                                    ->placeholder('Ex: 50000')
                                    ->helperText('Preço mínimo de fechamento em dólares')
                                    ->columnSpan(3),
                                Forms\Components\TextInput::make('price_max')
                                    ->label('Preço Máximo de Fechamento (USD)')
                                    ->numeric()
                                    ->step(0.01)
                                    ->placeholder('Ex: 100000')
                                    ->helperText('Preço máximo de fechamento em dólares')
                                    ->columnSpan(3),
                            ]),
                    ])
                    ->query(function (Builder $query, array $data) {
                        return $query
                            ->when(
                                $data['price_min'],
                                fn (Builder $query, $value) => $query->where('close', '>=', $value),
                            )
                            ->when(
                                $data['price_max'],
                                fn (Builder $query, $value) => $query->where('close', '<=', $value),
                            );
                    })
                    ->indicateUsing(function (array $data): ?string {
                        $indicators = [];
                        if ($data['price_min'] ?? null) {
                            $indicators[] = 'Min: $' . number_format($data['price_min'], 2);
                        }
                        if ($data['price_max'] ?? null) {
                            $indicators[] = 'Max: $' . number_format($data['price_max'], 2);
                        }
                        return $indicators ? 'Preço de Fechamento: ' . implode(' - ', $indicators) : null;
                    }),
            ])
            ->defaultSort('timestamp', 'desc')
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make()
                        ->label('Ver Detalhes')
                        ->icon('heroicon-o-eye'),
                ]),
            ])
            ->bulkActions([])
            ->striped()
            ->paginated([10, 25, 50, 100]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBitcoinPriceHistories::route('/'),
            'view' => Pages\ViewBitcoinPriceHistory::route('/{record}'),
        ];
    }
}

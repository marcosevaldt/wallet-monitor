<?php

namespace App\Filament\Resources\WalletResource\RelationManagers;

use Filament\Forms;
use Filament\Tables;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Resources\RelationManagers\RelationManager;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class TransactionsRelationManager extends RelationManager
{
    protected static string $relationship = 'transactions';

    protected static ?string $title = 'Transações';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('tx_hash')
            ->modifyQueryUsing(fn (Builder $query) => $query
                ->select(['id', 'tx_hash', 'value', 'type', 'address', 'block_height', 'block_time', 'created_at'])
                ->whereNotNull('block_time')
                ->orderBy('block_time', 'desc')
                ->limit(1000)
            )
            ->columns([
                Tables\Columns\TextColumn::make('tx_hash')
                    ->label('Hash')
                    ->searchable()
                    ->copyable()
                    ->copyMessage('Hash copiado!')
                    ->copyMessageDuration(1500)
                    ->formatStateUsing(function ($state) {
                        if (strlen($state) > 16) {
                            return substr($state, 0, 8) . '...' . substr($state, -8);
                        }
                        return $state;
                    })
                    ->tooltip(function (Tables\Columns\TextColumn $column): ?string {
                        $state = $column->getState();
                        if (strlen($state) > 16) {
                            // Quebrar o hash em linhas para melhor visualização
                            $chunkSize = 32; // 32 caracteres por linha
                            $chunks = str_split($state, $chunkSize);
                            return implode("\n", $chunks);
                        }
                        return null;
                    }),
                
                Tables\Columns\TextColumn::make('formatted_value')
                    ->label('Valor')
                    ->badge()
                    ->color(fn (string $state): string => match (true) {
                        str_contains($state, '-') => 'danger',
                        default => 'success',
                    }),
                
                Tables\Columns\BadgeColumn::make('type')
                    ->label('Tipo')
                    ->colors([
                        'success' => 'receive',
                        'warning' => 'send',
                    ])
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'send' => 'Enviada',
                        'receive' => 'Recebida',
                        default => $state,
                    }),
                
                Tables\Columns\TextColumn::make('address')
                    ->label('Endereço')
                    ->searchable()
                    ->copyable()
                    ->copyMessage('Endereço copiado!')
                    ->copyMessageDuration(1500)
                    ->formatStateUsing(function ($state) {
                        if (strlen($state) > 16) {
                            return substr($state, 0, 8) . '...' . substr($state, -8);
                        }
                        return $state;
                    })
                    ->tooltip(function (Tables\Columns\TextColumn $column): ?string {
                        $state = $column->getState();
                        if (strlen($state) > 16) {
                            return $state;
                        }
                        return null;
                    }),
                
                Tables\Columns\TextColumn::make('block_height')
                    ->label('Bloco')
                    ->sortable()
                    ->toggleable(),
                
                Tables\Columns\TextColumn::make('block_time')
                    ->label('Data/Hora')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Importado em')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->options([
                        'send' => 'Enviada',
                        'receive' => 'Recebida',
                    ])
                    ->label('Tipo')
                    ->indicateUsing(function (array $data): ?string {
                        if ($data['value'] ?? null) {
                            return 'Tipo: ' . match ($data['value']) {
                                'send' => 'Enviada',
                                'receive' => 'Recebida',
                            };
                        }
                        return null;
                    }),
                
                Tables\Filters\Filter::make('value_range')
                    ->label('Faixa de Valor da Transação (BTC)')
                    ->form([
                        Forms\Components\Grid::make(6)
                            ->schema([
                                Forms\Components\TextInput::make('value_min')
                                    ->label('Valor Mínimo da Transação (BTC)')
                                    ->numeric()
                                    ->step(0.00000001)
                                    ->placeholder('Ex: 0.001')
                                    ->helperText('Valor mínimo da transação em Bitcoin')
                                    ->columnSpan(3),
                                Forms\Components\TextInput::make('value_max')
                                    ->label('Valor Máximo da Transação (BTC)')
                                    ->numeric()
                                    ->step(0.00000001)
                                    ->placeholder('Ex: 1.0')
                                    ->helperText('Valor máximo da transação em Bitcoin')
                                    ->columnSpan(3),
                            ]),
                    ])
                    ->query(function (Builder $query, array $data) {
                        return $query
                            ->when(
                                $data['value_min'],
                                fn (Builder $query, $value) => $query->where('value', '>=', $value * 100000000), // Converter BTC para satoshis
                            )
                            ->when(
                                $data['value_max'],
                                fn (Builder $query, $value) => $query->where('value', '<=', $value * 100000000), // Converter BTC para satoshis
                            );
                    })
                    ->indicateUsing(function (array $data): ?string {
                        $indicators = [];
                        if ($data['value_min'] ?? null) {
                            $indicators[] = 'Min: ' . number_format($data['value_min'], 8) . ' BTC';
                        }
                        if ($data['value_max'] ?? null) {
                            $indicators[] = 'Max: ' . number_format($data['value_max'], 8) . ' BTC';
                        }
                        return $indicators ? 'Valor da Transação: ' . implode(' - ', $indicators) : null;
                    }),
            ])
            ->headerActions([
                // Aqui você pode adicionar ações específicas para transações, se necessário
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make()
                        ->label('Ver')
                        ->modalHeading('Detalhes da Transação'),
                    Tables\Actions\DeleteAction::make()
                        ->label('Excluir'),
                ])
                ->label('Ações')
                ->icon('heroicon-m-ellipsis-vertical')
                ->color('gray')
                ->size('sm')
                ->dropdownPlacement('bottom-end'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->label('Excluir Selecionados'),
                ]),
            ])
            ->defaultSort('block_time', 'desc')
            ->paginated([10, 25, 50]);
    }
} 
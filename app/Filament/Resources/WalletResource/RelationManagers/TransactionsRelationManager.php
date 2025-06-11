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

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('tx_hash')
                    ->label('Hash da Transação')
                    ->required()
                    ->maxLength(255),
            ]);
    }

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
                    ->limit(16)
                    ->tooltip(function (Tables\Columns\TextColumn $column): ?string {
                        $state = $column->getState();
                        if (strlen($state) > 16) {
                            return $state;
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
                        'success' => 'input',
                        'warning' => 'output',
                    ])
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'input' => 'Entrada',
                        'output' => 'Saída',
                        default => $state,
                    }),
                
                Tables\Columns\TextColumn::make('address')
                    ->label('Endereço')
                    ->searchable()
                    ->copyable()
                    ->limit(16)
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
                        'input' => 'Entrada',
                        'output' => 'Saída',
                    ])
                    ->label('Tipo'),
            ])
            ->headerActions([
                // Aqui você pode adicionar ações específicas para transações, se necessário
            ])
            ->actions([
                Tables\Actions\DeleteAction::make()
                    ->label('Excluir'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->label('Excluir Selecionados'),
                ]),
            ])
            ->defaultSort('block_time', 'desc')
            ->paginated([10, 25, 50]); // Paginação mais conservadora
    }
} 
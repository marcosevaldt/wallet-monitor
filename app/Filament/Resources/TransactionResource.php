<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TransactionResource\Pages;
use App\Filament\Resources\TransactionResource\RelationManagers;
use App\Models\Transaction;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class TransactionResource extends Resource
{
    protected static ?string $model = Transaction::class;

    protected static ?string $navigationIcon = 'heroicon-o-currency-dollar';

    protected static ?string $navigationLabel = 'Transações';

    protected static ?string $modelLabel = 'Transação';

    protected static ?string $pluralModelLabel = 'Transações';

    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informações da Transação')
                    ->schema([
                        Forms\Components\Select::make('wallet_id')
                            ->relationship('wallet', 'name')
                            ->label('Carteira')
                            ->required()
                            ->searchable(),
                        
                        Forms\Components\TextInput::make('tx_hash')
                            ->label('Hash da Transação')
                            ->required()
                            ->maxLength(255),
                        
                        Forms\Components\TextInput::make('block_height')
                            ->label('Altura do Bloco')
                            ->numeric()
                            ->placeholder('Ex: 800000'),
                        
                        Forms\Components\TextInput::make('tx_index')
                            ->label('Índice da Transação')
                            ->numeric()
                            ->placeholder('Ex: 12345678'),
                        
                        Forms\Components\TextInput::make('value')
                            ->label('Valor (Satoshis)')
                            ->numeric()
                            ->required()
                            ->placeholder('Ex: 100000000'),
                        
                        Forms\Components\Select::make('type')
                            ->label('Tipo')
                            ->options([
                                'input' => 'Entrada',
                                'output' => 'Saída',
                            ])
                            ->required(),
                        
                        Forms\Components\TextInput::make('address')
                            ->label('Endereço')
                            ->maxLength(255),
                        
                        Forms\Components\DateTimePicker::make('block_time')
                            ->label('Tempo do Bloco')
                            ->displayFormat('d/m/Y H:i:s'),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query
                ->select(['id', 'wallet_id', 'tx_hash', 'value', 'type', 'address', 'block_height', 'block_time', 'created_at'])
                ->with('wallet:id,name') // Eager loading otimizado
                ->whereNotNull('block_time')
                ->orderBy('block_time', 'desc')
                ->limit(1000) // Limitar a 1000 registros para evitar problemas de memória
            )
            ->columns([
                Tables\Columns\TextColumn::make('wallet.name')
                    ->label('Carteira')
                    ->searchable()
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('tx_hash')
                    ->label('Hash')
                    ->searchable()
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
                Tables\Filters\SelectFilter::make('wallet')
                    ->relationship('wallet', 'name')
                    ->label('Carteira'),
                
                Tables\Filters\SelectFilter::make('type')
                    ->options([
                        'input' => 'Entrada',
                        'output' => 'Saída',
                    ])
                    ->label('Tipo'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->label('Ver'),
                Tables\Actions\DeleteAction::make()
                    ->label('Excluir'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->label('Excluir Selecionadas'),
                ]),
            ])
            ->defaultSort('block_time', 'desc')
            ->paginated([10, 25, 50]); // Paginação mais conservadora
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTransactions::route('/'),
            'create' => Pages\CreateTransaction::route('/create'),
            'edit' => Pages\EditTransaction::route('/{record}/edit'),
        ];
    }
}

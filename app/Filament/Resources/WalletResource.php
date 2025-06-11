<?php

namespace App\Filament\Resources;

use App\Filament\Resources\WalletResource\Pages;
use App\Filament\Resources\WalletResource\RelationManagers;
use App\Filament\Actions\ImportTransactionsAction;
use App\Models\Wallet;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class WalletResource extends Resource
{
    protected static ?string $model = Wallet::class;

    protected static ?string $navigationIcon = 'heroicon-o-wallet';

    protected static ?string $navigationLabel = 'Carteiras';

    protected static ?string $modelLabel = 'Carteira';

    protected static ?string $pluralModelLabel = 'Carteiras';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informações da Carteira')
                    ->description('Adicione uma nova carteira Bitcoin para monitoramento')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Nome da Carteira')
                            ->placeholder('Ex: Minha Carteira Principal')
                            ->required()
                            ->maxLength(255)
                            ->helperText('Digite um nome amigável para identificar esta carteira'),
                        
                        Forms\Components\TextInput::make('address')
                            ->label('Endereço Bitcoin')
                            ->placeholder('Ex: 1A1zP1eP5QGefi2DMPTfTL5SLmv7DivfNa')
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true)
                            ->helperText('Digite o endereço completo da carteira Bitcoin')
                            ->columnSpanFull(),
                        
                        Forms\Components\TextInput::make('label')
                            ->label('Rótulo (Opcional)')
                            ->placeholder('Ex: Carteira de Recebimento')
                            ->maxLength(255)
                            ->helperText('Rótulo adicional para organização (opcional)'),
                    ])
                    ->columns(2),
                
                Forms\Components\Section::make('Informações de Importação')
                    ->description('Dados de progresso da importação de transações')
                    ->schema([
                        Forms\Components\TextInput::make('balance')
                            ->label('Saldo Atual (Satoshis)')
                            ->numeric()
                            ->default(0)
                            ->disabled()
                            ->helperText('Saldo atual da carteira (será atualizado automaticamente)'),
                        
                        Forms\Components\TextInput::make('total_transactions')
                            ->label('Total de Transações')
                            ->numeric()
                            ->default(0)
                            ->disabled()
                            ->helperText('Total de transações encontradas na blockchain'),
                        
                        Forms\Components\TextInput::make('imported_transactions')
                            ->label('Transações Importadas')
                            ->numeric()
                            ->default(0)
                            ->disabled()
                            ->helperText('Quantidade de transações já importadas'),
                        
                        Forms\Components\TextInput::make('import_progress')
                            ->label('Progresso da Importação')
                            ->numeric()
                            ->default(0.0)
                            ->suffix('%')
                            ->disabled()
                            ->helperText('Progresso atual da importação'),
                        
                        Forms\Components\DateTimePicker::make('last_import_at')
                            ->label('Última Importação')
                            ->disabled()
                            ->helperText('Data e hora da última importação'),
                    ])
                    ->columns(2)
                    ->collapsible()
                    ->collapsed(),
                
                Forms\Components\Hidden::make('user_id')
                    ->default(auth()->id()),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nome')
                    ->searchable()
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('address')
                    ->label('Endereço')
                    ->searchable()
                    ->copyable()
                    ->copyMessage('Endereço copiado!')
                    ->copyMessageDuration(1500)
                    ->limit(20)
                    ->tooltip(function (Tables\Columns\TextColumn $column): ?string {
                        $state = $column->getState();
                        if (strlen($state) > 20) {
                            return $state;
                        }
                        return null;
                    }),
                
                Tables\Columns\TextColumn::make('label')
                    ->searchable(),
                
                Tables\Columns\TextColumn::make('balance')
                    ->label('Saldo (BTC)')
                    ->numeric()
                    ->sortable()
                    ->formatStateUsing(fn ($state) => number_format(($state ?? 0) / 100000000, 8))
                    ->description(fn ($record) => $record->formatted_balance)
                    ->color(fn ($state) => ($state ?? 0) > 0 ? 'success' : 'danger'),
                
                Tables\Columns\TextColumn::make('import_progress')
                    ->label('Progresso Importação')
                    ->numeric()
                    ->suffix('%')
                    ->sortable()
                    ->color(fn ($state) => match (true) {
                        $state == -2 => 'danger',
                        $state == -1 => 'danger',
                        $state == 100 => 'success',
                        $state > 0 => 'warning',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn ($state) => match (true) {
                        $state == -2 => 'Truncado',
                        $state == -1 => 'Erro',
                        default => $state,
                    })
                    ->description(fn ($record) => match (true) {
                        $record->import_progress == -2 => 'Limite da API excedido (máx. 5000 transações)',
                        $record->import_progress == -1 => 'Erro na importação',
                        default => $record->imported_transactions . '/' . $record->total_transactions,
                    }),
                
                Tables\Columns\TextColumn::make('imported_transactions')
                    ->label('Transações Importadas')
                    ->numeric()
                    ->sortable()
                    ->color('info'),
                
                Tables\Columns\TextColumn::make('total_transactions')
                    ->label('Total Transações')
                    ->numeric()
                    ->sortable()
                    ->color('gray'),
                
                Tables\Columns\TextColumn::make('send_transactions')
                    ->label('Enviadas')
                    ->numeric()
                    ->sortable()
                    ->color('danger')
                    ->description('Transações de envio'),
                
                Tables\Columns\TextColumn::make('receive_transactions')
                    ->label('Recebidas')
                    ->numeric()
                    ->sortable()
                    ->color('success')
                    ->description('Transações de recebimento'),
                
                Tables\Columns\TextColumn::make('input_transactions')
                    ->label('Inputs')
                    ->numeric()
                    ->sortable()
                    ->color('warning')
                    ->description('Transações de entrada')
                    ->toggleable(isToggledHiddenByDefault: true),
                
                Tables\Columns\TextColumn::make('output_transactions')
                    ->label('Outputs')
                    ->numeric()
                    ->sortable()
                    ->color('info')
                    ->description('Transações de saída')
                    ->toggleable(isToggledHiddenByDefault: true),
                
                Tables\Columns\TextColumn::make('last_import_at')
                    ->label('Última Importação')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->color(fn ($state) => $state ? 'success' : 'gray'),
                
                Tables\Columns\BadgeColumn::make('import_status')
                    ->label('Status Importação')
                    ->getStateUsing(function ($record) {
                        if ($record->import_progress == -2) return 'Truncado';
                        if ($record->import_progress == -1) return 'Erro';
                        if ($record->import_progress == 100) return 'Concluída';
                        if ($record->import_progress > 0) return 'Em Andamento';
                        return 'Não Iniciada';
                    })
                    ->colors([
                        'danger' => ['Truncado', 'Erro'],
                        'success' => 'Concluída',
                        'warning' => 'Em Andamento',
                        'gray' => 'Não Iniciada',
                    ]),
                
                Tables\Columns\TextColumn::make('formatted_balance')
                    ->label('Saldo')
                    ->badge()
                    ->color(fn (string $state): string => match (true) {
                        str_contains($state, '-') => 'danger',
                        str_contains($state, '0.00000000') => 'gray',
                        default => 'success',
                    })
                    ->sortable(query: function (Builder $query, string $direction): Builder {
                        return $query->orderBy('id', $direction);
                    }),
                
                Tables\Columns\TextColumn::make('transactions_count')
                    ->label('Transações')
                    ->counts('transactions')
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Criado em')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Atualizado em')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\Action::make('import_transactions')
                    ->label('Importar Transações')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Importar Transações')
                    ->modalDescription('Deseja importar todas as transações desta carteira da blockchain? A importação será executada em background e você poderá acompanhar o progresso.')
                    ->modalSubmitActionLabel('Sim, Importar')
                    ->modalCancelActionLabel('Cancelar')
                    ->action(function (Wallet $record) {
                        // Verificar se já existe um job em execução para esta carteira
                        if ($record->import_progress > 0 && $record->import_progress < 100) {
                            \Filament\Notifications\Notification::make()
                                ->title('Importação já em andamento')
                                ->body('Esta carteira já possui uma importação em progresso. Aguarde a conclusão.')
                                ->warning()
                                ->send();
                            return;
                        }
                        
                        // Verificar o limite antes de iniciar (opcional - pode ser removido se preferir)
                        $blockchainApi = new \App\Services\BlockchainApiService();
                        $totalTransactions = $blockchainApi->getTransactionCount($record->address);
                        $maxTransactions = \App\Jobs\ImportTransactionsJob::MAX_TOTAL_TRANSACTIONS;
                        
                        if ($totalTransactions > $maxTransactions) {
                            \Filament\Notifications\Notification::make()
                                ->title('Limite de transações excedido')
                                ->body("Esta carteira possui {$totalTransactions} transações, mas o sistema só pode importar até {$maxTransactions} transações. A importação será cancelada.")
                                ->warning()
                                ->send();
                            return;
                        }
                        
                        // Iniciar importação em background com delay
                        dispatch(new \App\Jobs\ImportTransactionsJob($record->id))
                            ->delay(now()->addSeconds(5)); // 5 segundos de delay
                        
                        // Notificação inicial
                        \Filament\Notifications\Notification::make()
                            ->title('Importação agendada')
                            ->body('A importação foi agendada e será iniciada em 5 segundos. Você pode acompanhar o progresso na tabela.')
                            ->success()
                            ->send();
                    }),
                Tables\Actions\Action::make('refresh_balance')
                    ->label('Atualizar Saldo')
                    ->icon('heroicon-o-arrow-path')
                    ->color('info')
                    ->action(function (Wallet $record) {
                        $blockchainApi = new \App\Services\BlockchainApiService();
                        $balance = $blockchainApi->getBalance($record->address);
                        
                        $record->update(['balance' => $balance]);
                        
                        \Filament\Notifications\Notification::make()
                            ->title('Saldo atualizado')
                            ->body('O saldo da carteira foi atualizado com sucesso.')
                            ->success()
                            ->send();
                    }),
                Tables\Actions\EditAction::make()
                    ->label('Editar'),
                Tables\Actions\DeleteAction::make()
                    ->label('Excluir'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->label('Excluir Selecionados'),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\TransactionsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListWallets::route('/'),
            'create' => Pages\CreateWallet::route('/create'),
            'edit' => Pages\EditWallet::route('/{record}/edit'),
        ];
    }
}

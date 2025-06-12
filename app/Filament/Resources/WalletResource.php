<?php

namespace App\Filament\Resources;

use App\Filament\Resources\WalletResource\Pages;
use App\Filament\Resources\WalletResource\RelationManagers;
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

    protected static ?string $navigationGroup = 'Portfólio';

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
                        
                        Forms\Components\TextInput::make('send_transactions')
                            ->label('Transações Enviadas')
                            ->numeric()
                            ->default(0)
                            ->disabled()
                            ->helperText('Quantidade de transações de envio'),
                        
                        Forms\Components\TextInput::make('receive_transactions')
                            ->label('Transações Recebidas')
                            ->numeric()
                            ->default(0)
                            ->disabled()
                            ->helperText('Quantidade de transações de recebimento'),
                        
                        Forms\Components\DateTimePicker::make('last_import_at')
                            ->label('Última Importação')
                            ->disabled()
                            ->helperText('Data e hora da última importação'),
                    ])
                    ->collapsible()
                    ->collapsed(),
                
                Forms\Components\Hidden::make('user_id')
                    ->default(auth()->id()),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['importJobs' => function ($query) {
                $query->orderBy('created_at', 'desc')->limit(1);
            }]))
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
                    })
                    ->size('sm'),
                
                Tables\Columns\TextColumn::make('label')
                    ->label('Rótulo')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                
                Tables\Columns\TextColumn::make('balance')
                    ->label('Saldo (BTC)')
                    ->numeric()
                    ->sortable()
                    ->formatStateUsing(fn ($state) => number_format(($state ?? 0) / 100000000, 8))
                    ->color(fn ($state) => ($state ?? 0) > 0 ? 'success' : 'danger'),
                
                Tables\Columns\TextColumn::make('transactions_summary')
                    ->label('Transações')
                    ->getStateUsing(function ($record) {
                        $send = $record->send_transactions ?? 0;
                        $receive = $record->receive_transactions ?? 0;
                        return "📤 {$send} | 📥 {$receive}";
                    })
                    ->description('Enviadas | Recebidas'),
                
                Tables\Columns\TextColumn::make('last_import_at')
                    ->label('Última Importação')
                    ->getStateUsing(function ($record) {
                        // Usar o relacionamento carregado via eager loading
                        $lastJob = $record->importJobs->first();
                        
                        if (!$lastJob) {
                            return 'Nunca';
                        }
                        
                        return $lastJob->created_at->diffForHumans();
                    })
                    ->color(fn ($state) => $state === 'Nunca' ? 'gray' : 'success')
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Criado em')
                    ->getStateUsing(function ($record) {
                        return $record->created_at->diffForHumans();
                    })
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('balance_status')
                    ->label('Status do Saldo')
                    ->options([
                        'with_balance' => 'Com Saldo',
                        'without_balance' => 'Sem Saldo',
                    ])
                    ->query(function (Builder $query, array $data) {
                        return match ($data['value']) {
                            'with_balance' => $query->where('balance', '>', 0),
                            'without_balance' => $query->where('balance', '<=', 0),
                            default => $query,
                        };
                    })
                    ->indicateUsing(function (array $data): ?string {
                        if ($data['value'] ?? null) {
                            return 'Saldo: ' . match ($data['value']) {
                                'with_balance' => 'Com Saldo',
                                'without_balance' => 'Sem Saldo',
                            };
                        }
                        return null;
                    }),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\Action::make('import_transactions')
                        ->label('Importar Transações')
                        ->icon('heroicon-o-arrow-down-tray')
                        ->visible(function (Wallet $record) {
                            // Só mostrar se NÃO tem transações importadas
                            return $record->imported_transactions == 0;
                        })
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
                    
                    Tables\Actions\Action::make('update_transactions')
                        ->label('Atualizar Transações')
                        ->icon('heroicon-o-arrow-path')
                        ->visible(function (Wallet $record) {
                            // Só mostrar se já tem transações importadas
                            return $record->imported_transactions > 0;
                        })
                        ->requiresConfirmation()
                        ->modalHeading('Atualizar Transações')
                        ->modalDescription('Deseja atualizar as transações desta carteira? Apenas as transações mais recentes serão importadas.')
                        ->modalSubmitActionLabel('Sim, Atualizar')
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
                            
                            // Iniciar atualização em background
                            dispatch(new \App\Jobs\UpdateTransactionsJob($record->id))
                                ->delay(now()->addSeconds(2)); // 2 segundos de delay
                            
                            // Notificação inicial
                            \Filament\Notifications\Notification::make()
                                ->title('Atualização agendada')
                                ->body('A atualização das transações foi agendada e será iniciada em 2 segundos. Apenas as transações mais recentes serão importadas.')
                                ->success()
                                ->send();
                        }),
                    
                    Tables\Actions\Action::make('refresh_balance')
                        ->label('Atualizar Saldo')
                        ->icon('heroicon-o-currency-dollar')
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
                    
                    Tables\Actions\Action::make('view_transactions')
                        ->label('Ver Transações')
                        ->icon('heroicon-o-list-bullet')
                        ->url(fn (Wallet $record): string => route('filament.admin.resources.wallets.edit', $record) . '?activeTab=transactions'),
                    
                    Tables\Actions\Action::make('wallet_details')
                        ->label('Informações Detalhadas')
                        ->icon('heroicon-o-information-circle')
                        ->url(fn (Wallet $record): string => route('filament.admin.resources.wallets.details', $record))
                        ->openUrlInNewTab(),
                    
                    Tables\Actions\EditAction::make()
                        ->label('Editar Carteira')
                        ->icon('heroicon-o-pencil-square'),
                    
                    Tables\Actions\DeleteAction::make()
                        ->label('Excluir Carteira')
                        ->icon('heroicon-o-trash')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->modalHeading('Excluir Carteira')
                        ->modalDescription('Tem certeza que deseja excluir esta carteira? Esta ação não pode ser desfeita.')
                        ->modalSubmitActionLabel('Sim, Excluir')
                        ->modalCancelActionLabel('Cancelar'),
                ])
                ->label('Ações')
                ->icon('heroicon-m-ellipsis-vertical')
                ->size('sm')
                ->dropdownPlacement('bottom-end'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->label('Excluir Selecionados'),
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->paginated([10, 25, 50]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\TransactionsRelationManager::class,
            RelationManagers\ImportJobsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListWallets::route('/'),
            'create' => Pages\CreateWallet::route('/create'),
            'edit' => Pages\EditWallet::route('/{record}/edit'),
            'details' => Pages\WalletDetails::route('/{record}/details'),
        ];
    }
}

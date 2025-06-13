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

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informações da Carteira')
                    ->description('Gerencie as informações da carteira Bitcoin')
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
                            ->disabled(function ($record) {
                                // Sempre desabilitar na edição (quando $record existe)
                                return $record !== null;
                            })
                            ->helperText(function ($record) {
                                if ($record !== null) {
                                    return 'O endereço da carteira não pode ser alterado após a criação. Para usar um endereço diferente, exclua esta carteira e crie uma nova.';
                                }
                                return 'Digite o endereço completo da carteira Bitcoin';
                            })
                            ->columnSpanFull(),
                        
                        Forms\Components\TextInput::make('label')
                            ->label('Rótulo (Opcional)')
                            ->placeholder('Ex: Carteira de Recebimento')
                            ->maxLength(255)
                            ->helperText('Rótulo adicional para organização (opcional)'),
                    ])
                    ->columns(2),
                
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
                
                Tables\Columns\TextColumn::make('formatted_balance')
                    ->label('Saldo')
                    ->badge()
                    ->color(fn (string $state): string => match (true) {
                        str_contains($state, '0.00000000') => 'danger',
                        default => 'success',
                    }),
                
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
                    Tables\Actions\Action::make('view_transactions')
                        ->label('Ver Transações')
                        ->icon('heroicon-o-list-bullet')
                        ->url(fn (Wallet $record): string => route('filament.admin.resources.wallets.edit', $record) . '?activeTab=transactions'),
                    
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
        ];
    }
}

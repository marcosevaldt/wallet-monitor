<?php

namespace App\Filament\Resources\WalletResource\Pages;

use App\Filament\Resources\WalletResource;
use App\Jobs\ImportTransactionsJob;
use App\Jobs\UpdateTransactionsJob;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Log;

class EditWallet extends EditRecord
{
    protected static string $resource = WalletResource::class;

    protected function getHeaderActions(): array
    {
        $wallet = $this->record;
        $hasImportedTransactions = $wallet->imported_transactions > 0;
        
        $actions = [];
        
        if (!$hasImportedTransactions) {
            // Se nunca foi importado, mostrar apenas o botão de importar
            $actions[] = Actions\Action::make('import_transactions')
                ->label('Importar Transações')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('success')
                ->requiresConfirmation()
                ->modalHeading('Importar Transações')
                ->modalDescription('Deseja importar todas as transações desta carteira? Esta operação pode demorar alguns minutos.')
                ->modalSubmitActionLabel('Sim, Importar')
                ->modalCancelActionLabel('Cancelar')
                ->action(function () {
                    try {
                        $wallet = $this->record;
                        
                        // Criar job de importação passando apenas o ID
                        ImportTransactionsJob::dispatch($wallet->id);
                        
                        // Notificar sucesso
                        Notification::make()
                            ->title('Importação Iniciada')
                            ->body('A importação das transações foi iniciada. Você pode acompanhar o progresso na aba "Histórico de Importações".')
                            ->success()
                            ->send();
                        
                        // Recarregar a página
                        $this->redirect(route('filament.admin.resources.wallets.edit', $wallet));
                        
                    } catch (\Exception $e) {
                        Log::error('Erro ao iniciar importação de transações', [
                            'wallet_id' => $this->record->id,
                            'error' => $e->getMessage()
                        ]);
                        
                        Notification::make()
                            ->title('Erro na Importação')
                            ->body('Ocorreu um erro ao iniciar a importação. Tente novamente.')
                            ->danger()
                            ->send();
                    }
                });
        } else {
            // Se já foi importado, mostrar apenas o botão de atualizar
            $actions[] = Actions\Action::make('update_transactions')
                ->label('Atualizar Transações')
                ->icon('heroicon-o-arrow-path')
                ->color('info')
                ->requiresConfirmation()
                ->modalHeading('Atualizar Transações')
                ->modalDescription('Deseja buscar apenas as transações mais recentes desta carteira? Esta operação é mais rápida que a importação completa.')
                ->modalSubmitActionLabel('Sim, Atualizar')
                ->modalCancelActionLabel('Cancelar')
                ->action(function () {
                    try {
                        $wallet = $this->record;
                        
                        // Criar job de atualização passando apenas o ID
                        UpdateTransactionsJob::dispatch($wallet->id);
                        
                        // Notificar sucesso
                        Notification::make()
                            ->title('Atualização Iniciada')
                            ->body('A atualização das transações foi iniciada. Você pode acompanhar o progresso na aba "Histórico de Importações".')
                            ->success()
                            ->send();
                        
                        // Recarregar a página
                        $this->redirect(route('filament.admin.resources.wallets.edit', $wallet));
                        
                    } catch (\Exception $e) {
                        Log::error('Erro ao iniciar atualização de transações', [
                            'wallet_id' => $this->record->id,
                            'error' => $e->getMessage()
                        ]);
                        
                        Notification::make()
                            ->title('Erro na Atualização')
                            ->body('Ocorreu um erro ao iniciar a atualização. Tente novamente.')
                            ->danger()
                            ->send();
                    }
                });
        }
        
        // Adicionar botão de excluir sempre
        $actions[] = Actions\DeleteAction::make()
            ->label('Excluir Carteira')
            ->requiresConfirmation()
            ->modalHeading('Excluir Carteira')
            ->modalDescription('Tem certeza que deseja excluir esta carteira? Esta ação não pode ser desfeita.')
            ->modalSubmitActionLabel('Sim, Excluir')
            ->modalCancelActionLabel('Cancelar');
        
        return $actions;
    }
}

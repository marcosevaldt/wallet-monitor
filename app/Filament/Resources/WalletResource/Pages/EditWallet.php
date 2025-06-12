<?php

namespace App\Filament\Resources\WalletResource\Pages;

use App\Filament\Resources\WalletResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditWallet extends EditRecord
{
    protected static string $resource = WalletResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('import_transactions')
                ->label('Importar Transações')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('success')
                ->visible(function () {
                    $wallet = $this->getRecord();
                    // Só mostrar se NÃO tem transações importadas
                    return $wallet->imported_transactions == 0;
                })
                ->action(function () {
                    $wallet = $this->getRecord();
                    
                    // Verificar se já está importando
                    if ($wallet->import_progress > 0 && $wallet->import_progress < 100) {
                        \Filament\Notifications\Notification::make()
                            ->title('Importação em andamento')
                            ->body('Esta carteira já está sendo importada. Aguarde a conclusão.')
                            ->warning()
                            ->send();
                        return;
                    }
                    
                    // Iniciar importação
                    try {
                        \App\Jobs\ImportTransactionsJob::dispatch($wallet->id);
                        
                        \Filament\Notifications\Notification::make()
                            ->title('Importação iniciada')
                            ->body('A importação das transações foi iniciada em background. Você pode acompanhar o progresso na aba "Histórico de Importações".')
                            ->success()
                            ->send();
                            
                    } catch (\Exception $e) {
                        \Filament\Notifications\Notification::make()
                            ->title('Erro ao iniciar importação')
                            ->body('Não foi possível iniciar a importação: ' . $e->getMessage())
                            ->danger()
                            ->send();
                    }
                })
                ->requiresConfirmation()
                ->modalHeading('Importar Transações')
                ->modalDescription('Tem certeza que deseja importar as transações desta carteira? Esta operação pode demorar alguns minutos.')
                ->modalSubmitActionLabel('Sim, Importar')
                ->modalCancelActionLabel('Cancelar'),
            
            Actions\Action::make('update_transactions')
                ->label('Atualizar Transações')
                ->icon('heroicon-o-arrow-path')
                ->color('info')
                ->visible(function () {
                    $wallet = $this->getRecord();
                    // Só mostrar se já tem transações importadas
                    return $wallet->imported_transactions > 0;
                })
                ->action(function () {
                    $wallet = $this->getRecord();
                    
                    // Verificar se já está importando
                    if ($wallet->import_progress > 0 && $wallet->import_progress < 100) {
                        \Filament\Notifications\Notification::make()
                            ->title('Importação em andamento')
                            ->body('Esta carteira já está sendo importada. Aguarde a conclusão.')
                            ->warning()
                            ->send();
                        return;
                    }
                    
                    // Iniciar atualização
                    try {
                        \App\Jobs\UpdateTransactionsJob::dispatch($wallet->id);
                        
                        \Filament\Notifications\Notification::make()
                            ->title('Atualização iniciada')
                            ->body('A atualização das transações foi iniciada em background. Apenas as transações mais recentes serão importadas. Acompanhe o progresso na aba "Histórico de Importações".')
                            ->success()
                            ->send();
                            
                    } catch (\Exception $e) {
                        \Filament\Notifications\Notification::make()
                            ->title('Erro ao iniciar atualização')
                            ->body('Não foi possível iniciar a atualização: ' . $e->getMessage())
                            ->danger()
                            ->send();
                    }
                })
                ->requiresConfirmation()
                ->modalHeading('Atualizar Transações')
                ->modalDescription('Tem certeza que deseja atualizar as transações desta carteira? Apenas as transações mais recentes serão importadas.')
                ->modalSubmitActionLabel('Sim, Atualizar')
                ->modalCancelActionLabel('Cancelar'),
            
            Actions\DeleteAction::make()
                ->label('Excluir Carteira')
                ->requiresConfirmation()
                ->modalHeading('Excluir Carteira')
                ->modalDescription('Tem certeza que deseja excluir esta carteira? Esta ação não pode ser desfeita.')
                ->modalSubmitActionLabel('Sim, Excluir')
                ->modalCancelActionLabel('Cancelar'),
        ];
    }
}

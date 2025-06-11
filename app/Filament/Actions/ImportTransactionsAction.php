<?php

namespace App\Filament\Actions;

use App\Events\TransactionImportEvent;
use App\Models\Wallet;
use App\Models\Transaction;
use App\Services\BlockchainApiService;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ImportTransactionsAction
{
    public static function make(): Action
    {
        return Action::make('import_transactions')
            ->label('Importar Transações')
            ->icon('heroicon-o-arrow-down-tray')
            ->color('success')
            ->requiresConfirmation()
            ->modalHeading('Importar Transações')
            ->modalDescription('Deseja importar todas as transações desta carteira da blockchain? Esta operação pode demorar alguns minutos.')
            ->modalSubmitActionLabel('Sim, Importar')
            ->modalCancelActionLabel('Cancelar')
            ->action(function (Wallet $record) {
                $blockchainApi = new BlockchainApiService();
                
                try {
                    // Primeiro, verificar quantas transações existem
                    $initialCheck = $blockchainApi->getTransactions($record->address, 50, 0);
                    
                    if (empty($initialCheck)) {
                        Notification::make()
                            ->title('Nenhuma transação encontrada')
                            ->body('Não foram encontradas transações para este endereço.')
                            ->warning()
                            ->send();
                        return;
                    }

                    // Se há mais de 50 transações, usar sistema de eventos
                    if (count($initialCheck) >= 50) {
                        // Processar primeira página imediatamente
                        $importedCount = 0;
                        $skippedCount = 0;

                        foreach ($initialCheck as $txData) {
                            if (!isset($txData['tx_hash'])) {
                                Log::error('Transação sem tx_hash definida', ['wallet_id' => $record->id, 'txData' => $txData]);
                                continue;
                            }
                            // Processar inputs
                            foreach ($txData['inputs'] as $input) {
                                $existingTx = Transaction::where('wallet_id', $record->id)
                                    ->where('tx_hash', $txData['tx_hash'])
                                    ->where('type', 'input')
                                    ->where('address', $input['address'])
                                    ->first();

                                if ($existingTx) {
                                    $skippedCount++;
                                    continue;
                                }

                                try {
                                    Transaction::create([
                                        'wallet_id' => $record->id,
                                        'tx_hash' => $txData['tx_hash'],
                                        'block_height' => $txData['block_height'],
                                        'tx_index' => $txData['tx_index'],
                                        'value' => $input['value'],
                                        'type' => 'input',
                                        'address' => $input['address'],
                                        'raw_data' => $txData['raw_data'],
                                        'block_time' => $txData['block_time'],
                                    ]);
                                    $importedCount++;
                                } catch (\Illuminate\Database\QueryException $e) {
                                    if ($e->getCode() == 23000 && str_contains($e->getMessage(), 'transactions_unique_composite')) {
                                        $skippedCount++;
                                    } else {
                                        throw $e;
                                    }
                                }
                            }

                            // Processar outputs
                            foreach ($txData['outputs'] as $output) {
                                $existingTx = Transaction::where('wallet_id', $record->id)
                                    ->where('tx_hash', $txData['tx_hash'])
                                    ->where('type', 'output')
                                    ->where('address', $output['address'])
                                    ->first();

                                if ($existingTx) {
                                    $skippedCount++;
                                    continue;
                                }

                                try {
                                    Transaction::create([
                                        'wallet_id' => $record->id,
                                        'tx_hash' => $txData['tx_hash'],
                                        'block_height' => $txData['block_height'],
                                        'tx_index' => $txData['tx_index'],
                                        'value' => $output['value'],
                                        'type' => 'output',
                                        'address' => $output['address'],
                                        'raw_data' => $txData['raw_data'],
                                        'block_time' => $txData['block_time'],
                                    ]);
                                    $importedCount++;
                                } catch (\Illuminate\Database\QueryException $e) {
                                    if ($e->getCode() == 23000 && str_contains($e->getMessage(), 'transactions_unique_composite')) {
                                        $skippedCount++;
                                    } else {
                                        throw $e;
                                    }
                                }
                            }
                        }

                        // Disparar evento para processar as próximas páginas
                        TransactionImportEvent::dispatch(
                            $record->id,
                            $record->address,
                            1, // Próxima página
                            50,
                            100, // Estimativa de páginas
                            $importedCount,
                            $skippedCount
                        );

                        Notification::make()
                            ->title('Importação iniciada')
                            ->body("Primeira página processada: {$importedCount} importadas, {$skippedCount} puladas. As demais páginas serão processadas em background.")
                            ->success()
                            ->send();

                    } else {
                        // Processo síncrono para poucas transações
                        DB::beginTransaction();

                        $importedCount = 0;
                        $skippedCount = 0;

                        foreach ($initialCheck as $txData) {
                            if (!isset($txData['tx_hash'])) {
                                Log::error('Transação sem tx_hash definida', ['wallet_id' => $record->id, 'txData' => $txData]);
                                continue;
                            }
                            // Processar inputs
                            foreach ($txData['inputs'] as $input) {
                                $existingTx = Transaction::where('wallet_id', $record->id)
                                    ->where('tx_hash', $txData['tx_hash'])
                                    ->where('type', 'input')
                                    ->where('address', $input['address'])
                                    ->first();

                                if ($existingTx) {
                                    $skippedCount++;
                                    continue;
                                }

                                try {
                                    Transaction::create([
                                        'wallet_id' => $record->id,
                                        'tx_hash' => $txData['tx_hash'],
                                        'block_height' => $txData['block_height'],
                                        'tx_index' => $txData['tx_index'],
                                        'value' => $input['value'],
                                        'type' => 'input',
                                        'address' => $input['address'],
                                        'raw_data' => $txData['raw_data'],
                                        'block_time' => $txData['block_time'],
                                    ]);
                                    $importedCount++;
                                } catch (\Illuminate\Database\QueryException $e) {
                                    if ($e->getCode() == 23000 && str_contains($e->getMessage(), 'transactions_unique_composite')) {
                                        $skippedCount++;
                                    } else {
                                        throw $e;
                                    }
                                }
                            }

                            // Processar outputs
                            foreach ($txData['outputs'] as $output) {
                                $existingTx = Transaction::where('wallet_id', $record->id)
                                    ->where('tx_hash', $txData['tx_hash'])
                                    ->where('type', 'output')
                                    ->where('address', $output['address'])
                                    ->first();

                                if ($existingTx) {
                                    $skippedCount++;
                                    continue;
                                }

                                try {
                                    Transaction::create([
                                        'wallet_id' => $record->id,
                                        'tx_hash' => $txData['tx_hash'],
                                        'block_height' => $txData['block_height'],
                                        'tx_index' => $txData['tx_index'],
                                        'value' => $output['value'],
                                        'type' => 'output',
                                        'address' => $output['address'],
                                        'raw_data' => $txData['raw_data'],
                                        'block_time' => $txData['block_time'],
                                    ]);
                                    $importedCount++;
                                } catch (\Illuminate\Database\QueryException $e) {
                                    if ($e->getCode() == 23000 && str_contains($e->getMessage(), 'transactions_unique_composite')) {
                                        $skippedCount++;
                                    } else {
                                        throw $e;
                                    }
                                }
                            }
                        }

                        DB::commit();

                        Notification::make()
                            ->title('Importação concluída')
                            ->body("Importadas {$importedCount} transações. {$skippedCount} transações já existiam.")
                            ->success()
                            ->send();
                    }

                } catch (\Exception $e) {
                    DB::rollBack();

                    Notification::make()
                        ->title('Erro na importação')
                        ->body('Ocorreu um erro ao importar as transações: ' . $e->getMessage())
                        ->danger()
                        ->send();
                }
            });
    }
} 
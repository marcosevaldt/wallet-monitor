<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\Wallet;
use App\Models\Transaction;
use App\Services\BlockchainApiService;
use Illuminate\Support\Facades\Log;

class UpdateTransactionsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $walletId;
    public int $timeout = 3600; // 1 hora

    public function __construct(int $walletId)
    {
        $this->walletId = $walletId;
    }

    public function handle(): void
    {
        $wallet = Wallet::findOrFail($this->walletId);
        
        // Criar registro do job
        $importJob = \App\Models\ImportJob::create([
            'wallet_id' => $wallet->id,
            'job_type' => 'update',
            'status' => 'running',
            'progress' => 0,
            'started_at' => now(),
        ]);
        
        $blockchainApi = new BlockchainApiService();

        Log::info('Iniciando atualização de transações', [
            'wallet_id' => $wallet->id,
            'address' => $wallet->address
        ]);

        try {
            // Buscar a transação mais recente na base
            $lastTransaction = $wallet->transactions()
                ->whereNotNull('block_time')
                ->orderBy('block_time', 'desc')
                ->first();

            $lastBlockTime = $lastTransaction ? $lastTransaction->block_time->timestamp : 0;
            
            Log::info('Última transação encontrada', [
                'wallet_id' => $wallet->id,
                'last_block_time' => $lastBlockTime,
                'last_transaction_hash' => $lastTransaction?->tx_hash
            ]);

            // Buscar transações mais recentes que a última importada
            $newTransactions = $blockchainApi->getTransactionsAfter($wallet->address, $lastBlockTime);

            if (empty($newTransactions)) {
                Log::info('Nenhuma transação nova encontrada', [
                    'wallet_id' => $wallet->id,
                    'address' => $wallet->address
                ]);

                // Atualizar o saldo mesmo sem novas transações
                $balance = $blockchainApi->getBalance($wallet->address);
                $wallet->update(['balance' => $balance]);
                
                // Marcar job como concluído
                $importJob->update([
                    'status' => 'completed',
                    'progress' => 100,
                    'completed_at' => now(),
                ]);

                return;
            }

            Log::info('Transações novas encontradas', [
                'wallet_id' => $wallet->id,
                'count' => count($newTransactions)
            ]);

            $importedCount = 0;
            $sendCount = 0;
            $receiveCount = 0;

            foreach ($newTransactions as $tx) {
                try {
                    $blockchainApi->saveTransaction($wallet, $tx);
                    $importedCount++;

                    // Contar por tipo
                    if (isset($tx['type'])) {
                        if ($tx['type'] === 'send') {
                            $sendCount++;
                        } elseif ($tx['type'] === 'receive') {
                            $receiveCount++;
                        }
                    }

                } catch (\Exception $e) {
                    Log::error('Erro ao salvar transação durante atualização', [
                        'wallet_id' => $wallet->id,
                        'tx_hash' => $tx['hash'] ?? 'unknown',
                        'error' => $e->getMessage()
                    ]);
                }
            }

            // Atualizar contadores e saldo
            $totalImported = $wallet->imported_transactions + $importedCount;
            $totalSend = $wallet->send_transactions + $sendCount;
            $totalReceive = $wallet->receive_transactions + $receiveCount;
            
            $balance = $blockchainApi->getBalance($wallet->address);

            $wallet->update([
                'imported_transactions' => $totalImported,
                'send_transactions' => $totalSend,
                'receive_transactions' => $totalReceive,
                'balance' => $balance,
                'last_import_at' => now(),
            ]);
            
            // Marcar job como concluído
            $importJob->update([
                'status' => 'completed',
                'progress' => 100,
                'total_transactions' => count($newTransactions),
                'imported_transactions' => $importedCount,
                'send_transactions' => $sendCount,
                'receive_transactions' => $receiveCount,
                'completed_at' => now(),
            ]);

            Log::info('Atualização de transações concluída', [
                'wallet_id' => $wallet->id,
                'address' => $wallet->address,
                'new_transactions' => $importedCount,
                'total_imported' => $totalImported,
                'balance' => $balance,
                'balance_btc' => number_format($balance / 100000000, 8)
            ]);

        } catch (\Exception $e) {
            Log::error('Erro durante atualização de transações', [
                'wallet_id' => $wallet->id,
                'address' => $wallet->address,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            // Marcar job como falhado
            $importJob->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
                'completed_at' => now(),
            ]);

            throw $e;
        }
    }
} 
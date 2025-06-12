<?php

namespace App\Listeners;

use App\Events\TransactionImportStarted;
use App\Models\Wallet;
use App\Services\BlockchainApiService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class ProcessTransactionImport implements ShouldQueue
{
    use InteractsWithQueue;

    protected $apiService;

    public function __construct(BlockchainApiService $apiService)
    {
        $this->apiService = $apiService;
    }

    public function handle(TransactionImportStarted $event)
    {
        $wallet = $event->wallet;
        $address = $wallet->address;
        $limit = 30; // Buscar 30 transações por vez
        $offset = 0;
        $totalTransactions = $this->apiService->getTransactionCount($address);
        $importedTransactions = 0;

        // Atualizar o total de transações na carteira
        $wallet->update([
            'total_transactions' => $totalTransactions,
            'imported_transactions' => 0,
            'import_progress' => 0,
            'last_import_at' => now(),
        ]);

        Log::info('Iniciando importação paginada de transações', ['wallet_id' => $wallet->id, 'address' => $address, 'total_transactions' => $totalTransactions]);

        while ($offset < $totalTransactions) {
            try {
                Log::info('Buscando lote de transações', ['wallet_id' => $wallet->id, 'offset' => $offset, 'limit' => $limit]);
                $transactions = $this->apiService->getTransactions($address, $limit, $offset);
                if (empty($transactions)) {
                    Log::info('Nenhuma transação encontrada para importar', ['wallet_id' => $wallet->id, 'offset' => $offset]);
                    break;
                }

                Log::info('Processando lote de transações', ['wallet_id' => $wallet->id, 'offset' => $offset, 'count' => count($transactions)]);
                foreach ($transactions as $tx) {
                    $this->apiService->saveTransaction($wallet, $tx);
                    $importedTransactions++;
                }

                $offset += $limit;

                // Atualizar progresso na carteira
                $progress = $totalTransactions > 0 ? ($importedTransactions / $totalTransactions) * 100 : 0;
                $wallet->update([
                    'imported_transactions' => $importedTransactions,
                    'import_progress' => $progress,
                ]);

                // Atualizar progresso
                event(new TransactionImportStarted($wallet, $totalTransactions, $importedTransactions));

                Log::info('Lote de transações importado', ['wallet_id' => $wallet->id, 'offset' => $offset, 'imported' => $importedTransactions]);

                // Aguardar 3 segundos antes do próximo lote (reduzido de 30s para 3s)
                sleep(3);
            } catch (\Exception $e) {
                Log::error('Erro durante importação paginada de transações', ['wallet_id' => $wallet->id, 'error' => $e->getMessage(), 'offset' => $offset]);
                // Em caso de erro, aguardar 3 segundos antes de tentar novamente (reduzido de 30s para 3s)
                sleep(3);
            }
        }

        // Atualizar o progresso final
        $progress = $totalTransactions > 0 ? ($importedTransactions / $totalTransactions) * 100 : 0;
        $wallet->update([
            'imported_transactions' => $importedTransactions,
            'import_progress' => $progress,
            'last_import_at' => now(),
        ]);

        // Atualizar o saldo da carteira automaticamente após a importação
        try {
            $balance = $this->apiService->getBalance($address);
            $wallet->update(['balance' => $balance]);
            
            Log::info('Saldo da carteira atualizado após importação (Listener)', [
                'wallet_id' => $wallet->id,
                'address' => $address,
                'balance' => $balance,
                'balance_btc' => number_format($balance / 100000000, 8)
            ]);
        } catch (\Exception $balanceError) {
            Log::error('Erro ao atualizar saldo após importação (Listener)', [
                'wallet_id' => $wallet->id,
                'error' => $balanceError->getMessage()
            ]);
        }

        Log::info('Importação paginada de transações concluída', ['wallet_id' => $wallet->id, 'total_imported' => $importedTransactions]);
    }
} 
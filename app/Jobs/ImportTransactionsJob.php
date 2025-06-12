<?php

namespace App\Jobs;

use App\Models\Wallet;
use App\Services\BlockchainApiService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ImportTransactionsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    // Constantes para limites da API
    const MAX_TRANSACTIONS_PER_PAGE = 50;
    const MAX_PAGES = 100;
    const MAX_TOTAL_TRANSACTIONS = self::MAX_TRANSACTIONS_PER_PAGE * self::MAX_PAGES; // 5000
    const DELAY_BETWEEN_PAGES = 3; // segundos (reduzido de 10s para 3s)

    protected int $walletId;
    
    /**
     * Número máximo de tentativas
     */
    public $tries = 3;
    
    /**
     * Tempo máximo de execução em segundos (1 hora)
     */
    public $timeout = 3600;
    
    /**
     * Tempo de espera entre tentativas em segundos
     */
    public $backoff = [60, 300, 600]; // 1 min, 5 min, 10 min
    
    /**
     * Número máximo de jobs que podem ser executados simultaneamente
     */
    public $maxExceptions = 3;

    public function __construct(int $walletId)
    {
        $this->walletId = $walletId;
    }

    public function handle(): void
    {
        $wallet = Wallet::find($this->walletId);
        
        if (!$wallet) {
            Log::error('Carteira não encontrada para importação', ['wallet_id' => $this->walletId]);
            return;
        }

        $blockchainApi = new BlockchainApiService();
        
        try {
            $importedCount = 0;
            $page = 0;
            $limit = self::MAX_TRANSACTIONS_PER_PAGE;
            $maxPages = self::MAX_PAGES;
            $maxTransactions = self::MAX_TOTAL_TRANSACTIONS;
            $hasMoreTransactions = true;
            
            // Obter total de transações
            $totalTransactions = $blockchainApi->getTransactionCount($wallet->address);
            
            // Calcular total de páginas estimado
            $estimatedPages = ceil($totalTransactions / $limit);
            
            // Verificar se o total excede o máximo possível de importar
            if ($totalTransactions > $maxTransactions) {
                Log::warning('Importação cancelada: limite de transações excedido', [
                    'wallet_id' => $wallet->id,
                    'address' => $wallet->address,
                    'total_transactions' => $totalTransactions,
                    'max_importable' => $maxTransactions,
                    'limit_per_page' => $limit,
                    'max_pages' => $maxPages,
                    'estimated_pages' => $estimatedPages
                ]);
                
                // Marcar a carteira como truncada e parar a execução
                $wallet->update([
                    'total_transactions' => $totalTransactions,
                    'imported_transactions' => 0,
                    'import_progress' => -2, // -2 indica truncamento
                    'last_import_at' => now(),
                ]);
                
                // Notificação para o usuário
                \Filament\Notifications\Notification::make()
                    ->title('Importação cancelada - Limite excedido')
                    ->body("A carteira {$wallet->address} possui {$totalTransactions} transações, mas o sistema só pode importar até {$maxTransactions} transações (limite da API: {$limit} por página × {$maxPages} páginas). A importação foi cancelada.")
                    ->warning()
                    ->persistent()
                    ->send();
                    
                return; // Parar completamente a execução do job
            }

            // Atualizar dados iniciais da carteira
            $wallet->update([
                'total_transactions' => $totalTransactions,
                'imported_transactions' => 0,
                'import_progress' => 0,
                'last_import_at' => now(),
            ]);

            Log::info('Iniciando importação em background', [
                'wallet_id' => $wallet->id,
                'address' => $wallet->address,
                'total_transactions' => $totalTransactions,
                'estimated_pages' => $estimatedPages,
                'limit_per_page' => $limit
            ]);

            while ($hasMoreTransactions && $page < $maxPages) {
                // Delay fixo entre cada página
                if ($page > 0) {
                    Log::info('Aguardando ' . self::DELAY_BETWEEN_PAGES . 's antes da próxima página', [
                        'wallet_id' => $wallet->id,
                        'page' => $page
                    ]);
                    sleep(self::DELAY_BETWEEN_PAGES);
                }

                // Buscar transações da API
                $transactions = $blockchainApi->getTransactions($wallet->address, $limit, $page * $limit);

                if (empty($transactions)) {
                    $hasMoreTransactions = false;
                    break;
                }

                // Processar transações com delay entre cada uma
                foreach ($transactions as $index => $txData) {
                    $blockchainApi->saveTransaction($wallet, $txData);
                    
                    // Pequeno delay entre transações para não sobrecarregar o banco
                    if ($index > 0 && $index % 20 === 0) {
                        usleep(50000); // 0.05 segundo a cada 20 transações
                    }
                }

                $page++;

                // Verificar se ainda há mais transações
                if (count($transactions) < $limit) {
                    $hasMoreTransactions = false;
                }

                // Atualizar progresso na carteira usando o número real de transações
                $actualImportedCount = $wallet->transactions()->count();
                $progress = $totalTransactions > 0 ? min(100, round(($actualImportedCount / $totalTransactions) * 100)) : 0;
                
                // Contar transações por tipo
                $sendCount = $wallet->transactions()->where('type', 'send')->count();
                $receiveCount = $wallet->transactions()->where('type', 'receive')->count();
                
                $wallet->update([
                    'imported_transactions' => $actualImportedCount,
                    'import_progress' => $progress,
                    'send_transactions' => $sendCount,
                    'receive_transactions' => $receiveCount,
                ]);

                // Log de progresso a cada 5 páginas
                if ($page % 5 === 0) {
                    Log::info('Progresso da importação', [
                        'wallet_id' => $wallet->id,
                        'page' => $page,
                        'total_pages' => $estimatedPages,
                        'imported' => $actualImportedCount,
                        'total_transactions' => $totalTransactions,
                        'progress' => $progress . '%'
                    ]);
                }
            }

            // Atualizar dados finais
            $wallet->update([
                'import_progress' => 100,
                'last_import_at' => now(),
            ]);

            Log::info('Importação concluída com sucesso', [
                'wallet_id' => $wallet->id,
                'total_imported' => $actualImportedCount,
                'total_transactions' => $totalTransactions,
                'pages_processed' => $page,
                'estimated_pages' => $estimatedPages
            ]);

        } catch (\Exception $e) {
            Log::error('Erro na importação em background', [
                'wallet_id' => $wallet->id,
                'error' => $e->getMessage()
            ]);
            
            // Marcar como erro
            $wallet->update([
                'import_progress' => -1, // -1 indica erro
                'last_import_at' => now(),
            ]);
        }
    }
} 
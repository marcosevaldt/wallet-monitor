<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\BlockchainApiService;
use Illuminate\Support\Carbon;

class TestImportSpeed extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:import-speed 
                            {address : Endereço Bitcoin para testar}
                            {--pages=3 : Número de páginas para testar}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Testa a velocidade de importação com os novos delays otimizados';

    protected BlockchainApiService $apiService;

    public function __construct(BlockchainApiService $apiService)
    {
        parent::__construct();
        $this->apiService = $apiService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $address = $this->argument('address');
        $pages = (int) $this->option('pages');
        $limit = 30;

        $this->info("🚀 Testando velocidade de importação...");
        $this->info("📧 Endereço: {$address}");
        $this->info("📄 Páginas: {$pages}");
        $this->info("📊 Transações por página: {$limit}");
        $this->info("");

        $startTime = microtime(true);
        $totalTransactions = 0;

        for ($page = 0; $page < $pages; $page++) {
            $offset = $page * $limit;
            $pageStartTime = microtime(true);

            $this->info("📦 Página " . ($page + 1) . "/{$pages} (offset: {$offset})");

            try {
                $transactions = $this->apiService->getTransactions($address, $limit, $offset);
                $pageEndTime = microtime(true);
                $pageDuration = round($pageEndTime - $pageStartTime, 2);

                $count = count($transactions);
                $totalTransactions += $count;

                $this->info("✅ Transações recebidas: {$count}");
                $this->info("⏱️  Tempo da página: {$pageDuration}s");

                if ($count > 0) {
                    $firstTx = $transactions[0];
                    $this->info("🔗 Primeira transação: " . substr($firstTx['hash'], 0, 20) . "...");
                    $this->info("📅 Data: " . Carbon::createFromTimestamp($firstTx['time'])->format('d/m/Y H:i:s'));
                    $this->info("🏗️  Bloco: " . ($firstTx['block_height'] ?? 'N/A'));
                }

                $this->info("");

                // Delay entre páginas (simulando o processo real)
                if ($page < $pages - 1) {
                    $this->info("⏳ Aguardando 3 segundos antes da próxima página...");
                    sleep(3);
                    $this->info("");
                }

            } catch (\Exception $e) {
                $this->error("❌ Erro na página " . ($page + 1) . ": " . $e->getMessage());
                break;
            }
        }

        $endTime = microtime(true);
        $totalDuration = round($endTime - $startTime, 2);
        $expectedDuration = $pages * 3; // 3 segundos por página
        $actualDuration = max(0, $totalDuration - $expectedDuration); // Removendo o tempo de espera, mínimo 0

        $this->info("📊 Resultados do Teste:");
        $this->info("=========================");
        $this->info("✅ Total de transações: {$totalTransactions}");
        $this->info("⏱️  Tempo total: {$totalDuration}s");
        $this->info("⚡ Tempo de API: {$actualDuration}s");
        $this->info("📈 Velocidade: " . ($actualDuration > 0 ? round($totalTransactions / $actualDuration, 2) : "Muito rápida") . " transações/segundo");
        $this->info("");

        if ($totalDuration < ($pages * 10)) {
            $this->info("🎉 Excelente! A importação está muito mais rápida!");
        } elseif ($totalDuration < ($pages * 20)) {
            $this->info("👍 Bom! A importação está mais rápida que antes.");
        } else {
            $this->info("⚠️  Ainda pode ser otimizada mais.");
        }

        return 0;
    }
}

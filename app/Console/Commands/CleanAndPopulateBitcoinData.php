<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\BitcoinPriceService;
use App\Models\BitcoinPriceHistory;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Carbon;

class CleanAndPopulateBitcoinData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'bitcoin:clean-and-populate 
                            {--days=365 : Número de dias para buscar (padrão: 365)}
                            {--currency=usd : Moeda para buscar (padrão: usd)}
                            {--force : Forçar limpeza e população}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Limpa dados duplicados e repopula dados históricos do Bitcoin';

    protected BitcoinPriceService $bitcoinService;

    public function __construct(BitcoinPriceService $bitcoinService)
    {
        parent::__construct();
        $this->bitcoinService = $bitcoinService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $days = (int) $this->option('days');
        $currency = $this->option('currency');
        $force = $this->option('force');

        $this->info("🧹 Limpando e populando dados do Bitcoin...");
        $this->info("💰 Moeda: {$currency}");
        $this->info("📅 Dias: {$days}");

        // Verificar se deve prosseguir
        if (!$force) {
            $totalRecords = BitcoinPriceHistory::count();
            $dailyRecords = BitcoinPriceHistory::where('is_daily', true)->count();
            
            $this->warn("⚠️  Esta operação irá:");
            $this->warn("   - Remover {$totalRecords} registros existentes");
            $this->warn("   - Repopular com dados diários");
            $this->warn("   - Pode demorar alguns minutos");
            
            if (!$this->confirm('Deseja continuar?')) {
                $this->info("❌ Operação cancelada.");
                return 1;
            }
        }

        try {
            // 1. Limpar todos os registros existentes
            $this->info("🗑️  Limpando registros existentes...");
            $deletedCount = BitcoinPriceHistory::count();
            BitcoinPriceHistory::truncate();
            $this->info("✅ Removidos {$deletedCount} registros");

            // 2. Buscar dados históricos em lotes menores
            $this->info("📊 Buscando dados históricos...");
            
            $batchSize = 30; // Lotes menores para evitar rate limiting
            $batches = ceil($days / $batchSize);
            $processedDays = 0;

            for ($i = 0; $i < $batches; $i++) {
                $startDay = $i * $batchSize + 1;
                $endDay = min(($i + 1) * $batchSize, $days);
                $currentBatchSize = $endDay - $startDay + 1;

                $this->info("📦 Processando lote " . ($i + 1) . "/{$batches} (dias {$startDay}-{$endDay})...");

                try {
                    $result = $this->bitcoinService->getDailyHistoricalData($currency, $currentBatchSize);

                    if ($result['success']) {
                        $dailyData = $result['daily_data'] ?? [];
                        $processedDays += count($dailyData);
                        
                        $this->info("✅ Lote processado: " . count($dailyData) . " dias");
                        
                        if (!empty($dailyData)) {
                            $latestData = end($dailyData);
                            $this->info("📅 Última data: " . $latestData['date']);
                            $this->info("💵 Último fechamento: " . $latestData['formatted_close']);
                        }
                    } else {
                        $this->error("❌ Erro no lote " . ($i + 1) . ": " . ($result['error'] ?? 'Erro desconhecido'));
                    }

                    // Aguardar entre lotes para evitar rate limiting
                    if ($i < $batches - 1) {
                        $this->info("⏳ Aguardando 3 segundos antes do próximo lote...");
                        sleep(3);
                    }

                } catch (\Exception $e) {
                    $this->error("❌ Exceção no lote " . ($i + 1) . ": " . $e->getMessage());
                    Log::error('Erro ao processar lote de dados históricos', [
                        'batch' => $i + 1,
                        'error' => $e->getMessage()
                    ]);
                }
            }

            // 3. Estatísticas finais
            $this->info("\n📊 Estatísticas Finais:");
            $totalRecords = BitcoinPriceHistory::count();
            $dailyRecords = BitcoinPriceHistory::where('is_daily', true)->count();
            
            $this->info("🗄️  Total de registros na base: " . number_format($totalRecords, 0, ',', '.'));
            $this->info("📅 Registros diários: " . number_format($dailyRecords, 0, ',', '.'));
            $this->info("📈 Dias processados: " . number_format($processedDays, 0, ',', '.'));

            // Mostrar período coberto
            $oldestRecord = BitcoinPriceHistory::where('is_daily', true)->oldest('timestamp')->first();
            $newestRecord = BitcoinPriceHistory::where('is_daily', true)->latest('timestamp')->first();
            
            if ($oldestRecord && $newestRecord) {
                $this->info("📅 Período coberto: " . 
                    $oldestRecord->timestamp->format('d/m/Y') . " até " . 
                    $newestRecord->timestamp->format('d/m/Y'));
            }

            $this->info("✅ Limpeza e população concluídas com sucesso!");

            return 0;

        } catch (\Exception $e) {
            $this->error("❌ Erro durante a operação: " . $e->getMessage());
            Log::error('Erro durante limpeza e população de dados', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return 1;
        }
    }
}

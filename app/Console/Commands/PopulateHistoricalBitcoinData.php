<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\BitcoinPriceService;
use App\Models\BitcoinPriceHistory;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Carbon;

class PopulateHistoricalBitcoinData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'bitcoin:populate-historical 
                            {--days=365 : Número de dias para buscar (padrão: 365)}
                            {--currency=usd : Moeda para buscar (padrão: usd)}
                            {--batch=30 : Tamanho do lote para processar (padrão: 30)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Popula dados históricos do Bitcoin de forma incremental';

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
        $totalDays = (int) $this->option('days');
        $currency = $this->option('currency');
        $batchSize = (int) $this->option('batch');

        $this->info("🪙 Populando dados históricos do Bitcoin...");
        $this->info("💰 Moeda: {$currency}");
        $this->info("📅 Total de dias: {$totalDays}");
        $this->info("📦 Tamanho do lote: {$batchSize}");

        $batches = ceil($totalDays / $batchSize);
        $processedDays = 0;

        for ($i = 0; $i < $batches; $i++) {
            $startDay = $i * $batchSize + 1;
            $endDay = min(($i + 1) * $batchSize, $totalDays);
            $currentBatchSize = $endDay - $startDay + 1;

            $this->info("\n📦 Processando lote " . ($i + 1) . "/{$batches} (dias {$startDay}-{$endDay})...");

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
                    $this->info("⏳ Aguardando 2 segundos antes do próximo lote...");
                    sleep(2);
                }

            } catch (\Exception $e) {
                $this->error("❌ Exceção no lote " . ($i + 1) . ": " . $e->getMessage());
                Log::error('Erro ao processar lote de dados históricos', [
                    'batch' => $i + 1,
                    'error' => $e->getMessage()
                ]);
            }
        }

        // Estatísticas finais
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

        $this->info("✅ População de dados históricos concluída!");

        return 0;
    }
}

<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\BinanceApiService;
use App\Models\BitcoinPriceHistory;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class PopulateBitcoinDataBinance extends Command
{
    protected $signature = 'bitcoin:populate-binance 
                            {--start-date= : Data inicial (YYYY-MM-DD)}
                            {--end-date= : Data final (YYYY-MM-DD)}
                            {--symbol=BTCUSDT : Símbolo (BTCUSDT, BTCEUR, BTCBRL, etc.)}
                            {--interval=1d : Intervalo (1d, 1h, 4h, etc.)}
                            {--currency=usd : Moeda (usd, eur, brl, etc.)}
                            {--batch-size=30 : Tamanho do lote em dias}
                            {--delay=1 : Delay entre requisições em segundos}
                            {--force : Forçar execução sem confirmação}
                            {--test : Testar conectividade apenas}';

    protected $description = 'Popula dados históricos do Bitcoin usando API Binance (suporte completo a range de datas)';

    protected BinanceApiService $binanceService;

    public function __construct(BinanceApiService $binanceService)
    {
        parent::__construct();
        $this->binanceService = $binanceService;
    }

    public function handle()
    {
        $startDate = $this->option('start-date');
        $endDate = $this->option('end-date');
        $symbol = $this->option('symbol');
        $interval = $this->option('interval');
        $currency = $this->option('currency');
        $batchSize = (int) $this->option('batch-size');
        $delay = (int) $this->option('delay');
        $force = $this->option('force');
        $test = $this->option('test');

        $this->info("🪙 Populando dados históricos do Bitcoin via Binance...");
        $this->info("💰 Símbolo: {$symbol}");
        $this->info("⏰ Intervalo: {$interval}");
        $this->info("💱 Moeda: {$currency}");
        $this->info("📊 Fonte: API Binance (suporte completo a range de datas)");

        // Testar conectividade se solicitado
        if ($test) {
            return $this->testConnection();
        }

        // Determinar período de busca
        if ($startDate && $endDate) {
            $start = Carbon::parse($startDate);
            $end = Carbon::parse($endDate);
            $totalDays = $start->diffInDays($end);
            $this->info("📅 Período: {$startDate} até {$endDate} ({$totalDays} dias)");
            $this->info("📦 Tamanho do lote: {$batchSize} dias");
            $this->info("⏱️  Delay entre requisições: {$delay} segundos");
        } else {
            // Se não especificado, usar últimos 30 dias
            $end = Carbon::now();
            $start = $end->copy()->subDays(30);
            $this->info("📅 Período: Últimos 30 dias ({$start->format('Y-m-d')} até {$end->format('Y-m-d')})");
        }

        // Verificar se deve prosseguir
        if (!$force) {
            $existingRecords = BitcoinPriceHistory::where('currency', $currency)->count();
            $this->warn("⚠️  Registros existentes: {$existingRecords}");
            
            if (!$this->confirm('Deseja continuar?')) {
                $this->info("❌ Operação cancelada.");
                return 1;
            }
        }

        try {
            return $this->processDateRange($start, $end, $symbol, $interval, $currency, $batchSize, $delay);
        } catch (\Exception $e) {
            $this->error("❌ Erro durante a operação: " . $e->getMessage());
            Log::error('Erro durante população de dados via Binance', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return 1;
        }
    }

    /**
     * Testa a conectividade com a API Binance
     */
    private function testConnection(): int
    {
        $this->info("🔍 Testando conectividade com a API Binance...");

        if (!$this->binanceService->testConnection()) {
            $this->error("❌ Falha na conectividade com a API Binance");
            return 1;
        }

        $this->info("✅ Conectividade OK!");

        $serverInfo = $this->binanceService->getServerInfo();
        if ($serverInfo['success']) {
            $this->info("📊 Informações do servidor:");
            $this->info("   - Hora do servidor: " . Carbon::createFromTimestampUTC($serverInfo['server_time'] / 1000)->format('Y-m-d H:i:s'));
            $this->info("   - Timezone: " . $serverInfo['timezone']);
            $this->info("   - Símbolos disponíveis: " . $serverInfo['symbols']);
        }

        $this->info("\n📋 Símbolos disponíveis:");
        foreach ($this->binanceService->getAvailableSymbols() as $symbol => $description) {
            $this->info("   - {$symbol}: {$description}");
        }

        $this->info("\n⏰ Intervalos disponíveis:");
        foreach ($this->binanceService->getAvailableIntervals() as $interval => $description) {
            $this->info("   - {$interval}: {$description}");
        }

        return 0;
    }

    /**
     * Processa um range de datas em lotes
     */
    private function processDateRange(Carbon $start, Carbon $end, string $symbol, string $interval, string $currency, int $batchSize, int $delay): int
    {
        $totalProcessed = 0;
        $totalSkipped = 0;
        $totalErrors = 0;
        $batchNumber = 1;

        $currentStart = $start->copy();

        while ($currentStart->lte($end)) {
            $currentEnd = $currentStart->copy()->addDays($batchSize - 1);
            
            // Ajustar para não ultrapassar a data final
            if ($currentEnd->gt($end)) {
                $currentEnd = $end->copy();
            }

            $daysInBatch = $currentStart->diffInDays($currentEnd) + 1;
            
            $this->info("\n📦 Lote {$batchNumber}: {$currentStart->format('Y-m-d')} até {$currentEnd->format('Y-m-d')} ({$daysInBatch} dias)");
            
            $result = $this->processBatch($currentStart, $currentEnd, $symbol, $interval, $currency, $delay);
            
            $totalProcessed += $result['processed'];
            $totalSkipped += $result['skipped'];
            $totalErrors += $result['errors'];
            
            $batchNumber++;
            $currentStart = $currentEnd->addDay();
            
            // Delay entre lotes (exceto no último)
            if ($currentStart->lte($end)) {
                $this->info("⏱️  Aguardando {$delay} segundos antes do próximo lote...");
                sleep($delay);
            }
        }

        $this->showFinalStatistics($totalProcessed, $totalSkipped, $totalErrors);
        return 0;
    }

    /**
     * Processa um lote de datas
     */
    private function processBatch(Carbon $start, Carbon $end, string $symbol, string $interval, string $currency, int $delay): array
    {
        $processed = 0;
        $skipped = 0;
        $errors = 0;

        try {
            $result = $this->binanceService->fetchAndPersistOHLC($symbol, $start, $end, $currency, $interval);
            
            if ($result['success']) {
                $processed = $result['persisted_records'];
                $this->info("✅ Lote processado: {$processed} registros");
                
                if ($processed > 0) {
                    $this->info("📅 Período: {$result['period']['start']} até {$result['period']['end']}");
                }
            } else {
                $errors++;
                $this->error("❌ Erro no lote: " . ($result['error'] ?? 'Erro desconhecido'));
            }

        } catch (\Exception $e) {
            $errors++;
            $this->error("❌ Exceção no lote: " . $e->getMessage());
            Log::error('Erro ao processar lote via Binance', [
                'start' => $start->toDateString(),
                'end' => $end->toDateString(),
                'symbol' => $symbol,
                'error' => $e->getMessage()
            ]);
        }

        return [
            'processed' => $processed,
            'skipped' => $skipped,
            'errors' => $errors
        ];
    }

    /**
     * Exibe estatísticas finais
     */
    private function showFinalStatistics(int $processed, int $skipped, int $errors): void
    {
        $this->info("\n📊 Estatísticas Finais:");
        $totalRecords = BitcoinPriceHistory::count();
        $dailyRecords = BitcoinPriceHistory::where('is_daily', true)->count();
        
        $this->info("🗄️  Total de registros na base: " . number_format($totalRecords, 0, ',', '.'));
        $this->info("📅 Registros diários: " . number_format($dailyRecords, 0, ',', '.'));
        $this->info("✅ Novos registros processados: " . number_format($processed, 0, ',', '.'));
        $this->info("⏭️  Registros pulados (já existiam): " . number_format($skipped, 0, ',', '.'));
        
        if ($errors > 0) {
            $this->warn("⚠️  Registros com erro: " . number_format($errors, 0, ',', '.'));
        }

        // Mostrar período coberto
        $oldestRecord = BitcoinPriceHistory::where('is_daily', true)->oldest('timestamp')->first();
        $newestRecord = BitcoinPriceHistory::where('is_daily', true)->latest('timestamp')->first();
        
        if ($oldestRecord && $newestRecord) {
            $this->info("📅 Período coberto: " . 
                $oldestRecord->timestamp->format('d/m/Y') . " até " . 
                $newestRecord->timestamp->format('d/m/Y'));
            
            $daysCovered = $oldestRecord->timestamp->diffInDays($newestRecord->timestamp);
            $this->info("📈 Dias cobertos: {$daysCovered} dias");
        }

        $this->info("✅ População de dados históricos via Binance concluída!");
        $this->info("💡 Dados salvos: Open, High, Low, Close, Price");
        $this->info("🚀 Vantagens da API Binance:");
        $this->info("   - Suporte completo a range de datas");
        $this->info("   - Dados OHLC precisos");
        $this->info("   - Rate limits generosos");
        $this->info("   - Múltiplos intervalos disponíveis");
    }
} 
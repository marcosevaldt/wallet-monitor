<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\BitcoinPriceHistory;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class PopulateBitcoinHistoricalData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'bitcoin:populate-historical-data 
                            {--days=365 : Número de dias para buscar (padrão: 365)}
                            {--currency=usd : Moeda para buscar (padrão: usd)}
                            {--force : Forçar população sem confirmação}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Popula dados históricos do Bitcoin usando API de market chart';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $days = (int) $this->option('days');
        $currency = $this->option('currency');
        $force = $this->option('force');

        $this->info("🪙 Populando dados históricos do Bitcoin...");
        $this->info("💰 Moeda: {$currency}");
        $this->info("📅 Dias: {$days}");

        // Verificar se deve prosseguir
        if (!$force) {
            $existingRecords = BitcoinPriceHistory::where('is_daily', true)->count();
            $this->warn("⚠️  Registros diários existentes: {$existingRecords}");
            
            if (!$this->confirm('Deseja continuar?')) {
                $this->info("❌ Operação cancelada.");
                return 1;
            }
        }

        try {
            // Usar API OHLC que retorna dados completos (Open, High, Low, Close)
            $url = "https://api.coingecko.com/api/v3/coins/bitcoin/ohlc";
            $params = [
                'vs_currency' => $currency,
                'days' => $days
            ];

            $this->info("📊 Buscando dados OHLC da API CoinGecko...");
            $response = Http::timeout(60)->get($url, $params);

            if (!$response->successful()) {
                $this->error("❌ Erro na API: " . $response->status());
                $this->error("Resposta: " . $response->body());
                return 1;
            }

            $data = $response->json();
            
            $this->info("📈 Total de pontos de dados OHLC: " . count($data));

            if (empty($data)) {
                $this->error("❌ Nenhum dado OHLC encontrado");
                return 1;
            }

            // Processar e persistir dados OHLC
            $processedCount = 0;
            $skippedCount = 0;

            foreach ($data as $ohlcvData) {
                // Formato OHLCV: [timestamp, open, high, low, close]
                $timestamp = Carbon::createFromTimestamp($ohlcvData[0] / 1000);
                $open = $ohlcvData[1];
                $high = $ohlcvData[2];
                $low = $ohlcvData[3];
                $close = $ohlcvData[4];

                // Verificar se já existe registro para esta data
                $existingRecord = BitcoinPriceHistory::where('currency', $currency)
                    ->where('is_daily', true)
                    ->whereDate('timestamp', $timestamp->toDateString())
                    ->first();

                if ($existingRecord) {
                    $skippedCount++;
                    continue;
                }

                try {
                    BitcoinPriceHistory::create([
                        'timestamp' => $timestamp,
                        'price' => $close, // Manter compatibilidade
                        'open' => $open,
                        'high' => $high,
                        'low' => $low,
                        'close' => $close,
                        'volume' => null, // OHLC não inclui volume
                        'market_cap' => null,
                        'currency' => $currency,
                        'is_daily' => true,
                    ]);

                    $processedCount++;

                    // Mostrar progresso a cada 50 registros
                    if ($processedCount % 50 === 0) {
                        $this->info("✅ Processados: {$processedCount} registros");
                    }

                } catch (\Exception $e) {
                    $this->error("❌ Erro ao persistir registro: " . $e->getMessage());
                    Log::error('Erro ao persistir registro histórico OHLC', [
                        'timestamp' => $timestamp,
                        'open' => $open,
                        'high' => $high,
                        'low' => $low,
                        'close' => $close,
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
            $this->info("✅ Novos registros processados: " . number_format($processedCount, 0, ',', '.'));
            $this->info("⏭️  Registros pulados (já existiam): " . number_format($skippedCount, 0, ',', '.'));

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

            $this->info("✅ População de dados históricos concluída!");

            return 0;

        } catch (\Exception $e) {
            $this->error("❌ Erro durante a operação: " . $e->getMessage());
            Log::error('Erro durante população de dados históricos', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return 1;
        }
    }
}

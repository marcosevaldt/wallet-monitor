<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\BitcoinPriceService;
use App\Models\BitcoinPriceHistory;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Carbon;

class UpdateBitcoinPriceCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'bitcoin:update-price 
                            {--currency=usd : Moeda para buscar (padrão: usd)}
                            {--days=1 : Número de dias para buscar (padrão: 1)}
                            {--force : Forçar atualização mesmo com cache ativo}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Atualiza dados do preço do Bitcoin da API CoinGecko';

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
        $currency = $this->option('currency');
        $days = (int) $this->option('days');
        $force = $this->option('force');

        $this->info("🪙 Atualizando dados do Bitcoin...");
        $this->info("💰 Moeda: {$currency}");
        $this->info("📅 Período: {$days} dia(s)");

        // Verificar rate limit
        if (!$this->checkRateLimit() && !$force) {
            $this->warn("⚠️  Rate limit atingido. Aguarde antes da próxima execução.");
            $this->info("💡 Use --force para ignorar o rate limit (não recomendado)");
            return 1;
        }

        // Verificar se já temos dados recentes
        if (!$this->shouldUpdate() && !$force) {
            $this->info("✅ Dados já estão atualizados. Use --force para atualizar mesmo assim.");
            return 0;
        }

        try {
            $this->info("📊 Buscando dados diários da API CoinGecko...");
            
            $result = $this->bitcoinService->getDailyHistoricalData($currency, $days);
            
            if ($result['success']) {
                $this->displayResults($result);
                $this->updateLastUpdateTime();
                $this->info("✅ Atualização concluída com sucesso!");
                
                Log::info('Dados diários do Bitcoin atualizados com sucesso', [
                    'currency' => $currency,
                    'days' => $days,
                    'records_processed' => count($result['daily_data'] ?? [])
                ]);
                
                return 0;
            } else {
                $this->error("❌ Erro ao buscar dados: " . ($result['error'] ?? 'Erro desconhecido'));
                
                Log::error('Erro ao atualizar dados do Bitcoin', [
                    'currency' => $currency,
                    'days' => $days,
                    'error' => $result['error'] ?? 'Erro desconhecido'
                ]);
                
                return 1;
            }
            
        } catch (\Exception $e) {
            $this->error("❌ Exceção: " . $e->getMessage());
            
            Log::error('Exceção ao atualizar dados do Bitcoin', [
                'currency' => $currency,
                'days' => $days,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return 1;
        }
    }

    /**
     * Verifica se deve atualizar baseado no último update
     */
    protected function shouldUpdate(): bool
    {
        $lastUpdate = Cache::get('bitcoin_last_update');
        
        if (!$lastUpdate) {
            return true;
        }
        
        $lastUpdateTime = Carbon::parse($lastUpdate);
        $minutesSinceLastUpdate = Carbon::now()->diffInMinutes($lastUpdateTime);
        
        // Atualizar a cada 15 minutos no mínimo
        return $minutesSinceLastUpdate >= 15;
    }

    /**
     * Verifica rate limit da API
     */
    protected function checkRateLimit(): bool
    {
        $rateLimitKey = 'bitcoin_api_rate_limit';
        $lastCall = Cache::get($rateLimitKey);
        
        if (!$lastCall) {
            Cache::put($rateLimitKey, Carbon::now(), 60); // 1 minuto
            return true;
        }
        
        $lastCallTime = Carbon::parse($lastCall);
        $secondsSinceLastCall = Carbon::now()->diffInSeconds($lastCallTime);
        
        // Rate limit: máximo 1 chamada por minuto
        if ($secondsSinceLastCall < 60) {
            return false;
        }
        
        Cache::put($rateLimitKey, Carbon::now(), 60);
        return true;
    }

    /**
     * Atualiza timestamp do último update
     */
    protected function updateLastUpdateTime(): void
    {
        Cache::put('bitcoin_last_update', Carbon::now(), 60 * 60 * 24); // 24 horas
    }

    /**
     * Exibe resultados da atualização
     */
    protected function displayResults(array $result): void
    {
        $dailyData = $result['daily_data'] ?? [];
        
        $this->info("📈 Total de dias processados: " . count($dailyData));
        
        // Mostrar preço de fechamento mais recente
        if (!empty($dailyData)) {
            $latestData = end($dailyData);
            $this->info("💵 Preço de fechamento mais recente: " . $latestData['formatted_close']);
            $this->info("📅 Data: " . $latestData['date']);
            $this->info("📊 OHLC: " . $latestData['formatted_open'] . " / " . 
                       $latestData['formatted_high'] . " / " . 
                       $latestData['formatted_low'] . " / " . 
                       $latestData['formatted_close']);
        }
        
        // Mostrar estatísticas da base de dados
        $totalRecords = BitcoinPriceHistory::count();
        $dailyRecords = BitcoinPriceHistory::where('is_daily', true)->count();
        $this->info("🗄️  Total de registros na base: " . number_format($totalRecords, 0, ',', '.'));
        $this->info("📅 Registros diários: " . number_format($dailyRecords, 0, ',', '.'));
        
        // Mostrar período coberto
        $oldestRecord = BitcoinPriceHistory::where('is_daily', true)->oldest('timestamp')->first();
        $newestRecord = BitcoinPriceHistory::where('is_daily', true)->latest('timestamp')->first();
        
        if ($oldestRecord && $newestRecord) {
            $this->info("📅 Período coberto (diário): " . 
                $oldestRecord->timestamp->format('d/m/Y') . " até " . 
                $newestRecord->timestamp->format('d/m/Y'));
        }
    }
}

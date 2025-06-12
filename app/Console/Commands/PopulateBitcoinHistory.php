<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\BitcoinPriceService;
use App\Models\BitcoinPriceHistory;
use Carbon\Carbon;

class PopulateBitcoinHistory extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'bitcoin:populate-history {--days=365 : Número de dias para buscar} {--currency=usd : Moeda}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Popula a base de dados com dados históricos do Bitcoin';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $days = $this->option('days');
        $currency = $this->option('currency');
        
        $this->info("🪙 Populando histórico do Bitcoin...");
        $this->info("📅 Período: {$days} dias");
        $this->info("💰 Moeda: {$currency}");
        
        $priceService = new BitcoinPriceService();
        
        // Buscar dados históricos
        $this->info("📊 Buscando dados da API CoinGecko...");
        $data = $priceService->getHistoricalData($currency, $days);
        
        if (!$data['success']) {
            $this->error("❌ Falha ao buscar dados: " . ($data['error'] ?? 'Erro desconhecido'));
            return 1;
        }
        
        $this->info("✅ Dados obtidos com sucesso!");
        $this->info("📊 Total de pontos de preço: " . count($data['daily_data']));
        
        // Verificar quantos registros foram criados
        $totalRecords = BitcoinPriceHistory::where('currency', $currency)->count();
        $this->info("📈 Total de registros na base: {$totalRecords}");
        
        if ($totalRecords == 0) {
            $this->warn("⚠️  Nenhum registro foi criado. Verificando logs...");
            
            // Tentar criar um registro manualmente para testar
            $this->info("🧪 Testando criação manual de registro...");
            try {
                $testRecord = BitcoinPriceHistory::create([
                    'timestamp' => Carbon::now(),
                    'price' => 50000.00,
                    'volume' => 1000000000.00,
                    'market_cap' => 1000000000000.00,
                    'currency' => $currency,
                ]);
                $this->info("✅ Registro de teste criado com ID: {$testRecord->id}");
                
                // Remover o registro de teste
                $testRecord->delete();
                $this->info("🗑️  Registro de teste removido");
                
            } catch (\Exception $e) {
                $this->error("❌ Erro ao criar registro de teste: " . $e->getMessage());
            }
        }
        
        // Mostrar estatísticas
        $latestPrice = BitcoinPriceHistory::getLatestPrice($currency);
        if ($latestPrice) {
            $this->info("💰 Preço mais recente: $" . number_format($latestPrice, 2));
        }
        
        // Mostrar período coberto
        $firstRecord = BitcoinPriceHistory::where('currency', $currency)
            ->orderBy('timestamp', 'asc')
            ->first();
        $lastRecord = BitcoinPriceHistory::where('currency', $currency)
            ->orderBy('timestamp', 'desc')
            ->first();
            
        if ($firstRecord && $lastRecord) {
            $this->info("📅 Período coberto: {$firstRecord->timestamp->format('d/m/Y')} até {$lastRecord->timestamp->format('d/m/Y')}");
        }
        
        $this->info("🎯 População concluída!");
        
        return 0;
    }
}

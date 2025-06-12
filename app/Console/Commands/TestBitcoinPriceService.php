<?php

namespace App\Console\Commands;

use App\Services\BitcoinPriceService;
use Illuminate\Console\Command;

class TestBitcoinPriceService extends Command
{
    protected $signature = 'test:bitcoin-price {currency=usd} {days=30}';
    protected $description = 'Testa o serviço de preços históricos do Bitcoin';

    public function handle()
    {
        $currency = $this->argument('currency');
        $days = (int) $this->argument('days');
        
        $this->info("Testando BitcoinPriceService...");
        $this->info("Moeda: {$currency}");
        $this->info("Período: {$days} dias");
        $this->newLine();
        
        $priceService = new BitcoinPriceService();
        
        try {
            $this->info("Buscando dados históricos...");
            $data = $priceService->getHistoricalData($currency, $days);
            
            if ($data['success']) {
                $this->info("✅ Dados obtidos com sucesso!");
                $this->newLine();
                
                $prices = $data['prices'];
                $volumes = $data['volumes'];
                
                $this->info("📊 Estatísticas:");
                $this->info("  - Total de pontos de preço: " . count($prices));
                $this->info("  - Total de pontos de volume: " . count($volumes));
                
                if (!empty($prices)) {
                    $latestPrice = end($prices);
                    $firstPrice = reset($prices);
                    
                    $this->newLine();
                    $this->info("💰 Dados de Preço:");
                    $this->info("  - Preço mais recente: {$latestPrice['formatted_price']}");
                    $this->info("  - Data mais recente: {$latestPrice['date']}");
                    $this->info("  - Preço inicial: {$firstPrice['formatted_price']}");
                    $this->info("  - Data inicial: {$firstPrice['date']}");
                    
                    $priceChange = $latestPrice['price'] - $firstPrice['price'];
                    $priceChangePercent = ($firstPrice['price'] > 0) ? ($priceChange / $firstPrice['price']) * 100 : 0;
                    
                    $this->info("  - Variação: " . number_format($priceChange, 2) . " (" . number_format($priceChangePercent, 2) . "%)");
                }
                
                if (!empty($volumes)) {
                    $latestVolume = end($volumes);
                    $this->newLine();
                    $this->info("📈 Dados de Volume:");
                    $this->info("  - Volume mais recente: {$latestVolume['formatted_volume']}");
                }
                
                $this->newLine();
                $this->info("🎯 Teste concluído com sucesso!");
                
            } else {
                $this->error("❌ Erro ao obter dados: " . ($data['error'] ?? 'Erro desconhecido'));
            }
            
        } catch (\Exception $e) {
            $this->error("❌ Exceção: " . $e->getMessage());
        }
        
        return 0;
    }
} 
<?php

namespace App\Services;

use App\Models\BitcoinPriceHistory;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class BitcoinPriceService
{
    protected string $baseUrl = 'https://api.coingecko.com/api/v3/';
    protected int $cacheMinutes = 15; // Cache por 15 minutos

    /**
     * Busca dados históricos diários do Bitcoin (OHLCV) e persiste na base de dados
     */
    public function getDailyHistoricalData(string $currency = 'usd', int $days = 30): array
    {
        // A API OHLCV da CoinGecko tem limites específicos
        $maxDays = 90; // Máximo de dias para OHLCV
        $actualDays = min($days, $maxDays);
        
        $cacheKey = "bitcoin_daily_historical_{$currency}_{$actualDays}";
        
        return Cache::remember($cacheKey, $this->cacheMinutes * 60, function () use ($currency, $actualDays, $maxDays) {
            try {
                // Usar endpoint OHLCV para dados diários
                $url = $this->baseUrl . "coins/bitcoin/ohlc?vs_currency={$currency}&days={$actualDays}";
                
                $response = Http::timeout(30)->get($url);
                
                if ($response->successful()) {
                    $data = $response->json();
                    
                    // Persistir dados diários na base de dados
                    $this->persistDailyHistoricalData($data, $currency);
                    
                    return [
                        'success' => true,
                        'daily_data' => $this->formatDailyData($data),
                        'currency' => $currency,
                        'days' => $actualDays,
                        'max_days_allowed' => $maxDays,
                    ];
                }
                
                Log::error('Erro ao buscar dados diários do Bitcoin', [
                    'url' => $url,
                    'status' => $response->status(),
                    'response' => $response->body()
                ]);
                
                return ['success' => false, 'error' => 'Erro na API: ' . $response->status()];
                
            } catch (\Exception $e) {
                Log::error('Exceção ao buscar dados diários do Bitcoin', [
                    'error' => $e->getMessage()
                ]);
                
                return ['success' => false, 'error' => $e->getMessage()];
            }
        });
    }

    /**
     * Busca dados históricos do Bitcoin e persiste na base de dados (método legado)
     */
    public function getHistoricalData(string $currency = 'usd', int $days = 30): array
    {
        return $this->getDailyHistoricalData($currency, $days);
    }

    /**
     * Persiste dados históricos diários na base de dados
     */
    protected function persistDailyHistoricalData(array $data, string $currency): void
    {
        try {
            foreach ($data as $ohlcvData) {
                // Formato OHLCV: [timestamp, open, high, low, close]
                $timestamp = Carbon::createFromTimestamp($ohlcvData[0] / 1000);
                $open = $ohlcvData[1];
                $high = $ohlcvData[2];
                $low = $ohlcvData[3];
                $close = $ohlcvData[4];
                
                // Verificar se já existe registro diário para esta data
                $existingRecord = BitcoinPriceHistory::where('currency', $currency)
                    ->where('is_daily', true)
                    ->whereDate('timestamp', $timestamp->toDateString())
                    ->first();
                
                if (!$existingRecord) {
                    BitcoinPriceHistory::create([
                        'timestamp' => $timestamp,
                        'price' => $close, // Manter compatibilidade
                        'open' => $open,
                        'high' => $high,
                        'low' => $low,
                        'close' => $close,
                        'volume' => null, // OHLCV não inclui volume
                        'market_cap' => null,
                        'currency' => $currency,
                        'is_daily' => true,
                    ]);
                } else {
                    // Atualizar registro existente
                    $existingRecord->update([
                        'price' => $close,
                        'open' => $open,
                        'high' => $high,
                        'low' => $low,
                        'close' => $close,
                    ]);
                }
            }
            
            Log::info('Dados diários do Bitcoin persistidos com sucesso', [
                'currency' => $currency,
                'records_processed' => count($data)
            ]);
            
        } catch (\Exception $e) {
            Log::error('Erro ao persistir dados diários do Bitcoin', [
                'error' => $e->getMessage(),
                'currency' => $currency
            ]);
        }
    }

    /**
     * Persiste dados históricos na base de dados (método legado)
     */
    protected function persistHistoricalData(array $data, string $currency): void
    {
        $this->persistDailyHistoricalData($data, $currency);
    }

    /**
     * Busca dados para gráfico de linha (preços diários)
     */
    public function getDailyPriceChartData(string $currency = 'usd', int $days = 30): array
    {
        $data = $this->getDailyHistoricalData($currency, $days);
        
        if (!$data['success']) {
            return [];
        }
        
        return $data['daily_data'];
    }

    /**
     * Busca dados para gráfico de linha (preços) - método legado
     */
    public function getPriceChartData(string $currency = 'usd', int $days = 30): array
    {
        return $this->getDailyPriceChartData($currency, $days);
    }

    /**
     * Busca dados históricos diários da base de dados
     */
    public function getStoredDailyHistoricalData(string $currency = 'usd', int $days = 30): array
    {
        $startDate = Carbon::now()->subDays($days);
        $endDate = Carbon::now();
        
        return BitcoinPriceHistory::getDailyHistoricalData($startDate, $endDate, $currency);
    }

    /**
     * Busca dados históricos da base de dados (método legado)
     */
    public function getStoredHistoricalData(string $currency = 'usd', int $days = 30): array
    {
        return $this->getStoredDailyHistoricalData($currency, $days);
    }

    /**
     * Formata dados diários para gráfico
     */
    protected function formatDailyData(array $data): array
    {
        return array_map(function ($ohlcv) {
            $timestamp = intval($ohlcv[0] / 1000);
            return [
                'timestamp' => $timestamp,
                'date' => date('Y-m-d', $timestamp),
                'datetime' => date('Y-m-d H:i:s', $timestamp),
                'open' => $ohlcv[1],
                'high' => $ohlcv[2],
                'low' => $ohlcv[3],
                'close' => $ohlcv[4],
                'price' => $ohlcv[4], // Para compatibilidade
                'formatted_price' => '$' . number_format($ohlcv[4], 2),
                'formatted_open' => '$' . number_format($ohlcv[1], 2),
                'formatted_high' => '$' . number_format($ohlcv[2], 2),
                'formatted_low' => '$' . number_format($ohlcv[3], 2),
                'formatted_close' => '$' . number_format($ohlcv[4], 2),
            ];
        }, $data);
    }

    /**
     * Busca o preço atual do Bitcoin da base de dados
     */
    public function getCurrentPrice(string $currency = 'usd'): ?float
    {
        return BitcoinPriceHistory::getLatestPrice($currency);
    }

    /**
     * Formata dados de preços para gráfico
     */
    protected function formatPrices(array $prices): array
    {
        return array_map(function ($price) {
            return [
                'timestamp' => intval($price[0] / 1000), // Converter para segundos
                'date' => date('Y-m-d H:i:s', intval($price[0] / 1000)),
                'price' => $price[1],
                'formatted_price' => '$' . number_format($price[1], 2),
            ];
        }, $prices);
    }

    /**
     * Formata dados de volume para gráfico
     */
    protected function formatVolumes(array $volumes): array
    {
        return array_map(function ($volume) {
            return [
                'timestamp' => intval($volume[0] / 1000),
                'date' => date('Y-m-d H:i:s', intval($volume[0] / 1000)),
                'volume' => $volume[1],
                'formatted_volume' => $this->formatVolume($volume[1]),
            ];
        }, $volumes);
    }

    /**
     * Formata dados de market cap para gráfico
     */
    protected function formatMarketCaps(array $marketCaps): array
    {
        return array_map(function ($marketCap) {
            return [
                'timestamp' => intval($marketCap[0] / 1000),
                'date' => date('Y-m-d H:i:s', intval($marketCap[0] / 1000)),
                'market_cap' => $marketCap[1],
                'formatted_market_cap' => $this->formatMarketCap($marketCap[1]),
            ];
        }, $marketCaps);
    }

    /**
     * Formata volume para exibição
     */
    protected function formatVolume(float $volume): string
    {
        if ($volume >= 1e9) {
            return number_format($volume / 1e9, 2) . 'B';
        }
        if ($volume >= 1e6) {
            return number_format($volume / 1e6, 2) . 'M';
        }
        if ($volume >= 1e3) {
            return number_format($volume / 1e3, 2) . 'K';
        }
        return number_format($volume, 2);
    }

    /**
     * Formata market cap para exibição
     */
    protected function formatMarketCap(float $marketCap): string
    {
        if ($marketCap >= 1e12) {
            return '$' . number_format($marketCap / 1e12, 2) . 'T';
        }
        if ($marketCap >= 1e9) {
            return '$' . number_format($marketCap / 1e9, 2) . 'B';
        }
        if ($marketCap >= 1e6) {
            return '$' . number_format($marketCap / 1e6, 2) . 'M';
        }
        return '$' . number_format($marketCap, 2);
    }
} 
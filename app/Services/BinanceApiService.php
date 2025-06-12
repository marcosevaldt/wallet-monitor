<?php

namespace App\Services;

use App\Models\BitcoinPriceHistory;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class BinanceApiService
{
    protected string $baseUrl = 'https://api.binance.com/api/v3/';
    protected int $timeout = 60;
    protected int $retries = 3;

    /**
     * Busca dados OHLC do Bitcoin usando a API do Binance
     * Suporta range de datas específicas
     */
    public function getOHLCData(string $symbol = 'BTCUSDT', string $interval = '1d', Carbon $startTime = null, Carbon $endTime = null, int $limit = 1000): array
    {
        try {
            $params = [
                'symbol' => $symbol,
                'interval' => $interval,
                'limit' => $limit
            ];

            // Adicionar timestamps se fornecidos
            if ($startTime) {
                $params['startTime'] = $startTime->timestamp * 1000; // Binance usa milissegundos
            }
            
            if ($endTime) {
                $params['endTime'] = $endTime->timestamp * 1000;
            }

            $url = $this->baseUrl . 'klines';
            
            $response = Http::timeout($this->timeout)->get($url, $params);

            if (!$response->successful()) {
                throw new \Exception("Erro na API Binance: " . $response->status() . " - " . $response->body());
            }

            $data = $response->json();
            
            return $this->formatOHLCData($data, $symbol);

        } catch (\Exception $e) {
            Log::error('Erro ao buscar dados OHLC da Binance', [
                'symbol' => $symbol,
                'interval' => $interval,
                'startTime' => $startTime?->toISOString(),
                'endTime' => $endTime?->toISOString(),
                'error' => $e->getMessage()
            ]);
            
            throw $e;
        }
    }

    /**
     * Busca dados OHLC para um período específico
     */
    public function getOHLCForPeriod(string $symbol, Carbon $startDate, Carbon $endDate, string $interval = '1d'): array
    {
        $allData = [];
        $currentStart = $startDate->copy();
        
        // Binance tem limite de 1000 registros por requisição
        $maxRecordsPerRequest = 1000;
        
        while ($currentStart->lt($endDate)) {
            $currentEnd = $currentStart->copy()->addDays($maxRecordsPerRequest - 1);
            
            // Ajustar para não ultrapassar a data final
            if ($currentEnd->gt($endDate)) {
                $currentEnd = $endDate->copy();
            }

            try {
                $data = $this->getOHLCData($symbol, $interval, $currentStart, $currentEnd);
                $allData = array_merge($allData, $data);
                
                $currentStart = $currentEnd->addDay();
                
                // Delay para evitar rate limits
                usleep(100000); // 0.1 segundos
                
            } catch (\Exception $e) {
                Log::error('Erro ao buscar dados para período', [
                    'start' => $currentStart->toDateString(),
                    'end' => $currentEnd->toDateString(),
                    'error' => $e->getMessage()
                ]);
                
                $currentStart = $currentEnd->addDay();
            }
        }

        return $allData;
    }

    /**
     * Formata dados OHLC da Binance
     * Formato da Binance: [Open time, Open, High, Low, Close, Volume, Close time, Quote asset volume, Number of trades, Taker buy base asset volume, Taker buy quote asset volume, Ignore]
     */
    protected function formatOHLCData(array $data, string $symbol): array
    {
        return array_map(function ($item) use ($symbol) {
            return [
                'timestamp' => Carbon::createFromTimestampUTC($item[0] / 1000),
                'open' => (float) $item[1],
                'high' => (float) $item[2],
                'low' => (float) $item[3],
                'close' => (float) $item[4],
                'volume' => (float) $item[5],
                'close_time' => Carbon::createFromTimestampUTC($item[6] / 1000),
                'quote_volume' => (float) $item[7],
                'trades' => (int) $item[8],
                'taker_buy_base_volume' => (float) $item[9],
                'taker_buy_quote_volume' => (float) $item[10],
                'symbol' => $symbol
            ];
        }, $data);
    }

    /**
     * Persiste dados OHLC na base de dados
     */
    public function persistOHLCData(array $data, string $currency = 'usd'): int
    {
        $persisted = 0;
        
        foreach ($data as $item) {
            try {
                // Verificar se já existe registro para esta data
                $existingRecord = BitcoinPriceHistory::where('currency', $currency)
                    ->where('is_daily', true)
                    ->whereDate('timestamp', $item['timestamp']->toDateString())
                    ->first();

                if ($existingRecord) {
                    // Atualizar registro existente
                    $existingRecord->update([
                        'price' => $item['close'],
                        'open' => $item['open'],
                        'high' => $item['high'],
                        'low' => $item['low'],
                        'close' => $item['close'],
                    ]);
                } else {
                    // Criar novo registro
                    BitcoinPriceHistory::create([
                        'timestamp' => $item['timestamp'],
                        'price' => $item['close'],
                        'open' => $item['open'],
                        'high' => $item['high'],
                        'low' => $item['low'],
                        'close' => $item['close'],
                        'currency' => $currency,
                        'is_daily' => true,
                    ]);
                }
                
                $persisted++;
                
            } catch (\Exception $e) {
                Log::error('Erro ao persistir dados OHLC', [
                    'date' => $item['timestamp']->toDateString(),
                    'error' => $e->getMessage()
                ]);
            }
        }

        return $persisted;
    }

    /**
     * Busca dados OHLC e persiste na base de dados
     */
    public function fetchAndPersistOHLC(string $symbol, Carbon $startDate, Carbon $endDate, string $currency = 'usd', string $interval = '1d'): array
    {
        try {
            $data = $this->getOHLCForPeriod($symbol, $startDate, $endDate, $interval);
            
            if (empty($data)) {
                return [
                    'success' => false,
                    'error' => 'Nenhum dado encontrado para o período especificado'
                ];
            }

            $persisted = $this->persistOHLCData($data, $currency);
            
            return [
                'success' => true,
                'total_records' => count($data),
                'persisted_records' => $persisted,
                'period' => [
                    'start' => $startDate->format('Y-m-d'),
                    'end' => $endDate->format('Y-m-d')
                ],
                'symbol' => $symbol,
                'currency' => $currency
            ];

        } catch (\Exception $e) {
            Log::error('Erro ao buscar e persistir dados OHLC', [
                'symbol' => $symbol,
                'startDate' => $startDate->toDateString(),
                'endDate' => $endDate->toDateString(),
                'error' => $e->getMessage()
            ]);
            
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Converte símbolo para moeda (ex: BTCUSDT -> usd)
     */
    public function symbolToCurrency(string $symbol): string
    {
        $mapping = [
            'BTCUSDT' => 'usd',
            'BTCEUR' => 'eur',
            'BTCBRL' => 'brl',
            'BTCUSDC' => 'usd',
            'BTCBUSD' => 'usd',
        ];

        return $mapping[$symbol] ?? 'usd';
    }

    /**
     * Lista de símbolos disponíveis
     */
    public function getAvailableSymbols(): array
    {
        return [
            'BTCUSDT' => 'Bitcoin/USDT (USD)',
            'BTCEUR' => 'Bitcoin/EUR',
            'BTCBRL' => 'Bitcoin/BRL',
            'BTCUSDC' => 'Bitcoin/USDC (USD)',
            'BTCBUSD' => 'Bitcoin/BUSD (USD)',
        ];
    }

    /**
     * Lista de intervalos disponíveis
     */
    public function getAvailableIntervals(): array
    {
        return [
            '1m' => '1 minuto',
            '3m' => '3 minutos',
            '5m' => '5 minutos',
            '15m' => '15 minutos',
            '30m' => '30 minutos',
            '1h' => '1 hora',
            '2h' => '2 horas',
            '4h' => '4 horas',
            '6h' => '6 horas',
            '8h' => '8 horas',
            '12h' => '12 horas',
            '1d' => '1 dia',
            '3d' => '3 dias',
            '1w' => '1 semana',
            '1M' => '1 mês',
        ];
    }

    /**
     * Testa a conectividade com a API
     */
    public function testConnection(): bool
    {
        try {
            $response = Http::timeout(10)->get($this->baseUrl . 'ping');
            return $response->successful();
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Obtém informações do servidor
     */
    public function getServerInfo(): array
    {
        try {
            $response = Http::timeout(10)->get($this->baseUrl . 'exchangeInfo');
            
            if ($response->successful()) {
                $data = $response->json();
                return [
                    'success' => true,
                    'server_time' => $data['serverTime'] ?? null,
                    'timezone' => $data['timezone'] ?? null,
                    'rate_limits' => $data['rateLimits'] ?? [],
                    'symbols' => count($data['symbols'] ?? [])
                ];
            }
            
            return ['success' => false, 'error' => 'Erro ao obter informações do servidor'];
            
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
} 
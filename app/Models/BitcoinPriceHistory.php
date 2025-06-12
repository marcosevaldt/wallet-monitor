<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class BitcoinPriceHistory extends Model
{
    use HasFactory;

    protected $table = 'bitcoin_price_history';

    protected $fillable = [
        'timestamp',
        'price',
        'open',
        'high',
        'low',
        'close',
        'currency',
        'is_daily',
    ];

    protected $casts = [
        'timestamp' => 'datetime',
        'price' => 'decimal:2',
        'open' => 'decimal:2',
        'high' => 'decimal:2',
        'low' => 'decimal:2',
        'close' => 'decimal:2',
        'is_daily' => 'boolean',
    ];

    /**
     * Busca o preço de fechamento do Bitcoin em uma data específica
     */
    public static function getClosingPriceAtDate(Carbon $date, string $currency = 'usd'): ?float
    {
        $record = self::where('currency', $currency)
            ->where('is_daily', true)
            ->whereDate('timestamp', $date->toDateString())
            ->first();

        if ($record) {
            return (float) ($record->close ?? $record->price);
        }

        // Fallback: buscar o último preço do dia se não houver registro diário
        $record = self::where('currency', $currency)
            ->where('timestamp', '<=', $date->endOfDay())
            ->orderBy('timestamp', 'desc')
            ->first();

        return $record ? (float) $record->price : null;
    }

    /**
     * Busca o preço do Bitcoin em uma data específica (método legado)
     */
    public static function getPriceAtDate(Carbon $date, string $currency = 'usd'): ?float
    {
        return self::getClosingPriceAtDate($date, $currency);
    }

    /**
     * Busca o preço mais recente do Bitcoin
     */
    public static function getLatestPrice(string $currency = 'usd'): ?float
    {
        $record = self::where('currency', $currency)
            ->orderBy('timestamp', 'desc')
            ->first();

        if ($record) {
            return (float) ($record->close ?? $record->price);
        }

        return null;
    }

    /**
     * Busca dados históricos diários em um período específico
     */
    public static function getDailyHistoricalData(Carbon $startDate, Carbon $endDate, string $currency = 'usd'): array
    {
        return self::where('currency', $currency)
            ->where('is_daily', true)
            ->whereBetween('timestamp', [$startDate, $endDate])
            ->orderBy('timestamp', 'asc')
            ->get()
            ->toArray();
    }

    /**
     * Busca dados históricos em um período específico (método legado)
     */
    public static function getHistoricalData(Carbon $startDate, Carbon $endDate, string $currency = 'usd'): array
    {
        return self::getDailyHistoricalData($startDate, $endDate, $currency);
    }

    /**
     * Busca estatísticas de preço diário em um período
     */
    public static function getDailyPriceStats(Carbon $startDate, Carbon $endDate, string $currency = 'usd'): array
    {
        $data = self::where('currency', $currency)
            ->where('is_daily', true)
            ->whereBetween('timestamp', [$startDate, $endDate])
            ->get();

        if ($data->isEmpty()) {
            return [
                'min_price' => 0,
                'max_price' => 0,
                'avg_price' => 0,
                'total_records' => 0,
            ];
        }

        return [
            'min_price' => (float) $data->min('close'),
            'max_price' => (float) $data->max('close'),
            'avg_price' => (float) $data->avg('close'),
            'total_records' => $data->count(),
        ];
    }

    /**
     * Busca estatísticas de preço em um período (método legado)
     */
    public static function getPriceStats(Carbon $startDate, Carbon $endDate, string $currency = 'usd'): array
    {
        return self::getDailyPriceStats($startDate, $endDate, $currency);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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
}

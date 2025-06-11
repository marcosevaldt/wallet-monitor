<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Transaction extends Model
{
    use HasFactory;

    /**
     * Os atributos que são atribuíveis em massa.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'wallet_id',
        'tx_hash',
        'block_height',
        'tx_index',
        'value',
        'type',
        'address',
        'raw_data',
        'block_time',
    ];

    /**
     * Os atributos que devem ser convertidos para tipos nativos.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'raw_data' => 'array',
        'block_time' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Relacionamento com a carteira.
     */
    public function wallet(): BelongsTo
    {
        return $this->belongsTo(Wallet::class);
    }

    /**
     * Formata o valor em BTC.
     */
    public function getBtcValueAttribute(): float
    {
        return $this->value / 100000000; // Converter satoshis para BTC
    }

    /**
     * Formata o valor em formato legível.
     */
    public function getFormattedValueAttribute(): string
    {
        return number_format($this->btc_value, 8) . ' BTC';
    }
} 
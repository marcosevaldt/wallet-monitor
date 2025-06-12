<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Wallet extends Model
{
    use HasFactory;

    /**
     * Os atributos que são atribuíveis em massa.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'address',
        'user_id',
        'label',
        'balance',
        'total_transactions',
        'imported_transactions',
        'import_progress',
        'last_import_at',
        'send_transactions',
        'receive_transactions',
    ];

    /**
     * Os atributos que devem ser convertidos para tipos nativos.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'last_import_at' => 'datetime',
    ];

    /**
     * Relacionamento com o usuário.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relacionamento com as transações.
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    /**
     * Relacionamento com os jobs de importação.
     */
    public function importJobs(): HasMany
    {
        return $this->hasMany(ImportJob::class);
    }

    /**
     * Calcula o saldo total da carteira baseado nas transações.
     */
    public function getCalculatedBalanceAttribute(): float
    {
        $inputs = $this->transactions()->where('type', 'input')->sum('value');
        $outputs = $this->transactions()->where('type', 'output')->sum('value');
        
        return ($inputs - $outputs) / 100000000; // Converter para BTC
    }
} 
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class ImportJob extends Model
{
    use HasFactory;

    protected $fillable = [
        'wallet_id',
        'job_type',
        'status',
        'progress',
        'total_transactions',
        'imported_transactions',
        'send_transactions',
        'receive_transactions',
        'error_message',
        'started_at',
        'completed_at',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function wallet(): BelongsTo
    {
        return $this->belongsTo(Wallet::class);
    }

    public function getJobTypeLabelAttribute(): string
    {
        return match ($this->job_type) {
            'import' => 'Importação Inicial',
            'update' => 'Atualização',
            default => $this->job_type,
        };
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'pending' => 'Aguardando',
            'running' => 'Em Execução',
            'completed' => 'Concluído',
            'failed' => 'Falhou',
            default => $this->status,
        };
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'pending' => 'gray',
            'running' => 'warning',
            'completed' => 'success',
            'failed' => 'danger',
            default => 'gray',
        };
    }
}

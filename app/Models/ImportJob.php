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

    public function getDurationAttribute(): string
    {
        if (!$this->started_at) {
            return 'N/A';
        }

        $endTime = $this->completed_at ?? now();
        $duration = $this->started_at->diffInSeconds($endTime);

        if ($duration < 60) {
            return "{$duration}s";
        } elseif ($duration < 3600) {
            $minutes = floor($duration / 60);
            $seconds = $duration % 60;
            return "{$minutes}m {$seconds}s";
        } else {
            $hours = floor($duration / 3600);
            $minutes = floor(($duration % 3600) / 60);
            return "{$hours}h {$minutes}m";
        }
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

    public function isRunning(): bool
    {
        return $this->status === 'running';
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }
}

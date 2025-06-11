<?php

namespace App\Events;

use App\Models\Wallet;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithBroadcasting;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TransactionImportStarted implements ShouldBroadcast
{
    use Dispatchable, InteractsWithBroadcasting, SerializesModels;

    public $wallet;
    public $totalTransactions;
    public $importedTransactions;

    public function __construct(Wallet $wallet, int $totalTransactions = 0, int $importedTransactions = 0)
    {
        $this->wallet = $wallet;
        $this->totalTransactions = $totalTransactions;
        $this->importedTransactions = $importedTransactions;
    }

    public function broadcastOn()
    {
        return new PrivateChannel('user.' . auth()->id());
    }

    public function broadcastWith()
    {
        return [
            'wallet_id' => $this->wallet->id,
            'address' => $this->wallet->address,
            'total_transactions' => $this->totalTransactions,
            'imported_transactions' => $this->importedTransactions,
            'progress' => $this->totalTransactions > 0 ? ($this->importedTransactions / $this->totalTransactions) * 100 : 0,
        ];
    }
} 
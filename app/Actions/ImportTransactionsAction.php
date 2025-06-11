<?php

namespace App\Actions;

use App\Events\TransactionImportStarted;
use App\Models\Wallet;
use App\Services\BlockchainApiService;
use Illuminate\Support\Facades\Log;

class ImportTransactionsAction
{
    public function __construct(
        protected BlockchainApiService $apiService
    ) {}

    public function execute(Wallet $wallet): void
    {
        Log::info('Iniciando importação de transações para a carteira', ['wallet_id' => $wallet->id, 'address' => $wallet->address]);

        // Disparar o evento para iniciar a importação paginada
        event(new TransactionImportStarted($wallet));
    }
} 
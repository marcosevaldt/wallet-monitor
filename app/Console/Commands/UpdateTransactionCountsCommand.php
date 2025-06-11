<?php

namespace App\Console\Commands;

use App\Models\Wallet;
use Illuminate\Console\Command;

class UpdateTransactionCountsCommand extends Command
{
    protected $signature = 'wallets:update-transaction-counts';
    protected $description = 'Atualiza os contadores de transações de todas as carteiras';

    public function handle()
    {
        $this->info('Atualizando contadores de transações...');
        
        $wallets = Wallet::all();
        $bar = $this->output->createProgressBar($wallets->count());
        
        foreach ($wallets as $wallet) {
            // Contar transações por tipo
            $sendCount = $wallet->transactions()->where('type', 'send')->count();
            $receiveCount = $wallet->transactions()->where('type', 'receive')->count();
            $inputCount = $wallet->transactions()->where('type', 'input')->count();
            $outputCount = $wallet->transactions()->where('type', 'output')->count();
            $totalImported = $wallet->transactions()->count();
            
            // Atualizar carteira
            $wallet->update([
                'imported_transactions' => $totalImported,
                'send_transactions' => $sendCount,
                'receive_transactions' => $receiveCount,
                'input_transactions' => $inputCount,
                'output_transactions' => $outputCount,
            ]);
            
            $bar->advance();
        }
        
        $bar->finish();
        $this->newLine();
        $this->info('Contadores atualizados com sucesso!');
        
        return 0;
    }
} 
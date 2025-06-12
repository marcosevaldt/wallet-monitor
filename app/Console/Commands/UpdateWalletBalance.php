<?php

namespace App\Console\Commands;

use App\Models\Wallet;
use App\Services\BlockchainApiService;
use Illuminate\Console\Command;

class UpdateWalletBalance extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'wallet:update-balance {wallet_id? : ID da carteira (opcional)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Atualiza o saldo de uma carteira ou todas as carteiras';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $walletId = $this->argument('wallet_id');
        $api = new BlockchainApiService();
        
        if ($walletId) {
            $wallet = Wallet::find($walletId);
            if (!$wallet) {
                $this->error("Carteira com ID {$walletId} não encontrada!");
                return 1;
            }
            
            $this->updateWalletBalance($wallet, $api);
        } else {
            $wallets = Wallet::all();
            $this->info("Atualizando saldo de {$wallets->count()} carteiras...");
            
            $bar = $this->output->createProgressBar($wallets->count());
            
            foreach ($wallets as $wallet) {
                $this->updateWalletBalance($wallet, $api);
                $bar->advance();
            }
            
            $bar->finish();
            $this->newLine();
            $this->info('Saldo de todas as carteiras atualizado!');
        }
        
        return 0;
    }
    
    private function updateWalletBalance(Wallet $wallet, BlockchainApiService $api)
    {
        $oldBalance = $wallet->balance;
        $newBalance = $api->getBalance($wallet->address);
        
        $wallet->update(['balance' => $newBalance]);
        
        $this->line("Carteira {$wallet->name} ({$wallet->address}):");
        $this->line("  Saldo anterior: " . number_format($oldBalance / 100000000, 8) . " BTC");
        $this->line("  Saldo atual: " . number_format($newBalance / 100000000, 8) . " BTC");
        $this->line("");
    }
}

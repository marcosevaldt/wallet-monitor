<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Wallet;
use App\Jobs\UpdateTransactionsJob;

class UpdateWalletTransactions extends Command
{
    protected $signature = 'wallet:update-transactions {wallet_id? : ID da carteira específica} {--all : Atualizar todas as carteiras}';
    protected $description = 'Atualiza transações de carteiras Bitcoin';

    public function handle()
    {
        $walletId = $this->argument('wallet_id');
        $updateAll = $this->option('all');

        if ($updateAll) {
            $this->info('Atualizando transações de todas as carteiras...');
            
            $wallets = Wallet::where('imported_transactions', '>', 0)->get();
            
            if ($wallets->isEmpty()) {
                $this->warn('Nenhuma carteira com transações importadas encontrada.');
                return;
            }

            $this->info("Encontradas {$wallets->count()} carteiras para atualizar.");
            
            $bar = $this->output->createProgressBar($wallets->count());
            $bar->start();

            foreach ($wallets as $wallet) {
                UpdateTransactionsJob::dispatch($wallet->id);
                $bar->advance();
            }

            $bar->finish();
            $this->newLine();
            $this->info('Jobs de atualização enviados para todas as carteiras!');
            
        } elseif ($walletId) {
            $wallet = Wallet::find($walletId);
            
            if (!$wallet) {
                $this->error("Carteira com ID {$walletId} não encontrada.");
                return 1;
            }

            if ($wallet->imported_transactions == 0) {
                $this->warn("Carteira {$wallet->name} não possui transações importadas. Execute a importação completa primeiro.");
                return 1;
            }

            $this->info("Atualizando transações da carteira: {$wallet->name} ({$wallet->address})");
            
            try {
                UpdateTransactionsJob::dispatch($wallet->id);
                $this->info('Job de atualização enviado com sucesso!');
            } catch (\Exception $e) {
                $this->error('Erro ao enviar job de atualização: ' . $e->getMessage());
                return 1;
            }
            
        } else {
            $this->error('Especifique um ID de carteira ou use a opção --all para atualizar todas as carteiras.');
            $this->info('Exemplos:');
            $this->info('  php artisan wallet:update-transactions 1');
            $this->info('  php artisan wallet:update-transactions --all');
            return 1;
        }

        return 0;
    }
} 
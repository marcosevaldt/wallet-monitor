<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Wallet;
use App\Models\BitcoinPriceHistory;
use Carbon\Carbon;

class CalculateWalletROI extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'wallet:calculate-roi {wallet_id? : ID da carteira específica} {--all : Calcular ROI de todas as carteiras}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Calcula o ROI das carteiras baseado no preço histórico do Bitcoin';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $walletId = $this->argument('wallet_id');
        $allWallets = $this->option('all');
        
        $this->info("📊 Calculando ROI das carteiras...");
        
        if ($walletId) {
            $wallets = Wallet::where('id', $walletId)->get();
        } elseif ($allWallets) {
            $wallets = Wallet::all();
        } else {
            $this->error("❌ Especifique um wallet_id ou use --all");
            return 1;
        }
        
        if ($wallets->isEmpty()) {
            $this->error("❌ Nenhuma carteira encontrada");
            return 1;
        }
        
        $this->info("📈 Processando " . $wallets->count() . " carteira(s)...");
        
        $headers = ['ID', 'Endereço', 'Saldo BTC', 'Preço Compra', 'Preço Atual', 'ROI %', 'Valor Atual'];
        $rows = [];
        
        foreach ($wallets as $wallet) {
            $roi = $this->calculateWalletROI($wallet);
            
            if ($roi) {
                $rows[] = [
                    $wallet->id,
                    substr($wallet->address, 0, 20) . '...',
                    number_format($wallet->balance, 8),
                    '$' . number_format($roi['purchase_price'], 2),
                    '$' . number_format($roi['current_price'], 2),
                    number_format($roi['roi_percent'], 2) . '%',
                    '$' . number_format($roi['current_value'], 2),
                ];
            } else {
                $rows[] = [
                    $wallet->id,
                    substr($wallet->address, 0, 20) . '...',
                    number_format($wallet->balance, 8),
                    'N/A',
                    'N/A',
                    'N/A',
                    'N/A',
                ];
            }
        }
        
        $this->table($headers, $rows);
        
        // Resumo geral
        $this->info("\n📊 Resumo Geral:");
        $totalROI = $this->calculateTotalROI($wallets);
        
        if ($totalROI) {
            $this->info("💰 Valor total investido: $" . number_format($totalROI['total_invested'], 2));
            $this->info("💵 Valor total atual: $" . number_format($totalROI['total_current'], 2));
            $this->info("📈 ROI total: " . number_format($totalROI['total_roi_percent'], 2) . "%");
            $this->info("💸 Lucro/Prejuízo: $" . number_format($totalROI['total_profit'], 2));
        }
        
        return 0;
    }
    
    protected function calculateWalletROI(Wallet $wallet): ?array
    {
        // Buscar a primeira transação da carteira para determinar o preço de compra
        $firstTransaction = $wallet->transactions()
            ->orderBy('block_time', 'asc')
            ->first();
            
        if (!$firstTransaction) {
            return null;
        }
        
        $purchaseDate = Carbon::parse($firstTransaction->block_time);
        $currentDate = Carbon::now();
        
        // Buscar preço do Bitcoin na data de compra
        $purchasePrice = BitcoinPriceHistory::getPriceAtDate($purchaseDate);
        $currentPrice = BitcoinPriceHistory::getLatestPrice();
        
        if (!$purchasePrice || !$currentPrice) {
            return null;
        }
        
        $currentValue = $wallet->balance * $currentPrice;
        $purchaseValue = $wallet->balance * $purchasePrice;
        $roiPercent = ($purchasePrice > 0) ? (($currentPrice - $purchasePrice) / $purchasePrice) * 100 : 0;
        
        return [
            'purchase_price' => $purchasePrice,
            'current_price' => $currentPrice,
            'purchase_value' => $purchaseValue,
            'current_value' => $currentValue,
            'roi_percent' => $roiPercent,
            'profit' => $currentValue - $purchaseValue,
            'purchase_date' => $purchaseDate,
        ];
    }
    
    protected function calculateTotalROI($wallets): ?array
    {
        $totalInvested = 0;
        $totalCurrent = 0;
        $validWallets = 0;
        
        foreach ($wallets as $wallet) {
            $roi = $this->calculateWalletROI($wallet);
            if ($roi) {
                $totalInvested += $roi['purchase_value'];
                $totalCurrent += $roi['current_value'];
                $validWallets++;
            }
        }
        
        if ($validWallets == 0) {
            return null;
        }
        
        $totalROIPercent = ($totalInvested > 0) ? (($totalCurrent - $totalInvested) / $totalInvested) * 100 : 0;
        $totalProfit = $totalCurrent - $totalInvested;
        
        return [
            'total_invested' => $totalInvested,
            'total_current' => $totalCurrent,
            'total_roi_percent' => $totalROIPercent,
            'total_profit' => $totalProfit,
            'valid_wallets' => $validWallets,
        ];
    }
}

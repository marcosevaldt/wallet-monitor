<?php

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use App\Models\Transaction;
use App\Models\Wallet;
use Illuminate\Support\Facades\Auth;

class TotalTransactionsWidget extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        $user = Auth::user();
        $isAdmin = $user->email === 'admin@wallet-monitor.com';
        if ($isAdmin) {
            $totalTransactions = Transaction::count();
            $description = 'Total geral do sistema';
        } else {
            $userWalletIds = Wallet::where('user_id', $user->id)->pluck('id');
            $totalTransactions = Transaction::whereIn('wallet_id', $userWalletIds)->count();
            $description = 'Suas transações';
        }
        return [
            Stat::make('Total de Transações', $totalTransactions)
                ->description($description)
                ->descriptionIcon('heroicon-m-currency-dollar')
                ->color('success')
                ->chart([17, 16, 14, 15, 14, 13, 12]),
        ];
    }
} 
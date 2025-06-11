<?php

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use App\Models\Wallet;
use Illuminate\Support\Facades\Auth;

class TotalWalletsWidget extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        $user = Auth::user();
        $isAdmin = $user->email === 'admin@wallet-monitor.com';
        $totalWallets = $isAdmin ? Wallet::count() : Wallet::where('user_id', $user->id)->count();
        $description = $isAdmin ? 'Total geral do sistema' : 'Suas carteiras';
        return [
            Stat::make('Total de Carteiras', $totalWallets)
                ->description($description)
                ->descriptionIcon('heroicon-m-wallet')
                ->color('primary')
                ->chart([7, 2, 10, 3, 15, 4, 17]),
        ];
    }
} 
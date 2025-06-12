<?php

namespace App\Filament\Widgets;

use App\Models\Wallet;
use App\Models\Transaction;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class ImportProgressWidget extends BaseWidget
{
    protected static ?string $pollingInterval = '5m';

    protected function getStats(): array
    {
        $user = Auth::user();
        $isAdmin = $user->email === 'marcosevaldt@gmail.com';
        
        // Estatísticas de carteiras
        $totalWallets = $isAdmin ? Wallet::count() : Wallet::where('user_id', $user->id)->count();
        $completedImports = Wallet::where('import_progress', 100)->count();
        $inProgressImports = Wallet::where('import_progress', '>', 0)->where('import_progress', '<', 100)->count();
        $errorImports = Wallet::where('import_progress', -1)->count();
        $truncatedImports = Wallet::where('import_progress', -2)->count();
        $notStartedImports = Wallet::where('import_progress', 0)->count();

        // Estatísticas de transações
        if ($isAdmin) {
            $totalTransactions = Transaction::count();
            $transactionsDescription = 'Total geral do sistema';
        } else {
            $userWalletIds = Wallet::where('user_id', $user->id)->pluck('id');
            $totalTransactions = Transaction::whereIn('wallet_id', $userWalletIds)->count();
            $transactionsDescription = 'Suas transações';
        }

        // Preço do Bitcoin com timestamp da última atualização
        $data = Cache::remember('bitcoin_overview', 60, function () {
            try {
                $stats = [];
                $ratesResponse = Http::timeout(60)->get('https://blockchain.info/ticker');
                if ($ratesResponse->successful()) {
                    $rates = $ratesResponse->json();
                    if (isset($rates['USD'])) {
                        $stats['usd_price'] = $rates['USD']['last'];
                        $stats['last_updated'] = Carbon::now()->toISOString();
                    }
                }
                return $stats;
            } catch (\Exception $e) {
                return [];
            }
        });

        $bitcoinPrice = isset($data['usd_price']) ? '$' . number_format($data['usd_price'], 2) : 'N/A';
        
        // Calcular tempo desde a última atualização
        $lastUpdated = null;
        if (isset($data['last_updated'])) {
            $lastUpdated = Carbon::parse($data['last_updated'])->diffForHumans();
        }

        return [

            Stat::make('Preço Bitcoin (USD)', $bitcoinPrice)
                ->description($lastUpdated ? "Atualizado {$lastUpdated}" : 'Preço atual do Bitcoin')
                ->descriptionIcon('heroicon-m-currency-dollar')
                ->color('warning')
                ->chart([7, 2, 10, 3, 15, 4, 17]),

            Stat::make('Total de Carteiras', $totalWallets)
                ->description($isAdmin ? 'Total geral do sistema' : 'Suas carteiras')
                ->descriptionIcon('heroicon-m-wallet')
                ->color('primary')
                ->chart([7, 2, 10, 3, 15, 4, 17]),

            Stat::make('Total de Transações', $totalTransactions)
                ->description($transactionsDescription)
                ->descriptionIcon('heroicon-m-currency-dollar')
                ->color('success')
                ->chart([17, 16, 14, 15, 14, 13, 12]),

            Stat::make('Importações Concluídas', $completedImports)
                ->description('100% completas')
                ->color('success')
                ->chart([7, 2, 10, 3, 15, 4, 17]),

            Stat::make('Em Andamento', $inProgressImports)
                ->description('Importando...')
                ->color('warning')
                ->chart([17, 16, 14, 15, 14, 13, 12]),

            Stat::make('Com Erro', $errorImports)
                ->description('Falharam na importação')
                ->color('danger')
                ->chart([3, 4, 3, 2, 1, 1, 2]),

            Stat::make('Truncadas', $truncatedImports)
                ->description('Limite da API excedido')
                ->color('danger')
                ->chart([2, 1, 2, 1, 1, 2, 1]),

            Stat::make('Não Iniciadas', $notStartedImports)
                ->description('Aguardando importação')
                ->color('gray')
                ->chart([1, 2, 1, 3, 2, 1, 2]),
        ];
    }
} 
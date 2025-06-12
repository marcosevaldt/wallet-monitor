<?php

namespace App\Filament\Resources\WalletResource\Pages;

use App\Filament\Resources\WalletResource;
use Filament\Resources\Pages\Page;
use App\Services\WalletValuationService;
use App\Models\Wallet;

class WalletDetails extends Page
{
    protected static string $resource = WalletResource::class;

    protected static string $view = 'filament.resources.wallet-resource.pages.wallet-details';

    public $wallet;
    public $valorizacao;

    public function mount($record): void
    {
        // Buscar o modelo Wallet pelo ID
        $this->wallet = Wallet::findOrFail($record);
        
        try {
            $service = new WalletValuationService();
            $this->valorizacao = $service->calcularValorizacao($this->wallet);
        } catch (\Throwable $e) {
            $this->valorizacao = null;
        }
    }

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\Action::make('back_to_wallets')
                ->label('Voltar para Carteiras')
                ->icon('heroicon-o-arrow-left')
                ->url(route('filament.admin.resources.wallets.index'))
                ->color('gray'),
            
            \Filament\Actions\Action::make('edit_wallet')
                ->label('Editar Carteira')
                ->icon('heroicon-o-pencil')
                ->url(route('filament.admin.resources.wallets.edit', $this->wallet))
                ->color('primary'),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            // Aqui você pode adicionar widgets no futuro
            // Ex: WalletStatsWidget::class,
        ];
    }

    protected function getFooterWidgets(): array
    {
        return [
            // Aqui você pode adicionar widgets no futuro
            // Ex: WalletChartWidget::class,
        ];
    }
} 
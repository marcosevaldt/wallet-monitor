<?php

namespace App\Filament\Pages;

use Filament\Pages\Dashboard as BaseDashboard;
use App\Filament\Widgets\BitcoinChartWidget;

class Dashboard extends BaseDashboard
{
    protected static ?string $navigationIcon = 'heroicon-o-home';

    public function getWidgets(): array
    {
        return [
            \App\Filament\Widgets\WelcomeWidget::class,
            \App\Filament\Widgets\ImportProgressWidget::class,
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [];
    }
    
    protected function getFooterWidgets(): array
    {
        return [];
    }
} 
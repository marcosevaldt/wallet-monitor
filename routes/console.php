<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Bitcoin Price Update Schedule - Atualização diária completa
Schedule::command('bitcoin:populate-historical-data --days=7 --force')
    ->dailyAt('08:00')
    ->appendOutputTo(storage_path('logs/bitcoin-update.log'))
    ->withoutOverlapping()
    ->runInBackground();

// Bitcoin Price Update Schedule - Atualização a cada 4 horas para dados recentes
Schedule::command('bitcoin:populate-historical-data --days=1 --force')
    ->everyFourHours()
    ->appendOutputTo(storage_path('logs/bitcoin-update-recent.log'))
    ->withoutOverlapping()
    ->runInBackground();

// Bitcoin Historical Data Population - População semanal de dados históricos
Schedule::command('bitcoin:populate-historical-data --days=365 --force')
    ->weekly()
    ->sundays()
    ->at('02:00')
    ->appendOutputTo(storage_path('logs/bitcoin-historical.log'))
    ->withoutOverlapping()
    ->runInBackground();

// Wallet Balance Update Schedule - Atualização automática dos saldos das carteiras
Schedule::command('wallet:update-balance')
    ->hourly()
    ->appendOutputTo(storage_path('logs/wallet-balance-update.log'))
    ->withoutOverlapping()
    ->runInBackground();

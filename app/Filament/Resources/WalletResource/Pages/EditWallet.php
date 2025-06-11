<?php

namespace App\Filament\Resources\WalletResource\Pages;

use App\Filament\Resources\WalletResource;
use App\Filament\Actions\ImportTransactionsAction;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditWallet extends EditRecord
{
    protected static string $resource = WalletResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ImportTransactionsAction::make(),
            Actions\DeleteAction::make()
                ->label('Excluir'),
        ];
    }
}

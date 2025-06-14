<?php

namespace App\Filament\Resources\WalletResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ImportJobsRelationManager extends RelationManager
{
    protected static string $relationship = 'importJobs';

    protected static ?string $title = 'Histórico de Importações';

    protected static ?string $recordTitleAttribute = 'job_type';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('job_type')
                    ->required()
                    ->maxLength(255),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('job_type')
            ->columns([
                Tables\Columns\TextColumn::make('job_type_label')
                    ->label('Tipo')
                    ->badge()
                    ->color(fn ($record) => $record->job_type === 'import' ? 'success' : 'info'),
                
                Tables\Columns\BadgeColumn::make('status')
                    ->label('Status')
                    ->getStateUsing(fn ($record) => $record->status_label)
                    ->color(fn ($record) => $record->status_color),
                
                Tables\Columns\TextColumn::make('progress')
                    ->label('Progresso')
                    ->suffix('%')
                    ->color(fn ($state) => match (true) {
                        $state == 100 => 'success',
                        $state > 0 => 'warning',
                        default => 'gray',
                    }),
                
                Tables\Columns\TextColumn::make('imported_transactions')
                    ->label('Importadas')
                    ->getStateUsing(function ($record) {
                        return "{$record->imported_transactions}/{$record->total_transactions}";
                    })
                    ->color('success'),
                
                Tables\Columns\TextColumn::make('transactions_send')
                    ->label('Entradas')
                    ->getStateUsing(function ($record) {
                        return "📤 {$record->send_transactions}";
                    }),

                Tables\Columns\TextColumn::make('transactions_receive')
                    ->label('Saídas')
                    ->getStateUsing(function ($record) {
                        return "📤 {$record->receive_transactions}";
                    }),
                
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Solicitado em')
                    ->since()
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('completed_at')
                    ->label('Concluído em')
                    ->since()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('job_type')
                    ->label('Tipo de Job')
                    ->options([
                        'import' => 'Importação Inicial',
                        'update' => 'Atualização',
                    ]),
                
                Tables\Filters\SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'pending' => 'Aguardando',
                        'running' => 'Em Execução',
                        'completed' => 'Concluído',
                        'failed' => 'Falhou',
                    ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->paginated([10, 25, 50]);
    }
}

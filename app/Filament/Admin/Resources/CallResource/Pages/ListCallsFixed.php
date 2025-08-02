<?php

namespace App\Filament\Admin\Resources\CallResource\Pages;

use App\Filament\Admin\Resources\CallResource;
use Filament\Resources\Pages\ListRecords;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ListCallsFixed extends ListRecords
{
    protected static string $resource = CallResource::class;
    
    public function mount(): void
    {
        // Force company context
        if (auth()->check() && auth()->user()->company_id) {
            app()->instance('current_company_id', auth()->user()->company_id);
            app()->instance('company_context_source', 'web_auth');
        }
        
        parent::mount();
    }
    
    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('call_id')
                    ->label('Call ID')
                    ->searchable()
                    ->limit(20),
                    
                Tables\Columns\TextColumn::make('phone_number')
                    ->label('Telefon')
                    ->searchable(),
                    
                Tables\Columns\TextColumn::make('customer.name')
                    ->label('Kunde')
                    ->searchable()
                    ->default('-'),
                    
                Tables\Columns\TextColumn::make('duration_sec')
                    ->label('Dauer')
                    ->formatStateUsing(fn ($state) => $state ? $state . 's' : '-')
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Datum')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['customer']))
            ->paginated([10, 25, 50]);
    }
    
    protected function getHeaderActions(): array
    {
        return [];
    }
}
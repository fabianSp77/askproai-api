<?php

namespace App\Filament\Admin\Resources;

use App\Models\Call;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class SimpleCallResource extends Resource
{
    protected static ?string $model = Call::class;
    
    protected static ?string $navigationIcon = 'heroicon-o-phone';
    
    protected static ?string $navigationLabel = 'Anrufe (Simple)';
    
    protected static ?string $navigationGroup = 'Täglicher Betrieb';
    
    protected static ?int $navigationSort = 111;
    
    protected static ?string $slug = 'simple-calls-view';
    
    public static function shouldRegisterNavigation(): bool
    {
        return false; // Hide from navigation
    }
    
    public static function table(Table $table): Table
    {
        return $table
            ->query(Call::query()->where('company_id', auth()->user()->company_id ?? 1))
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Datum')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('duration_sec')
                    ->label('Dauer (Sek)')
                    ->sortable()
                    ->default('0'),
                    
                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'completed' => 'success',
                        'failed' => 'danger',
                        default => 'gray',
                    }),
                    
                Tables\Columns\TextColumn::make('from_phone')
                    ->label('Von')
                    ->default('—'),
                    
                Tables\Columns\TextColumn::make('to_phone')
                    ->label('Nach')
                    ->default('—'),
            ])
            ->defaultSort('created_at', 'desc')
            ->paginated([25, 50, 100]);
    }
    
    public static function getPages(): array
    {
        return [
            'index' => \App\Filament\Admin\Resources\SimpleCallResource\Pages\ListSimpleCalls::route('/'),
        ];
    }
}
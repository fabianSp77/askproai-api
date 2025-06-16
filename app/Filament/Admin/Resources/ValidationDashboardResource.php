<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\ValidationDashboardResource\Pages;
use App\Models\ValidationResult;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ValidationDashboardResource extends Resource
{
    protected static ?string $model = ValidationResult::class;
    protected static ?string $navigationIcon = 'heroicon-o-shield-check';
    protected static ?string $navigationGroup = 'System & Monitoring';
    protected static ?string $navigationLabel = 'Validation Dashboard';
    protected static ?string $slug = 'validation-dashboard';
    protected static ?int $navigationSort = 40;

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('validatable_type')
                    ->label('Type')
                    ->formatStateUsing(fn (string $state): string => class_basename($state))
                    ->badge(),
                    
                Tables\Columns\TextColumn::make('validation_type')
                    ->label('Validation')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'api_connection' => 'info',
                        'configuration' => 'warning',
                        'data_integrity' => 'danger',
                        default => 'gray',
                    }),
                    
                Tables\Columns\IconColumn::make('status')
                    ->icon(fn (string $state): string => match ($state) {
                        'success' => 'heroicon-o-check-circle',
                        'warning' => 'heroicon-o-exclamation-triangle',
                        'error' => 'heroicon-o-x-circle',
                        default => 'heroicon-o-question-mark-circle',
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'success' => 'success',
                        'warning' => 'warning',
                        'error' => 'danger',
                        default => 'gray',
                    }),
                    
                Tables\Columns\TextColumn::make('message')
                    ->limit(50)
                    ->tooltip(function (Tables\Columns\TextColumn $column): ?string {
                        $state = $column->getState();
                        return strlen($state) > 50 ? $state : null;
                    }),
                    
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->label('Validated At'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'success' => 'Success',
                        'warning' => 'Warning',
                        'error' => 'Error',
                    ]),
                    
                Tables\Filters\SelectFilter::make('validation_type')
                    ->options([
                        'api_connection' => 'API Connection',
                        'configuration' => 'Configuration',
                        'data_integrity' => 'Data Integrity',
                    ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListValidationDashboards::route('/'),
        ];
    }
}

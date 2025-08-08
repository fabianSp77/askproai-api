<?php

namespace App\Filament\Admin\Resources\ResellerResource\Widgets;

use App\Models\Company;
use App\Services\ResellerMetricsService;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Support\Facades\DB;

class TopResellersWidget extends BaseWidget
{
    protected int | string | array $columnSpan = 'full';
    
    protected static ?string $heading = 'Top Performing Resellers';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Company::query()
                    ->where('company_type', 'reseller')
                    ->where('is_active', true)
                    ->withCount('childCompanies')
                    ->with(['childCompanies:id,parent_company_id'])
                    ->orderByDesc('child_companies_count')
                    ->limit(10)
            )
            ->columns([
                Tables\Columns\TextColumn::make('rank')
                    ->label('#')
                    ->rowIndex()
                    ->alignCenter(),

                Tables\Columns\ImageColumn::make('logo')
                    ->label('')
                    ->circular()
                    ->size(32)
                    ->defaultImageUrl(url('/images/default-company.png')),

                Tables\Columns\TextColumn::make('name')
                    ->label('Reseller')
                    ->searchable()
                    ->weight('bold')
                    ->description(fn (Company $record): string => $record->email ?? ''),

                Tables\Columns\TextColumn::make('child_companies_count')
                    ->label('Clients')
                    ->badge()
                    ->color('primary')
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('total_revenue')
                    ->label('Total Revenue')
                    ->state(function (Company $record): float {
                        // Use cached metrics service
                        $metricsService = app(ResellerMetricsService::class);
                        $metrics = $metricsService->getRevenueMetrics($record);
                        return $metrics['total_revenue'];
                    })
                    ->money('EUR')
                    ->sortable()
                    ->color('success')
                    ->weight('semibold'),

                Tables\Columns\TextColumn::make('commission_earned')
                    ->label('Commission Earned')
                    ->state(function (Company $record): float {
                        // Use cached metrics service
                        $metricsService = app(ResellerMetricsService::class);
                        $metrics = $metricsService->getRevenueMetrics($record);
                        return $metrics['commission_earned'];
                    })
                    ->money('EUR')
                    ->color('warning'),

                Tables\Columns\TextColumn::make('commission_rate')
                    ->label('Rate')
                    ->suffix('%')
                    ->badge()
                    ->color('info')
                    ->alignCenter(),

                Tables\Columns\IconColumn::make('is_white_label')
                    ->label('White Label')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-minus')
                    ->trueColor('success')
                    ->falseColor('gray')
                    ->alignCenter(),
            ])
            ->actions([
                Tables\Actions\Action::make('view')
                    ->label('View')
                    ->icon('heroicon-o-eye')
                    ->color('primary')
                    ->url(fn (Company $record): string => 
                        route('filament.admin.resources.resellers.view', $record)
                    ),

                Tables\Actions\Action::make('dashboard')
                    ->label('Dashboard')
                    ->icon('heroicon-o-chart-bar')
                    ->color('success')
                    ->url(fn (Company $record): string => 
                        route('filament.admin.resources.resellers.dashboard', $record)
                    ),
            ])
            ->emptyStateHeading('No resellers found')
            ->emptyStateDescription('Create your first reseller to start tracking performance.')
            ->emptyStateIcon('heroicon-o-user-group');
    }
}
<?php

namespace App\Filament\Admin\Traits;

use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Illuminate\Database\Eloquent\Builder;
use Carbon\Carbon;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Grid;
use Filament\Tables\Enums\FiltersLayout;

trait HasCompactFilters
{
    protected function getCompactDateRangeFilter(): Filter
    {
        return Filter::make('date_range')
            ->form([
                Select::make('preset')
                    ->label('Zeitraum')
                    ->options([
                        'today' => 'Heute',
                        'yesterday' => 'Gestern',
                        'last7days' => 'Letzte 7 Tage',
                        'last30days' => 'Letzte 30 Tage',
                        'thisMonth' => 'Dieser Monat',
                        'lastMonth' => 'Letzter Monat',
                        'thisYear' => 'Dieses Jahr',
                        'custom' => 'Benutzerdefiniert',
                    ])
                    ->default('last7days')
                    ->reactive()
                    ->afterStateUpdated(function ($state, callable $set) {
                        if ($state !== 'custom') {
                            $dates = $this->getPresetDates($state);
                            $set('from', $dates['from']);
                            $set('until', $dates['until']);
                        }
                    }),
                Grid::make(2)
                    ->schema([
                        DatePicker::make('from')
                            ->label('Von')
                            ->visible(fn ($get) => $get('preset') === 'custom')
                            ->native(false)
                            ->displayFormat('d.m.Y'),
                        DatePicker::make('until')
                            ->label('Bis')
                            ->visible(fn ($get) => $get('preset') === 'custom')
                            ->native(false)
                            ->displayFormat('d.m.Y'),
                    ])
            ])
            ->query(function (Builder $query, array $data): Builder {
                $dateColumn = $this->getDateColumnForFilter();
                
                return $query
                    ->when(
                        $data['from'] ?? null,
                        fn (Builder $query, $date): Builder => $query->whereDate($dateColumn, '>=', $date),
                    )
                    ->when(
                        $data['until'] ?? null,
                        fn (Builder $query, $date): Builder => $query->whereDate($dateColumn, '<=', $date),
                    );
            })
            ->indicateUsing(function (array $data): array {
                $indicators = [];
                $preset = $data['preset'] ?? 'last7days';
                
                if ($preset === 'custom') {
                    if ($data['from'] ?? null) {
                        $indicators[] = 'Von: ' . Carbon::parse($data['from'])->format('d.m.Y');
                    }
                    if ($data['until'] ?? null) {
                        $indicators[] = 'Bis: ' . Carbon::parse($data['until'])->format('d.m.Y');
                    }
                } else {
                    $indicators[] = $this->getPresetLabel($preset);
                }
                
                return $indicators;
            });
    }
    
    protected function getPresetDates(string $preset): array
    {
        return match($preset) {
            'today' => [
                'from' => Carbon::today()->format('Y-m-d'),
                'until' => Carbon::today()->format('Y-m-d'),
            ],
            'yesterday' => [
                'from' => Carbon::yesterday()->format('Y-m-d'),
                'until' => Carbon::yesterday()->format('Y-m-d'),
            ],
            'last7days' => [
                'from' => Carbon::now()->subDays(6)->format('Y-m-d'),
                'until' => Carbon::today()->format('Y-m-d'),
            ],
            'last30days' => [
                'from' => Carbon::now()->subDays(29)->format('Y-m-d'),
                'until' => Carbon::today()->format('Y-m-d'),
            ],
            'thisMonth' => [
                'from' => Carbon::now()->startOfMonth()->format('Y-m-d'),
                'until' => Carbon::now()->endOfMonth()->format('Y-m-d'),
            ],
            'lastMonth' => [
                'from' => Carbon::now()->subMonth()->startOfMonth()->format('Y-m-d'),
                'until' => Carbon::now()->subMonth()->endOfMonth()->format('Y-m-d'),
            ],
            'thisYear' => [
                'from' => Carbon::now()->startOfYear()->format('Y-m-d'),
                'until' => Carbon::now()->endOfYear()->format('Y-m-d'),
            ],
            default => [
                'from' => Carbon::now()->subDays(6)->format('Y-m-d'),
                'until' => Carbon::today()->format('Y-m-d'),
            ],
        };
    }
    
    protected function getPresetLabel(string $preset): string
    {
        return match($preset) {
            'today' => 'Heute',
            'yesterday' => 'Gestern',
            'last7days' => 'Letzte 7 Tage',
            'last30days' => 'Letzte 30 Tage',
            'thisMonth' => 'Dieser Monat',
            'lastMonth' => 'Letzter Monat',
            'thisYear' => 'Dieses Jahr',
            default => 'Letzte 7 Tage',
        };
    }
    
    protected function getDateColumnForFilter(): string
    {
        // Override this method in your resource to specify the date column
        return 'created_at';
    }
    
    protected function applyCompactFilterLayout(): array
    {
        return [
            'layout' => FiltersLayout::AboveContent,
            'columns' => [
                'sm' => 2,
                'md' => 3,
                'lg' => 4,
            ],
        ];
    }
}
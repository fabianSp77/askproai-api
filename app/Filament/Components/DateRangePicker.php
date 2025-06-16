<?php

namespace App\Filament\Components;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Grid;
use Filament\Tables\Filters\Filter;
use Illuminate\Database\Eloquent\Builder;
use Carbon\Carbon;

class DateRangePicker
{
    /**
     * Create a date range filter for tables
     */
    public static function make(string $column = 'created_at', string $label = 'Datum'): Filter
    {
        return Filter::make($column . '_range')
            ->label($label)
            ->form([
                Grid::make(2)
                    ->schema([
                        DatePicker::make($column . '_from')
                            ->label('Von')
                            ->native(false)
                            ->displayFormat('d.m.Y')
                            ->closeOnDateSelection()
                            ->placeholder('Startdatum'),
                        DatePicker::make($column . '_until')
                            ->label('Bis')
                            ->native(false)
                            ->displayFormat('d.m.Y')
                            ->closeOnDateSelection()
                            ->placeholder('Enddatum'),
                    ]),
            ])
            ->query(function (Builder $query, array $data) use ($column): Builder {
                return $query
                    ->when(
                        $data[$column . '_from'],
                        fn (Builder $query, $date): Builder => $query->whereDate($column, '>=', $date),
                    )
                    ->when(
                        $data[$column . '_until'],
                        fn (Builder $query, $date): Builder => $query->whereDate($column, '<=', $date),
                    );
            })
            ->indicateUsing(function (array $data) use ($column, $label): array {
                $indicators = [];

                if ($data[$column . '_from'] ?? null) {
                    $indicators[$column . '_from'] = $label . ' ab ' . Carbon::parse($data[$column . '_from'])->format('d.m.Y');
                }

                if ($data[$column . '_until'] ?? null) {
                    $indicators[$column . '_until'] = $label . ' bis ' . Carbon::parse($data[$column . '_until'])->format('d.m.Y');
                }

                return $indicators;
            });
    }

    /**
     * Create preset date ranges
     */
    public static function withPresets(string $column = 'created_at', string $label = 'Datum'): Filter
    {
        return Filter::make($column . '_preset')
            ->label($label . ' (Vorlagen)')
            ->form([
                \Filament\Forms\Components\Select::make('preset')
                    ->label('Zeitraum')
                    ->options([
                        'today' => 'Heute',
                        'yesterday' => 'Gestern',
                        'this_week' => 'Diese Woche',
                        'last_week' => 'Letzte Woche',
                        'this_month' => 'Dieser Monat',
                        'last_month' => 'Letzter Monat',
                        'this_quarter' => 'Dieses Quartal',
                        'last_quarter' => 'Letztes Quartal',
                        'this_year' => 'Dieses Jahr',
                        'last_year' => 'Letztes Jahr',
                        'last_7_days' => 'Letzte 7 Tage',
                        'last_30_days' => 'Letzte 30 Tage',
                        'last_90_days' => 'Letzte 90 Tage',
                        'custom' => 'Benutzerdefiniert',
                    ])
                    ->reactive()
                    ->afterStateUpdated(fn ($state, callable $set) => static::setDatesByPreset($state, $set, $column)),
                Grid::make(2)
                    ->schema([
                        DatePicker::make($column . '_from')
                            ->label('Von')
                            ->native(false)
                            ->displayFormat('d.m.Y')
                            ->visible(fn (callable $get) => $get('preset') === 'custom'),
                        DatePicker::make($column . '_until')
                            ->label('Bis')
                            ->native(false)
                            ->displayFormat('d.m.Y')
                            ->visible(fn (callable $get) => $get('preset') === 'custom'),
                    ]),
            ])
            ->query(function (Builder $query, array $data) use ($column): Builder {
                if (empty($data['preset'])) {
                    return $query;
                }

                $dates = static::getPresetDates($data['preset']);
                
                if ($data['preset'] === 'custom') {
                    return $query
                        ->when(
                            $data[$column . '_from'] ?? null,
                            fn (Builder $query, $date): Builder => $query->whereDate($column, '>=', $date),
                        )
                        ->when(
                            $data[$column . '_until'] ?? null,
                            fn (Builder $query, $date): Builder => $query->whereDate($column, '<=', $date),
                        );
                }

                if ($dates) {
                    return $query->whereBetween($column, [$dates['from'], $dates['until']]);
                }

                return $query;
            })
            ->indicateUsing(function (array $data) use ($column, $label): array {
                if (empty($data['preset'])) {
                    return [];
                }

                if ($data['preset'] === 'custom') {
                    $indicators = [];
                    if ($data[$column . '_from'] ?? null) {
                        $indicators[] = $label . ' ab ' . Carbon::parse($data[$column . '_from'])->format('d.m.Y');
                    }
                    if ($data[$column . '_until'] ?? null) {
                        $indicators[] = $label . ' bis ' . Carbon::parse($data[$column . '_until'])->format('d.m.Y');
                    }
                    return $indicators;
                }

                $presetLabels = [
                    'today' => 'Heute',
                    'yesterday' => 'Gestern',
                    'this_week' => 'Diese Woche',
                    'last_week' => 'Letzte Woche',
                    'this_month' => 'Dieser Monat',
                    'last_month' => 'Letzter Monat',
                    'this_quarter' => 'Dieses Quartal',
                    'last_quarter' => 'Letztes Quartal',
                    'this_year' => 'Dieses Jahr',
                    'last_year' => 'Letztes Jahr',
                    'last_7_days' => 'Letzte 7 Tage',
                    'last_30_days' => 'Letzte 30 Tage',
                    'last_90_days' => 'Letzte 90 Tage',
                ];

                return [$label . ': ' . ($presetLabels[$data['preset']] ?? $data['preset'])];
            });
    }

    /**
     * Get dates for preset
     */
    protected static function getPresetDates(string $preset): ?array
    {
        $now = Carbon::now();

        return match ($preset) {
            'today' => [
                'from' => $now->copy()->startOfDay(),
                'until' => $now->copy()->endOfDay(),
            ],
            'yesterday' => [
                'from' => $now->copy()->subDay()->startOfDay(),
                'until' => $now->copy()->subDay()->endOfDay(),
            ],
            'this_week' => [
                'from' => $now->copy()->startOfWeek(),
                'until' => $now->copy()->endOfWeek(),
            ],
            'last_week' => [
                'from' => $now->copy()->subWeek()->startOfWeek(),
                'until' => $now->copy()->subWeek()->endOfWeek(),
            ],
            'this_month' => [
                'from' => $now->copy()->startOfMonth(),
                'until' => $now->copy()->endOfMonth(),
            ],
            'last_month' => [
                'from' => $now->copy()->subMonth()->startOfMonth(),
                'until' => $now->copy()->subMonth()->endOfMonth(),
            ],
            'this_quarter' => [
                'from' => $now->copy()->startOfQuarter(),
                'until' => $now->copy()->endOfQuarter(),
            ],
            'last_quarter' => [
                'from' => $now->copy()->subQuarter()->startOfQuarter(),
                'until' => $now->copy()->subQuarter()->endOfQuarter(),
            ],
            'this_year' => [
                'from' => $now->copy()->startOfYear(),
                'until' => $now->copy()->endOfYear(),
            ],
            'last_year' => [
                'from' => $now->copy()->subYear()->startOfYear(),
                'until' => $now->copy()->subYear()->endOfYear(),
            ],
            'last_7_days' => [
                'from' => $now->copy()->subDays(7)->startOfDay(),
                'until' => $now->copy()->endOfDay(),
            ],
            'last_30_days' => [
                'from' => $now->copy()->subDays(30)->startOfDay(),
                'until' => $now->copy()->endOfDay(),
            ],
            'last_90_days' => [
                'from' => $now->copy()->subDays(90)->startOfDay(),
                'until' => $now->copy()->endOfDay(),
            ],
            default => null,
        };
    }

    /**
     * Set dates by preset for form
     */
    protected static function setDatesByPreset(string $preset, callable $set, string $column): void
    {
        if ($preset === 'custom') {
            return;
        }

        $dates = static::getPresetDates($preset);
        
        if ($dates) {
            $set($column . '_from', $dates['from']->format('Y-m-d'));
            $set($column . '_until', $dates['until']->format('Y-m-d'));
        }
    }

    /**
     * Create form date range for forms
     */
    public static function formField(string $column = 'date', string $label = 'Zeitraum'): Section
    {
        return Section::make($label)
            ->schema([
                Grid::make(2)
                    ->schema([
                        DatePicker::make($column . '_from')
                            ->label('Von')
                            ->native(false)
                            ->displayFormat('d.m.Y')
                            ->closeOnDateSelection(),
                        DatePicker::make($column . '_until')
                            ->label('Bis')
                            ->native(false)
                            ->displayFormat('d.m.Y')
                            ->closeOnDateSelection()
                            ->minDate(fn (callable $get) => $get($column . '_from')),
                    ]),
            ])
            ->collapsible();
    }
}
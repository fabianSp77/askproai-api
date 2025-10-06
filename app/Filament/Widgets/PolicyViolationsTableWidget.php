<?php

namespace App\Filament\Widgets;

use App\Models\AppointmentModificationStat;
use App\Models\Customer;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;

class PolicyViolationsTableWidget extends BaseWidget
{
    protected static ?int $sort = 4;

    protected static ?string $heading = 'Neueste Policy-Verstöße';

    protected int | string | array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        $companyId = auth()->user()->company_id;

        return $table
            ->query(
                AppointmentModificationStat::query()
                    ->whereHas('customer', function (Builder $query) use ($companyId) {
                        $query->where('company_id', $companyId);
                    })
                    ->where('stat_type', 'violation')
                    ->latest()
                    ->limit(10)
            )
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('customer.name')
                    ->label('Kunde')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('metadata')
                    ->label('Policy-Typ')
                    ->formatStateUsing(function ($state): string {
                        if (is_array($state) && isset($state['policy_type'])) {
                            return ucfirst(str_replace('_', ' ', $state['policy_type']));
                        }
                        return '—';
                    })
                    ->badge()
                    ->color('danger'),

                Tables\Columns\TextColumn::make('count')
                    ->label('Anzahl')
                    ->badge()
                    ->color('warning')
                    ->sortable(),

                Tables\Columns\TextColumn::make('period_start')
                    ->label('Periode Start')
                    ->date('d.m.Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('period_end')
                    ->label('Periode Ende')
                    ->date('d.m.Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Erstellt')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->since(),
            ])
            ->defaultSort('created_at', 'desc')
            ->paginated([5, 10])
            ->poll('30s');
    }

    /**
     * Get column span
     */
    public function getColumnSpan(): string | array | int
    {
        return 'full';
    }
}

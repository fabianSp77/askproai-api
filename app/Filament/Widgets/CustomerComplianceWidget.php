<?php

namespace App\Filament\Widgets;

use App\Models\Customer;
use App\Models\AppointmentModificationStat;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;

class CustomerComplianceWidget extends BaseWidget
{
    protected static ?int $sort = 5;

    protected static ?string $heading = 'Kunden-Compliance-Ranking';

    protected int | string | array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        $companyId = auth()->user()->company_id;

        return $table
            ->query(
                Customer::query()
                    ->where('company_id', $companyId)
                    ->withCount([
                        'appointmentModificationStats as total_violations' => function (Builder $query) {
                            $query->where('stat_type', 'violation');
                        },
                        'appointmentModificationStats as total_cancellations' => function (Builder $query) {
                            $query->where('stat_type', 'cancellation');
                        },
                        'appointments as total_appointments'
                    ])
                    ->having('total_violations', '>', 0)
                    ->orderByDesc('total_violations')
                    ->limit(20)
            )
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->sortable()
                    ->badge()
                    ->color('gray'),

                Tables\Columns\TextColumn::make('name')
                    ->label('Kunde')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->description(fn (Customer $record): string => $record->email ?? '—'),

                Tables\Columns\TextColumn::make('journey_status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'lead' => '🌱 Lead',
                        'prospect' => '🔍 Interessent',
                        'customer' => '⭐ Kunde',
                        'regular' => '💎 Stammkunde',
                        'vip' => '👑 VIP',
                        'at_risk' => '⚠️ Gefährdet',
                        'churned' => '❌ Verloren',
                        default => $state,
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'vip' => 'success',
                        'regular' => 'primary',
                        'customer' => 'info',
                        'at_risk' => 'warning',
                        'churned' => 'danger',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('total_violations')
                    ->label('Verstöße')
                    ->badge()
                    ->color('danger')
                    ->sortable(),

                Tables\Columns\TextColumn::make('total_cancellations')
                    ->label('Stornierungen')
                    ->badge()
                    ->color('warning')
                    ->sortable(),

                Tables\Columns\TextColumn::make('total_appointments')
                    ->label('Termine gesamt')
                    ->badge()
                    ->color('info')
                    ->sortable(),

                Tables\Columns\TextColumn::make('compliance_rate')
                    ->label('Compliance-Rate')
                    ->getStateUsing(function (Customer $record): string {
                        if ($record->total_appointments == 0) return '100%';
                        $rate = (($record->total_appointments - $record->total_violations) / $record->total_appointments) * 100;
                        return round($rate, 1) . '%';
                    })
                    ->badge()
                    ->color(function (Customer $record): string {
                        if ($record->total_appointments == 0) return 'success';
                        $rate = (($record->total_appointments - $record->total_violations) / $record->total_appointments) * 100;
                        return $rate >= 90 ? 'success' : ($rate >= 70 ? 'warning' : 'danger');
                    })
                    ->sortable(query: function (Builder $query, string $direction): Builder {
                        return $query->orderByRaw("(total_appointments - total_violations) / total_appointments {$direction}");
                    }),
            ])
            ->defaultSort('total_violations', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('journey_status')
                    ->label('Journey Status')
                    ->options([
                        'lead' => '🌱 Lead',
                        'prospect' => '🔍 Interessent',
                        'customer' => '⭐ Kunde',
                        'regular' => '💎 Stammkunde',
                        'vip' => '👑 VIP',
                        'at_risk' => '⚠️ Gefährdet',
                        'churned' => '❌ Verloren',
                    ]),
            ])
            ->actions([
                Tables\Actions\Action::make('view_details')
                    ->label('Details')
                    ->icon('heroicon-o-eye')
                    ->url(fn (Customer $record): string => route('filament.admin.resources.customers.view', ['record' => $record])),
            ])
            ->paginated([10, 20, 50]);
    }

    public function getColumnSpan(): string | array | int
    {
        return 'full';
    }
}

<?php

namespace App\Filament\Resources\CustomerResource\Widgets;

use App\Models\Customer;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;
use Carbon\Carbon;

class CustomerRiskAlerts extends BaseWidget
{
    protected static ?int $sort = 3;
    protected int | string | array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Customer::query()
                    ->with(['preferredBranch', 'preferredStaff'])
                    ->where(function (Builder $query) {
                        $query->where('journey_status', 'at_risk')
                            ->orWhere(function (Builder $q) {
                                // Customers who haven't visited in 90+ days
                                $q->where('last_appointment_at', '<', now()->subDays(90))
                                  ->whereNotNull('last_appointment_at');
                            })
                            ->orWhere(function (Builder $q) {
                                // High-value customers with declining engagement
                                $q->where('total_revenue', '>', 500)
                                  ->where('engagement_score', '<', 30);
                            })
                            ->orWhere('cancellation_count', '>', 3);
                    })
                    ->orderBy('engagement_score', 'asc')
                    ->limit(10)
            )
            ->columns([
                Tables\Columns\TextColumn::make('customer_number')
                    ->label('Nr.')
                    ->searchable()
                    ->size('xs'),

                Tables\Columns\TextColumn::make('name')
                    ->label('Kunde')
                    ->searchable()
                    ->weight('bold')
                    ->description(fn ($record) => $record->phone),

                Tables\Columns\TextColumn::make('risk_level')
                    ->label('Risiko')
                    ->badge()
                    ->getStateUsing(function ($record) {
                        if ($record->journey_status === 'churned') return 'Verloren';
                        if ($record->journey_status === 'at_risk') return 'Hoch';

                        $daysSinceLastVisit = $record->last_appointment_at
                            ? Carbon::parse($record->last_appointment_at)->diffInDays()
                            : 999;

                        if ($daysSinceLastVisit > 120) return 'Kritisch';
                        if ($daysSinceLastVisit > 90) return 'Hoch';
                        if ($daysSinceLastVisit > 60) return 'Mittel';

                        return 'Niedrig';
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'Kritisch' => 'danger',
                        'Hoch' => 'warning',
                        'Mittel' => 'info',
                        'Verloren' => 'gray',
                        default => 'success',
                    }),

                Tables\Columns\TextColumn::make('last_appointment_at')
                    ->label('Letzter Besuch')
                    ->date('d.m.Y')
                    ->description(fn ($record) =>
                        $record->last_appointment_at
                            ? Carbon::parse($record->last_appointment_at)->diffForHumans()
                            : 'Noch nie'
                    )
                    ->color(fn ($record) =>
                        !$record->last_appointment_at || Carbon::parse($record->last_appointment_at)->lt(now()->subDays(90))
                            ? 'danger'
                            : 'gray'
                    ),

                Tables\Columns\TextColumn::make('total_revenue')
                    ->label('Umsatz')
                    ->money('EUR')
                    ->alignEnd()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('engagement_score')
                    ->label('Engagement')
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state . '%')
                    ->color(fn ($state) =>
                        $state < 30 ? 'danger' :
                        ($state < 60 ? 'warning' : 'success')
                    ),

                Tables\Columns\TextColumn::make('risk_reasons')
                    ->label('Risikofaktoren')
                    ->getStateUsing(function ($record) {
                        $reasons = [];

                        $daysSinceLastVisit = $record->last_appointment_at
                            ? Carbon::parse($record->last_appointment_at)->diffInDays()
                            : 999;

                        if ($daysSinceLastVisit > 90) {
                            $reasons[] = '⏰ Lange inaktiv';
                        }
                        if ($record->cancellation_count > 2) {
                            $reasons[] = '❌ Häufige Absagen';
                        }
                        if ($record->engagement_score < 30) {
                            $reasons[] = '📉 Niedriges Engagement';
                        }
                        if ($record->journey_status === 'at_risk') {
                            $reasons[] = '⚠️ Als gefährdet markiert';
                        }

                        return implode(' | ', $reasons) ?: 'Keine';
                    })
                    ->wrap()
                    ->size('xs'),
            ])
            ->actions([
                Tables\Actions\Action::make('contact')
                    ->label('Kontaktieren')
                    ->icon('heroicon-m-phone')
                    ->color('primary')
                    ->form([
                        \Filament\Forms\Components\Select::make('contact_type')
                            ->label('Kontaktart')
                            ->options([
                                'call' => '📞 Anrufen',
                                'sms' => '💬 SMS senden',
                                'email' => '📧 E-Mail senden',
                                'special_offer' => '🎁 Sonderangebot',
                            ])
                            ->required(),
                        \Filament\Forms\Components\Textarea::make('notes')
                            ->label('Notiz')
                            ->rows(2),
                    ])
                    ->action(function ($record, array $data) {
                        // Log contact attempt
                        $record->update([
                            'last_contact_at' => now(),
                            'notes' => ($record->notes ?? '') . "\n[" . now()->format('d.m.Y') . "] Kontakt: " . $data['contact_type'] . " - " . ($data['notes'] ?? ''),
                        ]);

                        \Filament\Notifications\Notification::make()
                            ->title('Kunde kontaktiert')
                            ->body("Kontakt zu {$record->name} wurde dokumentiert.")
                            ->success()
                            ->send();
                    }),

                Tables\Actions\Action::make('win_back')
                    ->label('Rückgewinnung')
                    ->icon('heroicon-m-gift')
                    ->color('success')
                    ->action(function ($record) {
                        $record->update([
                            'journey_status' => 'prospect',
                            'engagement_score' => min(100, $record->engagement_score + 20),
                        ]);

                        \Filament\Notifications\Notification::make()
                            ->title('Rückgewinnungskampagne gestartet')
                            ->body("Kunde wurde für Rückgewinnung markiert.")
                            ->success()
                            ->send();
                    }),
            ])
            ->heading('Risiko-Kunden')
            ->description('Kunden mit hohem Abwanderungsrisiko')
            ->poll('60s')
            ->emptyStateHeading('Keine Risiko-Kunden')
            ->emptyStateDescription('Alle Kunden sind aktiv und engagiert!')
            ->emptyStateIcon('heroicon-o-check-circle');
    }
}
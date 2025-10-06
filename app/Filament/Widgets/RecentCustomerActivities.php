<?php

namespace App\Filament\Widgets;

use App\Models\Customer;
use App\Models\Appointment;
use App\Models\Call;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class RecentCustomerActivities extends BaseWidget
{
    protected static ?string $heading = 'Aktuelle Kundenaktivitäten';

    protected static ?int $sort = 3;

    protected int | string | array $columnSpan = 'full';

    protected static ?string $pollingInterval = '300s';

    public function table(Table $table): Table
    {
        return $table
            ->query(function () {
                // Combine recent appointments and calls
                // PERFORMANCE: Select only needed columns to reduce memory usage
                $recentAppointments = Appointment::select(['id', 'customer_id', 'service_id', 'starts_at', 'status'])
                    ->with(['customer:id,name', 'service:id,name'])
                    ->whereDate('starts_at', '>=', now()->subDays(7))
                    ->limit(10)
                    ->get()
                    ->map(function ($appointment) {
                        return [
                            'id' => $appointment->id . '_appointment',
                            'type' => 'appointment',
                            'customer_name' => $appointment->customer?->name ?? 'Unbekannt',
                            'customer_id' => $appointment->customer_id,
                            'description' => $appointment->service?->name ?? 'Termin',
                            'status' => $appointment->status,
                            'timestamp' => $appointment->starts_at,
                            'icon' => 'heroicon-o-calendar',
                            'color' => match($appointment->status) {
                                'confirmed' => 'success',
                                'cancelled' => 'danger',
                                'completed' => 'info',
                                default => 'primary',
                            },
                        ];
                    });

                // PERFORMANCE: Exclude massive LONGTEXT columns (raw, analysis, details, transcript)
                $recentCalls = Call::select(['id', 'customer_id', 'direction', 'status', 'called_at', 'created_at'])
                    ->with('customer:id,name')
                    ->whereDate('called_at', '>=', now()->subDays(7))
                    ->limit(10)
                    ->get()
                    ->map(function ($call) {
                        return [
                            'id' => $call->id . '_call',
                            'type' => 'call',
                            'customer_name' => $call->customer?->name ?? 'Unbekannt',
                            'customer_id' => $call->customer_id,
                            'description' => match($call->direction) {
                                'inbound' => 'Eingehender Anruf',
                                'outbound' => 'Ausgehender Anruf',
                                default => 'Anruf',
                            },
                            'status' => $call->status,
                            'timestamp' => $call->called_at,
                            'icon' => 'heroicon-o-phone',
                            'color' => match($call->status) {
                                'answered' => 'success',
                                'missed' => 'danger',
                                default => 'warning',
                            },
                        ];
                    });

                $recentJourneyChanges = Customer::whereNotNull('journey_status_updated_at')
                    ->whereDate('journey_status_updated_at', '>=', now()->subDays(7))
                    ->limit(10)
                    ->get()
                    ->map(function ($customer) {
                        return [
                            'id' => $customer->id . '_journey',
                            'type' => 'journey',
                            'customer_name' => $customer->name,
                            'customer_id' => $customer->id,
                            'description' => 'Journey Status: ' . match($customer->journey_status) {
                                'initial_contact' => 'Erstkontakt',
                                'appointment_scheduled' => 'Termin vereinbart',
                                'appointment_completed' => 'Termin wahrgenommen',
                                'regular_customer' => 'Stammkunde',
                                'vip_customer' => 'VIP Kunde',
                                default => $customer->journey_status,
                            },
                            'status' => $customer->journey_status,
                            'timestamp' => $customer->journey_status_updated_at,
                            'icon' => 'heroicon-o-map',
                            'color' => 'primary',
                        ];
                    });

                // Merge and sort by timestamp
                return collect()
                    ->merge($recentAppointments)
                    ->merge($recentCalls)
                    ->merge($recentJourneyChanges)
                    ->sortByDesc('timestamp')
                    ->take(15);
            })
            ->columns([
                Tables\Columns\TextColumn::make('timestamp')
                    ->label('Zeit')
                    ->dateTime('d.m. H:i')
                    ->sortable()
                    ->size('sm'),
                Tables\Columns\TextColumn::make('type')
                    ->label('Typ')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match($state) {
                        'appointment' => 'Termin',
                        'call' => 'Anruf',
                        'journey' => 'Journey',
                        default => $state,
                    })
                    ->color(fn (string $state): string => match($state) {
                        'appointment' => 'info',
                        'call' => 'success',
                        'journey' => 'primary',
                        default => 'secondary',
                    }),
                Tables\Columns\TextColumn::make('customer_name')
                    ->label('Kunde')
                    ->searchable()
                    ->weight('bold')
                    ->url(fn ($record) => $record['customer_id'] ?
                        "/admin/customers/{$record['customer_id']}/edit" : null)
                    ->openUrlInNewTab(),
                Tables\Columns\TextColumn::make('description')
                    ->label('Beschreibung')
                    ->limit(50)
                    ->tooltip(fn ($record) => $record['description']),
                Tables\Columns\BadgeColumn::make('status')
                    ->label('Status')
                    ->colors([
                        'success' => fn ($state) => in_array($state, ['confirmed', 'answered', 'completed']),
                        'danger' => fn ($state) => in_array($state, ['cancelled', 'missed', 'no_show']),
                        'warning' => fn ($state) => in_array($state, ['scheduled', 'busy']),
                        'primary' => fn ($state) => !in_array($state, [
                            'confirmed', 'answered', 'completed', 'cancelled',
                            'missed', 'no_show', 'scheduled', 'busy'
                        ]),
                    ])
                    ->formatStateUsing(fn (string $state): string => match($state) {
                        'scheduled' => 'Geplant',
                        'confirmed' => 'Bestätigt',
                        'completed' => 'Abgeschlossen',
                        'cancelled' => 'Abgesagt',
                        'no_show' => 'Nicht erschienen',
                        'answered' => 'Beantwortet',
                        'missed' => 'Verpasst',
                        'busy' => 'Besetzt',
                        'initial_contact' => 'Erstkontakt',
                        'appointment_scheduled' => 'Termin vereinbart',
                        'appointment_completed' => 'Termin wahrgenommen',
                        'regular_customer' => 'Stammkunde',
                        'vip_customer' => 'VIP',
                        default => $state,
                    }),
            ])
            ->paginated(false)
            ->searchable(false)
            ->striped();
    }
}
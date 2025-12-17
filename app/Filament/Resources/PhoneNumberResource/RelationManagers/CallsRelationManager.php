<?php

namespace App\Filament\Resources\PhoneNumberResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use App\Filament\Resources\CallResource;

class CallsRelationManager extends RelationManager
{
    protected static string $relationship = 'calls';

    protected static ?string $title = 'Call History';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('retell_call_id')
                    ->label('Call ID')
                    ->disabled(),
                Forms\Components\DateTimePicker::make('created_at')
                    ->label('Created At')
                    ->disabled(),
                Forms\Components\TextInput::make('duration')
                    ->label('Duration (seconds)')
                    ->disabled(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(function ($query) {
                // 🔧 FIX 2025-11-24: Use string matching on to_number instead of FK phone_number_id
                // Reason: phone_number_id is not consistently set, but to_number always contains the phone number
                // This allows showing ALL calls for this phone number, not just those with FK set
                $phoneNumber = $this->getOwnerRecord()->number;
                return $query->where('to_number', $phoneNumber)
                    ->with(['appointments.service', 'customer']);
            })
            ->columns([
                Tables\Columns\TextColumn::make('retell_call_id')
                    ->label('Call ID')
                    ->searchable()
                    ->sortable()
                    ,
                Tables\Columns\TextColumn::make('customer_display')
                    ->label('Customer')
                    ->getStateUsing(function ($record) {
                        // Same logic as in CallResource
                        if ($record->customer_name) {
                            $prefix = '';
                            if ($record->customer_name_verified === true) {
                                $prefix = '✓ ';
                            } elseif ($record->customer_name_verified === false) {
                                $prefix = '? ';
                            }
                            return $prefix . $record->customer_name;
                        }

                        if ($record->customer_id && $record->customer) {
                            return '✓ ' . $record->customer->name;
                        }

                        return $record->from_number === 'anonymous' ? 'Anonym' : 'Unknown';
                    })
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('service_type')
                    ->label('Service / Terminwunsch')
                    ->html()
                    ->getStateUsing(function ($record) {
                        try {
                            $appointments = $record->appointments()->with('service')->get();

                            if ($appointments->isEmpty()) {
                                return '<span class="text-gray-400 text-xs">Kein Termin</span>';
                            }

                            $lines = [];
                            $seen = [];

                            foreach ($appointments as $appt) {
                                if (!$appt || !$appt->service) continue;

                                $serviceId = $appt->service->id;
                                if (in_array($serviceId, $seen)) continue;
                                $seen[] = $serviceId;

                                $name = ($appt->service->display_name && trim($appt->service->display_name) !== '')
                                    ? $appt->service->display_name
                                    : $appt->service->name;
                                $price = $appt->service->price;

                                $isCancelled = $appt->status === 'cancelled';

                                if ($isCancelled) {
                                    $lines[] = '<span class="text-xs text-orange-600 line-through">🚫 ' . $name . '</span>';
                                } else {
                                    $priceText = $price ? ' • ' . number_format($price, 2) . '€' : '';
                                    $lines[] = '<span class="text-xs text-gray-700">✓ ' . $name . $priceText . '</span>';
                                }
                            }

                            return implode('<br>', $lines) ?: '<span class="text-gray-400 text-xs">-</span>';
                        } catch (\Exception $e) {
                            return '<span class="text-gray-400 text-xs">-</span>';
                        }
                    })
                    ->wrap(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime('d.m.Y H:i:s')
                    ->sortable(),
                Tables\Columns\TextColumn::make('duration')
                    ->label('Duration')
                    ->formatStateUsing(function ($state) {
                        if (!$state) return 'N/A';
                        $minutes = floor($state / 60);
                        $seconds = $state % 60;
                        return sprintf('%d:%02d', $minutes, $seconds);
                    })
                    ->sortable(),
                Tables\Columns\BadgeColumn::make('status')
                    ->label('Status')
                    ->colors([
                        'success' => 'completed',
                        'warning' => 'in_progress',
                        'danger' => 'failed',
                        'secondary' => 'no_answer',
                    ]),
                Tables\Columns\TextColumn::make('direction')
                    ->label('Direction')
                    ->badge()
                    ->colors([
                        'primary' => 'inbound',
                        'success' => 'outbound',
                    ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'completed' => 'Completed',
                        'in_progress' => 'In Progress',
                        'failed' => 'Failed',
                        'no_answer' => 'No Answer',
                    ]),
                Tables\Filters\SelectFilter::make('direction')
                    ->options([
                        'inbound' => 'Inbound',
                        'outbound' => 'Outbound',
                    ]),
                Tables\Filters\Filter::make('today')
                    ->query(fn ($query) => $query->whereDate('created_at', today()))
                    ->label('Today'),
                Tables\Filters\Filter::make('this_week')
                    ->query(fn ($query) => $query->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()]))
                    ->label('This Week'),
            ])
            ->headerActions([
                // No create action for calls - they're created by the system
            ])
            ->actions([
                Tables\Actions\Action::make('view')
                    ->label('Ansehen')
                    ->icon('heroicon-o-eye')
                    ->url(fn ($record) => CallResource::getUrl('view', ['record' => $record]))
                    ->openUrlInNewTab(),
            ])
            ->bulkActions([
                // No bulk actions for call history
            ])
            ->emptyStateHeading('No calls yet')
            ->emptyStateDescription('Call history will appear here once calls are made.')
            ->emptyStateIcon('heroicon-o-phone');
    }
}
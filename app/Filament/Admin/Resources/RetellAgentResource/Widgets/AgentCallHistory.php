<?php

namespace App\Filament\Admin\Resources\RetellAgentResource\Widgets;

use App\Models\Call;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Model;

class AgentCallHistory extends BaseWidget
{
    public ?Model $record = null;

    protected int | string | array $columnSpan = 'full';
    
    protected static ?string $heading = 'Recent Calls';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Call::query()
                    ->where('metadata->agent_id', $this->record->retell_agent_id)
                    ->where('company_id', $this->record->company_id)
                    ->latest()
            )
            ->columns([
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Date & Time')
                    ->dateTime()
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('customer.full_name')
                    ->label('Customer')
                    ->searchable(),
                
                Tables\Columns\TextColumn::make('to_number')
                    ->label('Phone')
                    ->formatStateUsing(fn ($state) => $this->maskPhoneNumber($state)),
                
                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'success' => 'completed',
                        'warning' => 'in-progress',
                        'danger' => fn ($state) => in_array($state, ['failed', 'no-answer']),
                    ]),
                
                Tables\Columns\TextColumn::make('duration_sec')
                    ->label('Duration')
                    ->formatStateUsing(fn ($state) => gmdate('i:s', $state))
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('metadata.purpose')
                    ->label('Purpose')
                    ->formatStateUsing(fn ($state) => ucfirst(str_replace('_', ' ', $state ?? 'unknown'))),
                
                Tables\Columns\TextColumn::make('metadata.outcome')
                    ->label('Outcome')
                    ->formatStateUsing(fn ($state) => ucfirst($state ?? 'pending')),
                
                Tables\Columns\TextColumn::make('metadata.satisfaction_rating')
                    ->label('Rating')
                    ->formatStateUsing(fn ($state) => $state ? $state . '/5' : '-')
                    ->color(fn ($state) => match(true) {
                        $state >= 4 => 'success',
                        $state >= 3 => 'warning',
                        $state !== null => 'danger',
                        default => 'gray',
                    }),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'completed' => 'Completed',
                        'failed' => 'Failed',
                        'no-answer' => 'No Answer',
                        'in-progress' => 'In Progress',
                    ]),
                
                Tables\Filters\Filter::make('created_at')
                    ->form([
                        Tables\Filters\DatePicker::make('from'),
                        Tables\Filters\DatePicker::make('until'),
                    ])
                    ->query(function ($query, array $data) {
                        return $query
                            ->when(
                                $data['from'],
                                fn ($query, $date) => $query->whereDate('created_at', '>=', $date),
                            )
                            ->when(
                                $data['until'],
                                fn ($query, $date) => $query->whereDate('created_at', '<=', $date),
                            );
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->url(fn ($record) => route('filament.admin.resources.calls.view', $record)),
            ])
            ->defaultSort('created_at', 'desc')
            ->paginated([10, 25, 50]);
    }
    
    protected function maskPhoneNumber(string $phone): string
    {
        if (strlen($phone) > 8) {
            return substr($phone, 0, 3) . str_repeat('*', strlen($phone) - 5) . substr($phone, -2);
        }
        return $phone;
    }
}
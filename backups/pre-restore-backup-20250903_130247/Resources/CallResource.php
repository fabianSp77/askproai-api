<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\CallResource\Pages;
use App\Models\Call;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;
use Illuminate\Database\Eloquent\Builder;

class CallResource extends Resource
{
    protected static ?string $model = Call::class;
    protected static ?string $navigationIcon = 'heroicon-o-phone';
    protected static ?string $navigationGroup = 'Operations';
    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Call Information')
                    ->schema([
                        Forms\Components\TextInput::make('call_id')
                            ->label('Call ID')
                            ->disabled(),
                        Forms\Components\TextInput::make('retell_call_id')
                            ->label('Retell Call ID')
                            ->disabled(),
                        Forms\Components\TextInput::make('from_number')
                            ->label('From Number')
                            ->tel(),
                        Forms\Components\TextInput::make('to_number')
                            ->label('To Number')
                            ->tel(),
                        Forms\Components\Select::make('customer_id')
                            ->relationship('customer', 'name')
                            ->searchable()
                            ->preload(),
                        Forms\Components\Select::make('agent_id')
                            ->relationship('agent', 'name')
                            ->searchable(),
                    ])->columns(2),
                
                Forms\Components\Section::make('Call Details')
                    ->schema([
                        Forms\Components\DateTimePicker::make('start_timestamp')
                            ->label('Start Time'),
                        Forms\Components\DateTimePicker::make('end_timestamp')
                            ->label('End Time'),
                        Forms\Components\TextInput::make('duration_sec')
                            ->label('Duration (seconds)')
                            ->numeric(),
                        Forms\Components\Select::make('call_status')
                            ->label('Status')
                            ->options([
                                'completed' => 'Completed',
                                'missed' => 'Missed',
                                'ongoing' => 'Ongoing',
                                'in_progress' => 'In Progress',
                                'failed' => 'Failed',
                            ]),
                        Forms\Components\Toggle::make('call_successful')
                            ->label('Successful Call'),
                        Forms\Components\TextInput::make('disconnection_reason')
                            ->label('Disconnection Reason'),
                    ])->columns(2),
                
                Forms\Components\Section::make('Transcript & Analysis')
                    ->schema([
                        Forms\Components\Textarea::make('transcript')
                            ->label('Full Transcript')
                            ->rows(10)
                            ->columnSpanFull(),
                        Forms\Components\Textarea::make('summary')
                            ->label('Call Summary')
                            ->rows(4)
                            ->columnSpanFull(),
                        Forms\Components\KeyValue::make('analysis')
                            ->label('AI Analysis')
                            ->columnSpanFull(),
                    ]),
                
                Forms\Components\Section::make('Cost & Metadata')
                    ->schema([
                        Forms\Components\TextInput::make('cost_cents')
                            ->label('Cost (Cents)')
                            ->numeric()
                            ->prefix('¢'),
                        Forms\Components\TextInput::make('sentiment_score')
                            ->label('Sentiment Score')
                            ->numeric(),
                        Forms\Components\TextInput::make('audio_url')
                            ->label('Audio URL')
                            ->url(),
                        Forms\Components\TextInput::make('recording_url')
                            ->label('Recording URL')
                            ->url(),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('call_id')
                    ->label('Call ID')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('customer.name')
                    ->label('Customer')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('from_number')
                    ->label('From')
                    ->searchable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('to_number')
                    ->label('To')
                    ->searchable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('start_timestamp')
                    ->label('Date/Time')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),
                Tables\Columns\TextColumn::make('duration_sec')
                    ->label('Duration')
                    ->formatStateUsing(fn ($state) => $state ? gmdate('i:s', $state) : '-')
                    ->sortable(),
                Tables\Columns\IconColumn::make('call_successful')
                    ->label('Success')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger'),
                Tables\Columns\BadgeColumn::make('call_status')
                    ->label('Status')
                    ->colors([
                        'success' => 'completed',
                        'danger' => 'missed',
                        'warning' => 'ongoing',
                        'info' => 'in_progress',
                        'gray' => 'failed',
                    ]),
                Tables\Columns\TextColumn::make('agent.name')
                    ->label('Agent')
                    ->searchable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('cost_cents')
                    ->label('Cost')
                    ->money('EUR', divideBy: 100)
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('sentiment_score')
                    ->label('Sentiment')
                    ->numeric()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('summary')
                    ->label('Summary')
                    ->limit(50)
                    ->tooltip(function (Tables\Columns\TextColumn $column): ?string {
                        $state = $column->getState();
                        return strlen($state) > 50 ? $state : null;
                    })
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('call_successful')
                    ->label('Success Status')
                    ->options([
                        '1' => 'Successful',
                        '0' => 'Failed',
                    ]),
                SelectFilter::make('call_status')
                    ->label('Call Status')
                    ->options([
                        'completed' => 'Completed',
                        'missed' => 'Missed',
                        'ongoing' => 'Ongoing',
                        'in_progress' => 'In Progress',
                        'failed' => 'Failed',
                    ]),
                SelectFilter::make('agent_id')
                    ->label('Agent')
                    ->relationship('agent', 'name'),
                Filter::make('has_transcript')
                    ->query(fn (Builder $query): Builder => $query->whereNotNull('transcript')),
                Filter::make('has_recording')
                    ->query(fn (Builder $query): Builder => $query->whereNotNull('recording_url')),
                Filter::make('today')
                    ->query(fn (Builder $query): Builder => $query->whereDate('start_timestamp', today())),
                Filter::make('this_week')
                    ->query(fn (Builder $query): Builder => $query->whereBetween('start_timestamp', [now()->startOfWeek(), now()->endOfWeek()])),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('playRecording')
                    ->label('Play')
                    ->icon('heroicon-o-play')
                    ->url(fn (Call $record): string => $record->recording_url ?? '#')
                    ->openUrlInNewTab()
                    ->visible(fn (Call $record): bool => !empty($record->recording_url)),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\BulkAction::make('export')
                        ->label('Export Selected')
                        ->icon('heroicon-o-arrow-down-tray')
                        ->action(function ($records) {
                            // Export logic here
                        }),
                ]),
            ])
            ->defaultSort('start_timestamp', 'desc');
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Call Overview')
                    ->schema([
                        Infolists\Components\Grid::make(3)
                            ->schema([
                                Infolists\Components\TextEntry::make('call_id')
                                    ->label('Call ID')
                                    ->badge(),
                                Infolists\Components\TextEntry::make('retell_call_id')
                                    ->label('Retell Call ID'),
                                Infolists\Components\TextEntry::make('conversation_id')
                                    ->label('Conversation ID'),
                                Infolists\Components\TextEntry::make('from_number')
                                    ->label('From Number')
                                    ->icon('heroicon-o-phone'),
                                Infolists\Components\TextEntry::make('to_number')
                                    ->label('To Number')
                                    ->icon('heroicon-o-phone-arrow-down-left'),
                                Infolists\Components\IconEntry::make('call_successful')
                                    ->label('Successful')
                                    ->boolean(),
                            ]),
                    ]),
                
                Infolists\Components\Section::make('Timing & Duration')
                    ->schema([
                        Infolists\Components\Grid::make(4)
                            ->schema([
                                Infolists\Components\TextEntry::make('start_timestamp')
                                    ->label('Start Time')
                                    ->dateTime('d.m.Y H:i:s'),
                                Infolists\Components\TextEntry::make('end_timestamp')
                                    ->label('End Time')
                                    ->dateTime('d.m.Y H:i:s'),
                                Infolists\Components\TextEntry::make('duration_sec')
                                    ->label('Duration')
                                    ->formatStateUsing(fn ($state) => $state ? gmdate('H:i:s', $state) : '-'),
                                Infolists\Components\TextEntry::make('wait_time_sec')
                                    ->label('Wait Time')
                                    ->suffix(' seconds'),
                            ]),
                    ]),
                
                Infolists\Components\Section::make('Participants')
                    ->schema([
                        Infolists\Components\Grid::make(3)
                            ->schema([
                                Infolists\Components\TextEntry::make('customer.name')
                                    ->label('Customer')
                                    ->icon('heroicon-o-user'),
                                Infolists\Components\TextEntry::make('agent.name')
                                    ->label('AI Agent')
                                    ->icon('heroicon-o-cpu-chip'),
                                Infolists\Components\TextEntry::make('branch.name')
                                    ->label('Branch')
                                    ->icon('heroicon-o-building-office'),
                            ]),
                    ])->columns(3),
                
                Infolists\Components\Section::make('Call Analysis')
                    ->schema([
                        Infolists\Components\Grid::make(2)
                            ->schema([
                                Infolists\Components\TextEntry::make('sentiment')
                                    ->label('Sentiment')
                                    ->badge()
                                    ->color(fn (?string $state): string => match ($state ?? 'neutral') {
                                        'positive' => 'success',
                                        'negative' => 'danger',
                                        'neutral' => 'gray',
                                        default => 'gray',
                                    }),
                                Infolists\Components\TextEntry::make('sentiment_score')
                                    ->label('Sentiment Score')
                                    ->numeric(),
                                Infolists\Components\TextEntry::make('detected_language')
                                    ->label('Language'),
                                Infolists\Components\TextEntry::make('language_confidence')
                                    ->label('Language Confidence')
                                    ->suffix('%'),
                            ]),
                        Infolists\Components\TextEntry::make('summary')
                            ->label('Call Summary')
                            ->columnSpanFull(),
                        Infolists\Components\KeyValueEntry::make('analysis')
                            ->label('AI Analysis Details')
                            ->getStateUsing(function ($record) {
                                $analysis = $record->analysis;
                                if (empty($analysis) || !is_array($analysis)) {
                                    return [];
                                }
                                // Flatten nested arrays and convert to string representations
                                $formatted = [];
                                foreach ($analysis as $key => $value) {
                                    if (is_array($value)) {
                                        $formatted[$key] = json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
                                    } elseif (is_bool($value)) {
                                        $formatted[$key] = $value ? 'Yes' : 'No';
                                    } elseif ($value === null) {
                                        $formatted[$key] = 'N/A';
                                    } else {
                                        $formatted[$key] = (string) $value;
                                    }
                                }
                                return $formatted;
                            })
                            ->columnSpanFull(),
                    ]),
                
                Infolists\Components\Section::make('Transcript')
                    ->schema([
                        Infolists\Components\TextEntry::make('transcript')
                            ->label('')
                            ->prose()
                            ->markdown()
                            ->columnSpanFull(),
                    ])
                    ->collapsible(),
                
                Infolists\Components\Section::make('Financial Information')
                    ->schema([
                        Infolists\Components\Grid::make(4)
                            ->schema([
                                Infolists\Components\TextEntry::make('cost_cents')
                                    ->label('Total Cost')
                                    ->money('EUR', divideBy: 100),
                                Infolists\Components\TextEntry::make('retell_cost')
                                    ->label('Retell Cost')
                                    ->money('EUR'),
                                Infolists\Components\TextEntry::make('cost_per_minute')
                                    ->label('Cost per Minute')
                                    ->getStateUsing(function ($record) {
                                        if ($record->cost_cents && $record->duration_sec) {
                                            $minutes = $record->duration_sec / 60;
                                            return $minutes > 0 ? round(($record->cost_cents / 100) / $minutes, 2) : 0;
                                        }
                                        return 0;
                                    })
                                    ->money('EUR'),
                                Infolists\Components\TextEntry::make('duration_minutes')
                                    ->label('Billable Minutes')
                                    ->numeric(decimalPlaces: 2),
                            ]),
                    ]),
                
                Infolists\Components\Section::make('Related Records')
                    ->schema([
                        Infolists\Components\TextEntry::make('appointment.starts_at')
                            ->label('Appointment')
                            ->dateTime()
                            ->visible(fn ($record) => $record->appointment_id),
                        Infolists\Components\TextEntry::make('calcom_booking_id')
                            ->label('Cal.com Booking ID')
                            ->badge()
                            ->visible(fn ($record) => $record->calcom_booking_id),
                        Infolists\Components\TextEntry::make('appointment_requested')
                            ->label('Appointment Requested')
                            ->badge()
                            ->color(fn (bool $state): string => $state ? 'success' : 'gray'),
                        Infolists\Components\TextEntry::make('appointment_made')
                            ->label('Appointment Made')
                            ->badge()
                            ->color(fn (bool $state): string => $state ? 'success' : 'danger'),
                    ])
                    ->columns(2),
                
                Infolists\Components\Section::make('Technical Details')
                    ->schema([
                        Infolists\Components\Grid::make(2)
                            ->schema([
                                Infolists\Components\TextEntry::make('disconnection_reason')
                                    ->label('Disconnection Reason'),
                                Infolists\Components\TextEntry::make('call_type')
                                    ->label('Call Type'),
                                Infolists\Components\TextEntry::make('direction')
                                    ->label('Direction')
                                    ->badge(),
                                Infolists\Components\TextEntry::make('agent_version')
                                    ->label('Agent Version'),
                            ]),
                        Infolists\Components\TextEntry::make('audio_url')
                            ->label('Audio URL')
                            ->url(fn ($record) => $record->audio_url)
                            ->openUrlInNewTab()
                            ->visible(fn ($record) => $record->audio_url),
                        Infolists\Components\TextEntry::make('recording_url')
                            ->label('Recording URL')
                            ->url(fn ($record) => $record->recording_url)
                            ->openUrlInNewTab()
                            ->visible(fn ($record) => $record->recording_url),
                        Infolists\Components\TextEntry::make('public_log_url')
                            ->label('Public Log URL')
                            ->url(fn ($record) => $record->public_log_url)
                            ->openUrlInNewTab()
                            ->visible(fn ($record) => $record->public_log_url),
                    ])
                    ->collapsible()
                    ->collapsed(),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCalls::route('/'),
            'create' => Pages\CreateCall::route('/create'),
            'view' => Pages\ViewCall::route('/{record}'),
            'edit' => Pages\EditCall::route('/{record}/edit'),
        ];
    }
    
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['customer', 'agent', 'branch', 'appointment']);
    }
}
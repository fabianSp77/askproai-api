<?php

namespace App\Filament\Resources;

use App\Filament\Resources\RetellCallSessionResource\Pages;
use App\Models\RetellCallSession;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\HtmlString;

class RetellCallSessionResource extends Resource
{
    protected static ?string $model = RetellCallSession::class;

    protected static ?string $navigationIcon = 'heroicon-o-phone';

    protected static ?string $navigationLabel = 'Call Monitoring';

    protected static ?string $navigationGroup = 'Retell AI';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('call_id')
                    ->label('Call ID')
                    ->disabled(),
                Forms\Components\TextInput::make('customer.name')
                    ->label('Customer')
                    ->disabled(),
                Forms\Components\TextInput::make('agent_id')
                    ->label('Agent ID')
                    ->disabled(),
                Forms\Components\DateTimePicker::make('started_at')
                    ->label('Started At')
                    ->disabled(),
                Forms\Components\TextInput::make('duration_ms')
                    ->label('Duration (ms)')
                    ->disabled(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('call_id')
                    ->label('Call ID')
                    ->searchable()
                    ->copyable()
                    ->sortable()
                    ->limit(20),

                BadgeColumn::make('call_status')
                    ->label('Status')
                    ->colors([
                        'warning' => 'in_progress',
                        'success' => 'completed',
                        'danger' => 'failed',
                    ])
                    ->sortable(),

                TextColumn::make('customer.name')
                    ->label('Customer')
                    ->searchable()
                    ->default('-'),

                TextColumn::make('company_branch')
                    ->label('Unternehmen / Filiale')
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query
                            ->leftJoin('companies', 'retell_call_sessions.company_id', '=', 'companies.id')
                            ->where('companies.name', 'like', "%{$search}%")
                            ->orWhereHas('call.branch', function ($q) use ($search) {
                                $q->where('branches.name', 'like', "%{$search}%")
                                  ->orWhere('branches.phone_number', 'like', "%{$search}%");
                            });
                    })
                    ->wrap(),

                TextColumn::make('phone_number')
                    ->label('📞 Telefon')
                    ->default('-')
                    ->copyable()
                    ->toggleable(isToggledHiddenByDefault: false)
                    ->visible(true),

                TextColumn::make('call_id')
                    ->label('🧪 TEST: Call ID Copy')
                    ->copyable()
                    ->limit(15)
                    ->toggleable(isToggledHiddenByDefault: false),

                TextColumn::make('started_at')
                    ->label('Started')
                    ->dateTime('Y-m-d H:i:s')
                    ->sortable(),

                TextColumn::make('duration_seconds')
                    ->label('Duration')
                    ->getStateUsing(fn ($record) => $record->getDurationSeconds() ? $record->getDurationSeconds() . 's' : '-')
                    ->sortable(query: function (Builder $query, string $direction): Builder {
                        return $query->orderBy('duration_ms', $direction);
                    }),

                TextColumn::make('function_call_count')
                    ->label('Functions')
                    ->sortable()
                    ->alignCenter(),

                BadgeColumn::make('error_count')
                    ->label('Errors')
                    ->colors([
                        'success' => 0,
                        'warning' => fn ($state) => $state > 0 && $state <= 2,
                        'danger' => fn ($state) => $state > 2,
                    ])
                    ->sortable()
                    ->alignCenter(),

                TextColumn::make('avg_response_time_ms')
                    ->label('Avg Response')
                    ->suffix(' ms')
                    ->sortable()
                    ->alignCenter(),
            ])
            ->defaultSort('started_at', 'desc')
            ->filters([
                SelectFilter::make('call_status')
                    ->label('Status')
                    ->options([
                        'in_progress' => 'In Progress',
                        'completed' => 'Completed',
                        'failed' => 'Failed',
                    ]),

                Filter::make('has_errors')
                    ->label('Has Errors')
                    ->query(fn (Builder $query): Builder => $query->where('error_count', '>', 0)),

                Filter::make('recent')
                    ->label('Last 24 Hours')
                    ->query(fn (Builder $query): Builder => $query->where('started_at', '>=', now()->subHours(24))),

                Filter::make('slow_calls')
                    ->label('Slow Calls (>5s)')
                    ->query(fn (Builder $query): Builder => $query->where('duration_ms', '>', 5000)),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([
                // No bulk actions for monitoring data
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
            'index' => Pages\ListRetellCallSessions::route('/'),
            'view' => Pages\ViewRetellCallSession::route('/{record}'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with([
                'customer',
                'company',
                'call.branch',  // Critical: Needed for company_branch column description/tooltip
            ])
            ->withCount([
                'functionTraces',
                'errors',
            ]);
    }
}

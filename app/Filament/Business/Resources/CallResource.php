<?php

namespace App\Filament\Business\Resources;

use App\Filament\Business\Resources\CallResource\Pages;
use App\Models\Call;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class CallResource extends Resource
{
    protected static ?string $model = Call::class;

    protected static ?string $navigationIcon = 'heroicon-o-phone';
    
    protected static ?string $navigationGroup = 'Calls & Appointments';
    
    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Call Information')
                    ->schema([
                        Forms\Components\TextInput::make('from_phone_number')
                            ->label('From')
                            ->disabled(),
                        Forms\Components\TextInput::make('to_phone_number')
                            ->label('To')
                            ->disabled(),
                        Forms\Components\DateTimePicker::make('start_time')
                            ->label('Start Time')
                            ->disabled(),
                        Forms\Components\TextInput::make('duration_seconds')
                            ->label('Duration (seconds)')
                            ->disabled(),
                        Forms\Components\Select::make('status')
                            ->options([
                                'completed' => 'Completed',
                                'no_answer' => 'No Answer',
                                'busy' => 'Busy',
                                'failed' => 'Failed',
                            ])
                            ->disabled(),
                    ])
                    ->columns(2),
                    
                Forms\Components\Section::make('Call Details')
                    ->schema([
                        Forms\Components\Textarea::make('transcript')
                            ->label('Transcript')
                            ->rows(10)
                            ->columnSpanFull()
                            ->disabled(),
                        Forms\Components\Textarea::make('summary')
                            ->label('AI Summary')
                            ->rows(4)
                            ->columnSpanFull()
                            ->disabled(),
                    ])
                    ->collapsible(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('from_phone_number')
                    ->label('From')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('to_phone_number')
                    ->label('To')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('start_time')
                    ->label('Date & Time')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),
                Tables\Columns\TextColumn::make('duration_seconds')
                    ->label('Duration')
                    ->formatStateUsing(fn ($state) => gmdate('i:s', $state))
                    ->sortable(),
                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'success' => 'completed',
                        'warning' => 'no_answer',
                        'danger' => fn ($state) => in_array($state, ['busy', 'failed']),
                    ]),
                Tables\Columns\TextColumn::make('customer.name')
                    ->label('Customer')
                    ->searchable()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'completed' => 'Completed',
                        'no_answer' => 'No Answer',
                        'busy' => 'Busy',
                        'failed' => 'Failed',
                    ]),
                Tables\Filters\Filter::make('today')
                    ->query(fn (Builder $query): Builder => $query->whereDate('start_time', today()))
                    ->label('Today'),
                Tables\Filters\Filter::make('this_week')
                    ->query(fn (Builder $query): Builder => $query->whereBetween('start_time', [now()->startOfWeek(), now()->endOfWeek()]))
                    ->label('This Week'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\ExportBulkAction::make(),
                ]),
            ])
            ->defaultSort('start_time', 'desc');
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
            'view' => Pages\ViewCall::route('/{record}'),
        ];
    }
    
    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user = Auth::user();
        
        // Apply permission-based filtering
        if ($user->hasRole('company_staff')) {
            // Staff can only see their own calls
            $query->where('assigned_to', $user->id);
        } elseif ($user->hasRole('company_manager')) {
            // Managers see team calls
            $teamIds = $user->teamMembers()->pluck('id')->push($user->id);
            $query->whereIn('assigned_to', $teamIds);
        }
        // company_owner and company_admin see all company calls (handled by tenant scope)
        
        return $query;
    }
}
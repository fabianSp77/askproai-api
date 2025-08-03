<?php

namespace App\Filament\Business\Widgets;

use App\Models\Call;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class RecentCalls extends BaseWidget
{
    protected static ?int $sort = 3;
    
    protected int | string | array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query($this->getTableQuery())
            ->columns([
                Tables\Columns\TextColumn::make('from_phone_number')
                    ->label('From')
                    ->searchable(),
                Tables\Columns\TextColumn::make('start_time')
                    ->label('Time')
                    ->dateTime('H:i')
                    ->sortable(),
                Tables\Columns\TextColumn::make('duration_seconds')
                    ->label('Duration')
                    ->formatStateUsing(fn ($state) => gmdate('i:s', $state)),
                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'success' => 'completed',
                        'warning' => 'no_answer',
                        'danger' => fn ($state) => in_array($state, ['busy', 'failed']),
                    ]),
            ])
            ->defaultSort('start_time', 'desc')
            ->paginated([5]);
    }
    
    protected function getTableQuery(): Builder
    {
        $user = Auth::user();
        $query = Call::query()->where('company_id', $user->company_id);
        
        // Apply role-based filtering
        if ($user->hasRole('company_staff')) {
            $query->where('assigned_to', $user->id);
        } elseif ($user->hasRole('company_manager')) {
            $teamIds = $user->teamMembers()->pluck('id')->push($user->id);
            $query->whereIn('assigned_to', $teamIds);
        }
        
        return $query->whereDate('created_at', today());
    }
    
    protected function getTableHeading(): string
    {
        return 'Today\'s Recent Calls';
    }
}
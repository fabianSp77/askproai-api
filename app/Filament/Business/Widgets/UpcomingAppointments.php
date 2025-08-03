<?php

namespace App\Filament\Business\Widgets;

use App\Models\Appointment;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class UpcomingAppointments extends BaseWidget
{
    protected static ?int $sort = 4;
    
    protected int | string | array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query($this->getTableQuery())
            ->columns([
                Tables\Columns\TextColumn::make('customer.name')
                    ->label('Customer')
                    ->searchable(),
                Tables\Columns\TextColumn::make('service.name')
                    ->label('Service'),
                Tables\Columns\TextColumn::make('start_time')
                    ->label('Date & Time')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),
                Tables\Columns\TextColumn::make('duration')
                    ->label('Duration')
                    ->suffix(' min'),
                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'success' => 'confirmed',
                        'warning' => 'scheduled',
                        'danger' => 'cancelled',
                    ]),
            ])
            ->defaultSort('start_time', 'asc')
            ->paginated([5]);
    }
    
    protected function getTableQuery(): Builder
    {
        $user = Auth::user();
        $query = Appointment::query()->where('company_id', $user->company_id);
        
        // Apply role-based filtering
        if ($user->hasRole('company_staff')) {
            $query->where('staff_id', $user->id);
        } elseif ($user->hasRole('company_manager')) {
            $teamIds = $user->teamMembers()->pluck('id')->push($user->id);
            $query->whereIn('staff_id', $teamIds);
        }
        
        return $query->where('start_time', '>=', now())
            ->where('status', '!=', 'cancelled');
    }
    
    protected function getTableHeading(): string
    {
        return 'Upcoming Appointments';
    }
}
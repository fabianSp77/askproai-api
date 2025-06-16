<?php

namespace App\Filament\Admin\Resources\StaffResource\Pages;

use App\Filament\Admin\Resources\StaffResource;
use Filament\Resources\Pages\Page;
use Filament\Actions;
use App\Models\Staff;
use App\Models\Appointment;
use Carbon\Carbon;

class StaffAvailability extends Page
{
    protected static string $resource = StaffResource::class;
    
    protected static string $view = 'filament.resources.staff-resource.pages.staff-availability';
    
    protected static ?string $title = 'Verfügbarkeit';
    
    protected static ?string $navigationIcon = 'heroicon-o-calendar';
    
    public Staff $record;
    
    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('edit')
                ->label('Arbeitszeiten bearbeiten')
                ->icon('heroicon-o-pencil-square')
                ->url(fn () => StaffResource::getUrl('edit', ['record' => $this->record]))
                ->color('gray'),
                
            Actions\Action::make('performance')
                ->label('Leistungsübersicht')
                ->icon('heroicon-o-chart-bar')
                ->modalHeading('Leistungsübersicht: ' . $this->record->name)
                ->modalContent(view('filament.staff.performance-modal', ['staff' => $this->record]))
                ->modalWidth('5xl')
                ->modalSubmitAction(false)
                ->modalCancelActionLabel('Schließen'),
        ];
    }
    
    protected function getViewData(): array
    {
        $startDate = now()->startOfWeek();
        $endDate = now()->endOfWeek()->addWeeks(1);
        
        // Get appointments for the next 2 weeks
        $appointments = $this->record->appointments()
            ->whereBetween('starts_at', [$startDate, $endDate])
            ->with(['customer', 'service', 'branch'])
            ->get();
            
        // Get working hours
        $workingHours = $this->record->workingHours()
            ->get()
            ->keyBy('day_of_week');
            
        // Generate availability data
        $availabilityData = [];
        $currentDate = $startDate->copy();
        
        while ($currentDate <= $endDate) {
            $dayOfWeek = $currentDate->dayOfWeek;
            $dayWorkingHours = $workingHours->get($dayOfWeek);
            
            $dayData = [
                'date' => $currentDate->copy(),
                'day_name' => $currentDate->translatedFormat('l'),
                'is_working_day' => $dayWorkingHours && $dayWorkingHours->is_active,
                'working_hours' => $dayWorkingHours,
                'appointments' => $appointments->filter(function ($appointment) use ($currentDate) {
                    return $appointment->starts_at->isSameDay($currentDate);
                })->sortBy('starts_at'),
                'slots' => [],
            ];
            
            // Generate time slots if it's a working day
            if ($dayData['is_working_day'] && $dayWorkingHours) {
                $slots = $this->generateTimeSlots(
                    $currentDate,
                    $dayWorkingHours,
                    $dayData['appointments']
                );
                $dayData['slots'] = $slots;
            }
            
            $availabilityData[] = $dayData;
            $currentDate->addDay();
        }
        
        return [
            'staff' => $this->record,
            'availabilityData' => $availabilityData,
            'statistics' => $this->calculateStatistics($appointments),
        ];
    }
    
    protected function generateTimeSlots($date, $workingHours, $appointments)
    {
        $slots = [];
        
        // Parse working hours
        $startTime = Carbon::parse($date->format('Y-m-d') . ' ' . $workingHours->start_time);
        $endTime = Carbon::parse($date->format('Y-m-d') . ' ' . $workingHours->end_time);
        
        // Break times
        $breakStart = $workingHours->break_start_time ? 
            Carbon::parse($date->format('Y-m-d') . ' ' . $workingHours->break_start_time) : null;
        $breakEnd = $workingHours->break_end_time ? 
            Carbon::parse($date->format('Y-m-d') . ' ' . $workingHours->break_end_time) : null;
        
        // Generate 30-minute slots
        $currentSlot = $startTime->copy();
        while ($currentSlot < $endTime) {
            $slotEnd = $currentSlot->copy()->addMinutes(30);
            
            // Check if slot is during break
            $isDuringBreak = $breakStart && $breakEnd && 
                $currentSlot >= $breakStart && $currentSlot < $breakEnd;
            
            // Check if slot has appointment
            $appointment = $appointments->first(function ($apt) use ($currentSlot, $slotEnd) {
                return $apt->starts_at < $slotEnd && $apt->ends_at > $currentSlot;
            });
            
            $slots[] = [
                'start' => $currentSlot->copy(),
                'end' => $slotEnd,
                'is_available' => !$isDuringBreak && !$appointment && $currentSlot >= now(),
                'is_break' => $isDuringBreak,
                'is_past' => $currentSlot < now(),
                'appointment' => $appointment,
            ];
            
            $currentSlot->addMinutes(30);
        }
        
        return $slots;
    }
    
    protected function calculateStatistics($appointments)
    {
        $totalSlots = 0;
        $bookedSlots = 0;
        $totalWorkingHours = 0;
        
        // Calculate for the displayed period
        foreach ($this->record->workingHours as $wh) {
            if ($wh->is_active) {
                $start = Carbon::parse($wh->start_time);
                $end = Carbon::parse($wh->end_time);
                $break = 0;
                
                if ($wh->break_start_time && $wh->break_end_time) {
                    $breakStart = Carbon::parse($wh->break_start_time);
                    $breakEnd = Carbon::parse($wh->break_end_time);
                    $break = $breakStart->diffInMinutes($breakEnd);
                }
                
                $workingMinutes = $start->diffInMinutes($end) - $break;
                $totalWorkingHours += $workingMinutes / 60;
                $totalSlots += $workingMinutes / 30; // 30-minute slots
            }
        }
        
        // Calculate booked time
        $bookedMinutes = $appointments->sum(function ($apt) {
            return $apt->starts_at->diffInMinutes($apt->ends_at);
        });
        $bookedSlots = $bookedMinutes / 30;
        
        return [
            'total_working_hours' => round($totalWorkingHours * 2, 1), // For 2 weeks
            'total_appointments' => $appointments->count(),
            'utilization_rate' => $totalSlots > 0 ? round(($bookedSlots / ($totalSlots * 2)) * 100, 1) : 0,
            'average_appointment_duration' => $appointments->count() > 0 ? 
                round($bookedMinutes / $appointments->count()) : 0,
        ];
    }
}
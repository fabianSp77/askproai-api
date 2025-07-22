<?php

namespace App\Filament\Admin\Resources\CallResource\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use App\Models\Call;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class CallPerformanceWidget extends BaseWidget
{
    protected static ?int $sort = 1;
    
    protected function getStats(): array
    {
        try {
            Log::info('CallPerformanceWidget: Starting data collection');
            
            // Cache key mit aktuellem Tag für tägliche Invalidierung
            $cacheKey = 'call_performance_widget_' . (auth()->user()?->company_id ?? 'all') . '_' . today()->format('Y-m-d');
            
            return Cache::remember($cacheKey, 300, function () {
                $stats = [];
                
                // 1. Anrufannahme-Quote
                $stats[] = $this->getAnswerRateStat();
                
                // 2. Terminbuchungs-Erfolg
                $stats[] = $this->getBookingSuccessStat();
                
                // 3. Follow-up Potenzial
                $stats[] = $this->getFollowUpPotentialStat();
                
                // 4. Kosten-Effizienz
                $stats[] = $this->getCostEfficiencyStat();
                
                Log::info('CallPerformanceWidget: Data collection completed', [
                    'stats_count' => count($stats)
                ]);
                
                return $stats;
            });
        } catch (\Exception $e) {
            Log::error('CallPerformanceWidget: Error collecting stats', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            // Fallback bei Fehler
            return [
                Stat::make('Fehler', 'Daten nicht verfügbar')
                    ->description('Bitte Support kontaktieren')
                    ->color('danger')
            ];
        }
    }
    
    private function getAnswerRateStat(): Stat
    {
        $today = today();
        
        // Alle Anrufe heute
        $totalCalls = Call::whereDate('start_timestamp', $today)
            ->orWhereDate('created_at', $today)
            ->count();
        
        // Angenommene Anrufe (haben eine Dauer > 0)
        $answeredCalls = Call::where(function($query) use ($today) {
                $query->whereDate('start_timestamp', $today)
                      ->orWhereDate('created_at', $today);
            })
            ->where('duration_sec', '>', 0)
            ->count();
        
        $answerRate = $totalCalls > 0 ? round(($answeredCalls / $totalCalls) * 100) : 0;
        
        // Trend: Vergleich mit gestern
        $yesterdayTotal = Call::whereDate('start_timestamp', today()->subDay())
            ->orWhereDate('created_at', today()->subDay())
            ->count();
            
        $yesterdayAnswered = Call::where(function($query) {
                $query->whereDate('start_timestamp', today()->subDay())
                      ->orWhereDate('created_at', today()->subDay());
            })
            ->where('duration_sec', '>', 0)
            ->count();
            
        $yesterdayRate = $yesterdayTotal > 0 ? round(($yesterdayAnswered / $yesterdayTotal) * 100) : 0;
        $trend = $answerRate - $yesterdayRate;
        
        // Farbcodierung basierend auf Rate
        $color = match(true) {
            $answerRate >= 90 => 'success',
            $answerRate >= 70 => 'warning',
            default => 'danger'
        };
        
        return Stat::make('Anrufannahme-Quote', $answerRate . '%')
            ->description(
                $answeredCalls . ' von ' . $totalCalls . ' Anrufen' . 
                ($trend !== 0 ? ' (' . ($trend > 0 ? '+' : '') . $trend . '% vs. gestern)' : '')
            )
            ->descriptionIcon($trend > 0 ? 'heroicon-m-arrow-trending-up' : ($trend < 0 ? 'heroicon-m-arrow-trending-down' : 'heroicon-m-minus'))
            ->chart($this->getAnswerRateChart())
            ->color($color);
    }
    
    private function getBookingSuccessStat(): Stat
    {
        $today = today();
        
        // Anrufe mit Terminwunsch (appointment_requested = true)
        $appointmentRequests = Call::where(function($query) use ($today) {
                $query->whereDate('start_timestamp', $today)
                      ->orWhereDate('created_at', $today);
            })
            ->whereJsonContains('analysis->appointment_requested', true)
            ->count();
        
        // Erfolgreich gebuchte Termine
        $successfulBookings = Call::where(function($query) use ($today) {
                $query->whereDate('start_timestamp', $today)
                      ->orWhereDate('created_at', $today);
            })
            ->whereJsonContains('analysis->appointment_requested', true)
            ->whereNotNull('appointment_id')
            ->count();
        
        $bookingRate = $appointmentRequests > 0 ? round(($successfulBookings / $appointmentRequests) * 100) : 0;
        
        // Trend berechnen
        $yesterdayRequests = Call::where(function($query) {
                $query->whereDate('start_timestamp', today()->subDay())
                      ->orWhereDate('created_at', today()->subDay());
            })
            ->whereJsonContains('analysis->appointment_requested', true)
            ->count();
            
        $yesterdayBookings = Call::where(function($query) {
                $query->whereDate('start_timestamp', today()->subDay())
                      ->orWhereDate('created_at', today()->subDay());
            })
            ->whereJsonContains('analysis->appointment_requested', true)
            ->whereNotNull('appointment_id')
            ->count();
            
        $yesterdayRate = $yesterdayRequests > 0 ? round(($yesterdayBookings / $yesterdayRequests) * 100) : 0;
        
        return Stat::make('Terminbuchungs-Erfolg', $bookingRate . '%')
            ->description(
                $successfulBookings . ' von ' . $appointmentRequests . ' Terminwünschen erfüllt'
            )
            ->descriptionIcon('heroicon-m-calendar-days')
            ->chart($this->getBookingSuccessChart())
            ->color($bookingRate >= 80 ? 'success' : ($bookingRate >= 60 ? 'warning' : 'danger'));
    }
    
    private function getFollowUpPotentialStat(): Stat
    {
        // Anrufe mit Terminwunsch aber ohne gebuchten Termin
        $followUpNeeded = Call::whereJsonContains('analysis->appointment_requested', true)
            ->whereNull('appointment_id')
            ->where(function($query) {
                $query->whereDate('created_at', '>=', now()->subDays(7));
            })
            ->count();
        
        // Zusätzlich: Negative Stimmung ohne Termin
        $negativeMood = Call::whereJsonContains('analysis->sentiment', 'negative')
            ->whereNull('appointment_id')
            ->where(function($query) {
                $query->whereDate('created_at', '>=', now()->subDays(3));
            })
            ->count();
        
        $totalFollowUp = $followUpNeeded + $negativeMood;
        
        return Stat::make('Follow-up Potenzial', $totalFollowUp)
            ->description(
                $followUpNeeded . ' unerfüllte Terminwünsche, ' . $negativeMood . ' negative Anrufe'
            )
            ->descriptionIcon('heroicon-m-exclamation-triangle')
            ->color($totalFollowUp > 10 ? 'danger' : ($totalFollowUp > 5 ? 'warning' : 'success'))
            ->extraAttributes([
                'class' => 'cursor-pointer',
                'wire:click' => '$emit("filterFollowUp")'
            ]);
    }
    
    private function getCostEfficiencyStat(): Stat
    {
        $today = today();
        
        // Gesamtkosten heute
        $totalCost = Call::where(function($query) use ($today) {
                $query->whereDate('start_timestamp', $today)
                      ->orWhereDate('created_at', $today);
            })
            ->sum('cost');
        
        // Anzahl gebuchter Termine heute
        $bookedAppointments = Call::where(function($query) use ($today) {
                $query->whereDate('start_timestamp', $today)
                      ->orWhereDate('created_at', $today);
            })
            ->whereNotNull('appointment_id')
            ->count();
        
        $costPerAppointment = $bookedAppointments > 0 ? round($totalCost / $bookedAppointments, 2) : 0;
        
        // Trend: 7-Tage Durchschnitt
        $weekCost = Call::where('created_at', '>=', now()->subDays(7))
            ->sum('cost');
            
        $weekAppointments = Call::where('created_at', '>=', now()->subDays(7))
            ->whereNotNull('appointment_id')
            ->count();
            
        $weekAverage = $weekAppointments > 0 ? round($weekCost / $weekAppointments, 2) : 0;
        
        return Stat::make('Kosten pro Termin', '€ ' . number_format($costPerAppointment, 2, ',', '.'))
            ->description(
                'Ø 7 Tage: € ' . number_format($weekAverage, 2, ',', '.') .
                ' | Gesamt heute: € ' . number_format($totalCost, 2, ',', '.')
            )
            ->descriptionIcon('heroicon-m-currency-euro')
            ->chart($this->getCostEfficiencyChart())
            ->color($costPerAppointment <= $weekAverage ? 'success' : 'warning');
    }
    
    private function getAnswerRateChart(): array
    {
        $data = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = today()->subDays($i);
            $total = Call::whereDate('start_timestamp', $date)
                ->orWhereDate('created_at', $date)
                ->count();
            $answered = Call::where(function($query) use ($date) {
                    $query->whereDate('start_timestamp', $date)
                          ->orWhereDate('created_at', $date);
                })
                ->where('duration_sec', '>', 0)
                ->count();
            
            $data[] = $total > 0 ? round(($answered / $total) * 100) : 0;
        }
        return $data;
    }
    
    private function getBookingSuccessChart(): array
    {
        $data = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = today()->subDays($i);
            $requests = Call::where(function($query) use ($date) {
                    $query->whereDate('start_timestamp', $date)
                          ->orWhereDate('created_at', $date);
                })
                ->whereJsonContains('analysis->appointment_requested', true)
                ->count();
                
            $bookings = Call::where(function($query) use ($date) {
                    $query->whereDate('start_timestamp', $date)
                          ->orWhereDate('created_at', $date);
                })
                ->whereJsonContains('analysis->appointment_requested', true)
                ->whereNotNull('appointment_id')
                ->count();
            
            $data[] = $requests > 0 ? round(($bookings / $requests) * 100) : 0;
        }
        return $data;
    }
    
    private function getCostEfficiencyChart(): array
    {
        $data = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = today()->subDays($i);
            $cost = Call::whereDate('created_at', $date)->sum('cost');
            $appointments = Call::whereDate('created_at', $date)
                ->whereNotNull('appointment_id')
                ->count();
            
            $data[] = $appointments > 0 ? round($cost / $appointments, 2) : 0;
        }
        return $data;
    }
}
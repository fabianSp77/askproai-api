<?php

declare(strict_types=1);

namespace App\Filament\Admin\Widgets;

use App\Models\Call;
use App\Models\Appointment;
use Carbon\Carbon;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class ConversionFunnelWidget extends Widget
{
    protected static string $view = 'filament.admin.widgets.conversion-funnel';
    protected static ?int $sort = 4;
    protected int | string | array $columnSpan = [
        'default' => 'full',
        'lg' => 2,
        'xl' => 1,
    ];
    
    public array $funnelData = [];
    public array $timeRanges = [
        'today' => 'Today',
        'week' => 'This Week',
        'month' => 'This Month',
    ];
    public string $selectedRange = 'today';
    
    public function mount(): void
    {
        $this->calculateFunnel();
    }
    
    public function updatedSelectedRange(): void
    {
        $this->calculateFunnel();
    }
    
    public function calculateFunnel(): void
    {
        $user = Auth::user();
        $companyId = $user->company_id;
        
        if (!$companyId) {
            $this->funnelData = [];
            return;
        }
        
        $cacheKey = "conversion_funnel_{$companyId}_{$this->selectedRange}";
        
        $this->funnelData = Cache::remember($cacheKey, 300, function () use ($companyId) {
            $dateRange = $this->getDateRange();
            
            // Stage 1: Total Calls
            $totalCalls = Call::where('company_id', $companyId)
                ->whereBetween('created_at', $dateRange)
                ->count();
            
            // Stage 2: Qualified Calls (calls that lasted > 30 seconds)
            $qualifiedCalls = Call::where('company_id', $companyId)
                ->whereBetween('created_at', $dateRange)
                ->where('duration_sec', '>', 30)
                ->count();
            
            // Stage 3: Booking Attempts (calls with appointment intent)
            $bookingAttempts = Call::where('company_id', $companyId)
                ->whereBetween('created_at', $dateRange)
                ->where(function ($query) {
                    $query->where(function($q) { $q->whereNotNull('metadata')->where('metadata', 'like', '%appointment%'); })
                        ->orWhere('call_type', 'appointment_request')
                        ->orWhere('transcript', 'like', '%termin%')
                        ->orWhere('transcript', 'like', '%appointment%');
                })
                ->count();
            
            // Stage 4: Successful Bookings
            $successfulBookings = Call::where('company_id', $companyId)
                ->whereBetween('created_at', $dateRange)
                ->where(function($q) { $q->whereNotNull('metadata')->where('metadata', 'like', '%appointment%'); })
                ->count();
            
            // Stage 5: Confirmed Appointments
            $confirmedAppointments = Appointment::whereHas('branch', function ($q) use ($companyId) {
                    $q->where('company_id', $companyId);
                })
                ->whereBetween('created_at', $dateRange)
                ->whereIn('status', ['confirmed', 'completed'])
                ->count();
            
            // Stage 6: Completed Appointments
            $completedAppointments = Appointment::whereHas('branch', function ($q) use ($companyId) {
                    $q->where('company_id', $companyId);
                })
                ->whereBetween('created_at', $dateRange)
                ->where('status', 'completed')
                ->count();
            
            return [
                'stages' => [
                    [
                        'name' => 'Total Calls',
                        'count' => $totalCalls,
                        'percentage' => 100,
                        'color' => 'blue',
                    ],
                    [
                        'name' => 'Qualified Calls',
                        'count' => $qualifiedCalls,
                        'percentage' => $totalCalls > 0 ? round(($qualifiedCalls / $totalCalls) * 100, 1) : 0,
                        'color' => 'indigo',
                    ],
                    [
                        'name' => 'Booking Attempts',
                        'count' => $bookingAttempts,
                        'percentage' => $totalCalls > 0 ? round(($bookingAttempts / $totalCalls) * 100, 1) : 0,
                        'color' => 'purple',
                    ],
                    [
                        'name' => 'Booked',
                        'count' => $successfulBookings,
                        'percentage' => $totalCalls > 0 ? round(($successfulBookings / $totalCalls) * 100, 1) : 0,
                        'color' => 'green',
                    ],
                    [
                        'name' => 'Confirmed',
                        'count' => $confirmedAppointments,
                        'percentage' => $totalCalls > 0 ? round(($confirmedAppointments / $totalCalls) * 100, 1) : 0,
                        'color' => 'emerald',
                    ],
                    [
                        'name' => 'Completed',
                        'count' => $completedAppointments,
                        'percentage' => $totalCalls > 0 ? round(($completedAppointments / $totalCalls) * 100, 1) : 0,
                        'color' => 'teal',
                    ],
                ],
                'overall_conversion' => $totalCalls > 0 ? round(($successfulBookings / $totalCalls) * 100, 1) : 0,
                'completion_rate' => $successfulBookings > 0 ? round(($completedAppointments / $successfulBookings) * 100, 1) : 0,
                'drop_off_analysis' => $this->calculateDropOffs($totalCalls, $qualifiedCalls, $bookingAttempts, $successfulBookings, $confirmedAppointments, $completedAppointments),
            ];
        });
    }
    
    private function getDateRange(): array
    {
        $end = Carbon::now();
        
        switch ($this->selectedRange) {
            case 'today':
                $start = Carbon::today();
                break;
            case 'week':
                $start = Carbon::now()->startOfWeek();
                break;
            case 'month':
                $start = Carbon::now()->startOfMonth();
                break;
            default:
                $start = Carbon::today();
        }
        
        return [$start, $end];
    }
    
    private function calculateDropOffs(int $total, int $qualified, int $attempts, int $booked, int $confirmed, int $completed): array
    {
        $dropOffs = [];
        
        if ($total > 0) {
            $dropOffs[] = [
                'stage' => 'Initial → Qualified',
                'lost' => $total - $qualified,
                'rate' => round((($total - $qualified) / $total) * 100, 1),
            ];
        }
        
        if ($qualified > 0) {
            $dropOffs[] = [
                'stage' => 'Qualified → Booking Attempt',
                'lost' => $qualified - $attempts,
                'rate' => round((($qualified - $attempts) / $qualified) * 100, 1),
            ];
        }
        
        if ($attempts > 0) {
            $dropOffs[] = [
                'stage' => 'Attempt → Booked',
                'lost' => $attempts - $booked,
                'rate' => round((($attempts - $booked) / $attempts) * 100, 1),
            ];
        }
        
        if ($booked > 0) {
            $dropOffs[] = [
                'stage' => 'Booked → Confirmed',
                'lost' => $booked - $confirmed,
                'rate' => round((($booked - $confirmed) / $booked) * 100, 1),
            ];
        }
        
        if ($confirmed > 0) {
            $dropOffs[] = [
                'stage' => 'Confirmed → Completed',
                'lost' => $confirmed - $completed,
                'rate' => round((($confirmed - $completed) / $confirmed) * 100, 1),
            ];
        }
        
        return $dropOffs;
    }
}
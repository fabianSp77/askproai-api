<?php

namespace App\Services;

use App\Models\Call;
use App\Models\Company;
use App\Models\Appointment;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

/**
 * Hair Salon Billing Service
 * 
 * Handles billing calculations for hair salon MCP integration:
 * - €0.30 per minute call cost
 * - €199 one-time setup fee
 * - €49 monthly subscription
 * - Usage tracking and reporting
 * - Reseller margin calculations
 */
class HairSalonBillingService
{
    // Pricing constants
    const COST_PER_MINUTE = 0.30;
    const SETUP_FEE = 199.00;
    const MONTHLY_FEE = 49.00;
    
    // Reseller margins
    const RESELLER_MARGIN_CALLS = 0.05; // €0.05 per minute
    const RESELLER_MARGIN_SETUP = 50.00; // €50 from setup
    const RESELLER_MARGIN_MONTHLY = 10.00; // €10 from monthly
    
    protected Company $company;
    
    public function __construct(Company $company = null)
    {
        if ($company) {
            $this->company = $company;
        }
    }
    
    /**
     * Set the company for billing calculations
     */
    public function setCompany(Company $company): self
    {
        $this->company = $company;
        return $this;
    }
    
    /**
     * Calculate call cost based on duration
     */
    public function calculateCallCost(int $durationSeconds): array
    {
        $durationMinutes = ceil($durationSeconds / 60); // Round up to next minute
        $baseCost = $durationMinutes * self::COST_PER_MINUTE;
        $resellerMargin = $durationMinutes * self::RESELLER_MARGIN_CALLS;
        $netCost = $baseCost - $resellerMargin;
        
        return [
            'duration_seconds' => $durationSeconds,
            'duration_minutes' => $durationMinutes,
            'base_cost' => round($baseCost, 2),
            'reseller_margin' => round($resellerMargin, 2),
            'net_cost' => round($netCost, 2),
            'cost_per_minute' => self::COST_PER_MINUTE
        ];
    }
    
    /**
     * Track usage for a completed call
     */
    public function trackCallUsage(Call $call): array
    {
        try {
            $billing = $this->calculateCallCost($call->duration_seconds ?? 0);
            
            // Store billing information in call metadata
            $call->update([
                'metadata' => array_merge($call->metadata ?? [], [
                    'billing' => $billing,
                    'billed_at' => now()->toIso8601String(),
                    'billing_status' => 'calculated'
                ])
            ]);
            
            // Update company usage stats
            $this->updateCompanyUsageStats($billing);
            
            Log::info('Call usage tracked', [
                'call_id' => $call->id,
                'company_id' => $this->company->id,
                'billing' => $billing
            ]);
            
            return [
                'success' => true,
                'call_id' => $call->id,
                'billing' => $billing
            ];
            
        } catch (\Exception $e) {
            Log::error('HairSalonBillingService::trackCallUsage error', [
                'call_id' => $call->id,
                'error' => $e->getMessage()
            ]);
            
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Track appointment booking success
     */
    public function trackAppointmentBooking(Appointment $appointment, Call $call = null): array
    {
        try {
            $bookingValue = [
                'appointment_id' => $appointment->id,
                'service_value' => $appointment->price,
                'booking_fee' => 0, // No additional booking fee for hair salon
                'success_metric' => 1,
                'booked_at' => $appointment->created_at->toIso8601String()
            ];
            
            // If call provided, add booking tracking to call metadata
            if ($call) {
                $call->update([
                    'metadata' => array_merge($call->metadata ?? [], [
                        'booking_success' => $bookingValue
                    ])
                ]);
            }
            
            // Update success metrics
            $this->updateBookingSuccessMetrics($appointment);
            
            Log::info('Appointment booking tracked', [
                'appointment_id' => $appointment->id,
                'company_id' => $this->company->id,
                'call_id' => $call?->id
            ]);
            
            return [
                'success' => true,
                'booking_tracked' => true,
                'booking_value' => $bookingValue
            ];
            
        } catch (\Exception $e) {
            Log::error('HairSalonBillingService::trackAppointmentBooking error', [
                'appointment_id' => $appointment->id,
                'error' => $e->getMessage()
            ]);
            
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Generate monthly usage report
     */
    public function getMonthlyUsageReport(Carbon $month = null): array
    {
        try {
            $month = $month ?? Carbon::now();
            $startDate = $month->copy()->startOfMonth();
            $endDate = $month->copy()->endOfMonth();
            
            // Get calls for the month
            $calls = Call::where('company_id', $this->company->id)
                ->whereBetween('created_at', [$startDate, $endDate])
                ->get();
            
            // Calculate totals
            $totalCalls = $calls->count();
            $totalMinutes = 0;
            $totalCost = 0;
            $totalResellerMargin = 0;
            $successfulBookings = 0;
            
            foreach ($calls as $call) {
                $billing = $call->metadata['billing'] ?? null;
                if ($billing) {
                    $totalMinutes += $billing['duration_minutes'];
                    $totalCost += $billing['base_cost'];
                    $totalResellerMargin += $billing['reseller_margin'];
                }
                
                if (isset($call->metadata['booking_success'])) {
                    $successfulBookings++;
                }
            }
            
            // Get appointments for the month
            $appointments = Appointment::where('company_id', $this->company->id)
                ->whereBetween('created_at', [$startDate, $endDate])
                ->get();
            
            $totalServiceValue = $appointments->sum('price');
            $bookingConversionRate = $totalCalls > 0 ? ($successfulBookings / $totalCalls) * 100 : 0;
            
            return [
                'period' => $month->format('F Y'),
                'start_date' => $startDate->format('Y-m-d'),
                'end_date' => $endDate->format('Y-m-d'),
                'calls' => [
                    'total_calls' => $totalCalls,
                    'total_minutes' => $totalMinutes,
                    'total_cost' => round($totalCost, 2),
                    'average_duration' => $totalCalls > 0 ? round($totalMinutes / $totalCalls, 2) : 0
                ],
                'bookings' => [
                    'successful_bookings' => $successfulBookings,
                    'total_appointments' => $appointments->count(),
                    'conversion_rate' => round($bookingConversionRate, 2),
                    'total_service_value' => round($totalServiceValue, 2),
                    'average_booking_value' => $appointments->count() > 0 ? round($totalServiceValue / $appointments->count(), 2) : 0
                ],
                'billing' => [
                    'call_charges' => round($totalCost, 2),
                    'monthly_fee' => self::MONTHLY_FEE,
                    'setup_fee' => 0, // Only charged once
                    'total_charges' => round($totalCost + self::MONTHLY_FEE, 2),
                    'reseller_margin' => round($totalResellerMargin + self::RESELLER_MARGIN_MONTHLY, 2)
                ],
                'metrics' => [
                    'cost_per_booking' => $successfulBookings > 0 ? round($totalCost / $successfulBookings, 2) : 0,
                    'roi_percentage' => $totalCost > 0 ? round(($totalServiceValue / $totalCost) * 100, 2) : 0
                ]
            ];
            
        } catch (\Exception $e) {
            Log::error('HairSalonBillingService::getMonthlyUsageReport error', [
                'company_id' => $this->company->id,
                'month' => $month?->format('Y-m'),
                'error' => $e->getMessage()
            ]);
            
            return [
                'error' => 'Failed to generate usage report: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Get real-time usage statistics
     */
    public function getCurrentUsageStats(): array
    {
        $cacheKey = "usage_stats_{$this->company->id}";
        
        return Cache::remember($cacheKey, 5, function () {
            try {
                // Current month stats
                $currentMonth = Carbon::now()->startOfMonth();
                $today = Carbon::now()->startOfDay();
                
                // This month's calls
                $monthlyStats = DB::table('calls')
                    ->where('company_id', $this->company->id)
                    ->where('created_at', '>=', $currentMonth)
                    ->selectRaw('
                        COUNT(*) as total_calls,
                        SUM(duration_seconds) as total_seconds,
                        AVG(duration_seconds) as avg_seconds
                    ')
                    ->first();
                
                // Today's calls
                $dailyStats = DB::table('calls')
                    ->where('company_id', $this->company->id)
                    ->where('created_at', '>=', $today)
                    ->count();
                
                // Calculate costs
                $totalMinutes = ceil(($monthlyStats->total_seconds ?? 0) / 60);
                $monthlyCost = $totalMinutes * self::COST_PER_MINUTE;
                
                // Bookings this month
                $monthlyBookings = DB::table('appointments')
                    ->where('company_id', $this->company->id)
                    ->where('created_at', '>=', $currentMonth)
                    ->count();
                
                return [
                    'current_month' => [
                        'calls' => $monthlyStats->total_calls ?? 0,
                        'minutes' => $totalMinutes,
                        'cost' => round($monthlyCost, 2),
                        'bookings' => $monthlyBookings,
                        'conversion_rate' => ($monthlyStats->total_calls ?? 0) > 0 ? 
                            round(($monthlyBookings / $monthlyStats->total_calls) * 100, 2) : 0
                    ],
                    'today' => [
                        'calls' => $dailyStats
                    ],
                    'pricing' => [
                        'per_minute' => self::COST_PER_MINUTE,
                        'monthly_fee' => self::MONTHLY_FEE,
                        'setup_fee' => self::SETUP_FEE
                    ]
                ];
                
            } catch (\Exception $e) {
                Log::error('HairSalonBillingService::getCurrentUsageStats error', [
                    'company_id' => $this->company->id,
                    'error' => $e->getMessage()
                ]);
                
                return ['error' => $e->getMessage()];
            }
        });
    }
    
    /**
     * Update company usage statistics
     */
    protected function updateCompanyUsageStats(array $billing): void
    {
        try {
            // Use cache to accumulate stats
            $cacheKey = "company_usage_{$this->company->id}";
            $stats = Cache::get($cacheKey, [
                'total_calls' => 0,
                'total_minutes' => 0,
                'total_cost' => 0,
                'last_updated' => now()
            ]);
            
            $stats['total_calls'] += 1;
            $stats['total_minutes'] += $billing['duration_minutes'];
            $stats['total_cost'] += $billing['base_cost'];
            $stats['last_updated'] = now();
            
            Cache::put($cacheKey, $stats, 3600); // Cache for 1 hour
            
        } catch (\Exception $e) {
            Log::error('Failed to update company usage stats', [
                'company_id' => $this->company->id,
                'error' => $e->getMessage()
            ]);
        }
    }
    
    /**
     * Update booking success metrics
     */
    protected function updateBookingSuccessMetrics(Appointment $appointment): void
    {
        try {
            $cacheKey = "booking_metrics_{$this->company->id}";
            $metrics = Cache::get($cacheKey, [
                'total_bookings' => 0,
                'total_value' => 0,
                'last_booking' => null
            ]);
            
            $metrics['total_bookings'] += 1;
            $metrics['total_value'] += $appointment->price;
            $metrics['last_booking'] = now();
            
            Cache::put($cacheKey, $metrics, 3600); // Cache for 1 hour
            
        } catch (\Exception $e) {
            Log::error('Failed to update booking success metrics', [
                'appointment_id' => $appointment->id,
                'error' => $e->getMessage()
            ]);
        }
    }
    
    /**
     * Generate setup fee invoice
     */
    public function generateSetupFeeInvoice(): array
    {
        try {
            return [
                'invoice_type' => 'setup_fee',
                'company_id' => $this->company->id,
                'company_name' => $this->company->name,
                'amount' => self::SETUP_FEE,
                'reseller_margin' => self::RESELLER_MARGIN_SETUP,
                'net_amount' => self::SETUP_FEE - self::RESELLER_MARGIN_SETUP,
                'description' => 'Hair Salon MCP Integration - One-time Setup Fee',
                'due_date' => now()->addDays(14)->format('Y-m-d'),
                'items' => [
                    [
                        'description' => 'MCP Integration Setup',
                        'quantity' => 1,
                        'unit_price' => self::SETUP_FEE,
                        'total' => self::SETUP_FEE
                    ]
                ]
            ];
            
        } catch (\Exception $e) {
            Log::error('HairSalonBillingService::generateSetupFeeInvoice error', [
                'company_id' => $this->company->id,
                'error' => $e->getMessage()
            ]);
            
            return ['error' => $e->getMessage()];
        }
    }
    
    /**
     * Generate monthly subscription invoice
     */
    public function generateMonthlyInvoice(Carbon $month = null): array
    {
        try {
            $month = $month ?? Carbon::now();
            $report = $this->getMonthlyUsageReport($month);
            
            if (isset($report['error'])) {
                return $report;
            }
            
            return [
                'invoice_type' => 'monthly_usage',
                'period' => $report['period'],
                'company_id' => $this->company->id,
                'company_name' => $this->company->name,
                'subtotals' => [
                    'call_charges' => $report['billing']['call_charges'],
                    'monthly_fee' => $report['billing']['monthly_fee'],
                    'total' => $report['billing']['total_charges']
                ],
                'reseller_margin' => $report['billing']['reseller_margin'],
                'net_amount' => $report['billing']['total_charges'] - $report['billing']['reseller_margin'],
                'usage_details' => [
                    'total_calls' => $report['calls']['total_calls'],
                    'total_minutes' => $report['calls']['total_minutes'],
                    'successful_bookings' => $report['bookings']['successful_bookings'],
                    'conversion_rate' => $report['bookings']['conversion_rate'] . '%'
                ],
                'due_date' => now()->addDays(14)->format('Y-m-d')
            ];
            
        } catch (\Exception $e) {
            Log::error('HairSalonBillingService::generateMonthlyInvoice error', [
                'company_id' => $this->company->id,
                'month' => $month?->format('Y-m'),
                'error' => $e->getMessage()
            ]);
            
            return ['error' => $e->getMessage()];
        }
    }
    
    /**
     * Clear usage cache
     */
    public function clearUsageCache(): void
    {
        Cache::forget("usage_stats_{$this->company->id}");
        Cache::forget("company_usage_{$this->company->id}");
        Cache::forget("booking_metrics_{$this->company->id}");
    }
}
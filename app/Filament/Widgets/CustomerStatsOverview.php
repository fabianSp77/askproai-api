<?php

namespace App\Filament\Widgets;

use App\Models\Customer;
use App\Models\Appointment;
use App\Models\Call;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Number;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;

class CustomerStatsOverview extends BaseWidget
{
    protected static ?int $sort = 1;

    protected static ?string $pollingInterval = '300s';

    protected function getStats(): array
    {
        try {
            // Cache for 5 minutes with 5-minute key granularity (aligned with other widgets)
            $cacheMinute = floor(now()->minute / 5) * 5;
            return Cache::remember('customer_stats_overview-' . now()->format('Y-m-d-H') . '-' . str_pad($cacheMinute, 2, '0', STR_PAD_LEFT), 300, function () {
                // Optimized customer stats - single query
                $thisMonth = [now()->startOfMonth(), now()->endOfMonth()];
                $customerStats = Customer::selectRaw("
                    COUNT(*) as total_customers,
                    COUNT(CASE WHEN status = 'active' THEN 1 END) as active_customers,
                    COUNT(CASE WHEN created_at BETWEEN ? AND ? THEN 1 END) as new_this_month,
                    COUNT(CASE WHEN is_vip = 1 THEN 1 END) as vip_customers,
                    SUM(COALESCE(total_revenue, 0)) as total_revenue
                ", [$thisMonth[0], $thisMonth[1]])->first();

                $totalCustomers = $customerStats->total_customers ?? 0;
                $activeCustomers = $customerStats->active_customers ?? 0;
                $newCustomersThisMonth = $customerStats->new_this_month ?? 0;
                $vipCustomers = $customerStats->vip_customers ?? 0;
                $totalRevenue = $customerStats->total_revenue ?? 0;

                // Optimized appointment stats - single query
                $appointmentStats = Appointment::selectRaw("
                    COUNT(CASE WHEN status = 'completed' AND starts_at BETWEEN ? AND ? THEN 1 END) as completed_this_month,
                    SUM(CASE WHEN status = 'completed' AND starts_at BETWEEN ? AND ? THEN COALESCE(price, 0) ELSE 0 END) as revenue_this_month,
                    COUNT(CASE WHEN starts_at >= ? AND status IN ('scheduled', 'confirmed', 'accepted') THEN 1 END) as upcoming_appointments,
                    COUNT(CASE WHEN DATE(starts_at) = ? THEN 1 END) as today_appointments
                ", [$thisMonth[0], $thisMonth[1], $thisMonth[0], $thisMonth[1], now(), today()])->first();

                $revenueThisMonth = $appointmentStats->revenue_this_month ?? 0;
                $upcomingAppointments = $appointmentStats->upcoming_appointments ?? 0;
                $todayAppointments = $appointmentStats->today_appointments ?? 0;

                // Optimized call stats - single query
                $callStats = Call::selectRaw("
                    COUNT(CASE WHEN DATE(created_at) = ? THEN 1 END) as calls_today,
                    COUNT(CASE WHEN DATE(created_at) = ? AND status = 'missed' THEN 1 END) as missed_today
                ", [today(), today()])->first();

                $callsToday = $callStats->calls_today ?? 0;
                $missedCallsToday = $callStats->missed_today ?? 0;

                // Customer journey distribution
                $journeyDistribution = Customer::selectRaw('journey_status, COUNT(*) as count')
                    ->groupBy('journey_status')
                    ->pluck('count', 'journey_status')
                    ->toArray();

                // Optimized trend charts - single query instead of 21 individual queries
                $startDate = now()->subDays(6)->startOfDay();
                $endDate = now()->endOfDay();

                // Customer trend - single grouped query
                $customerData = Customer::whereBetween('created_at', [$startDate, $endDate])
                    ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
                    ->groupBy('date')
                    ->orderBy('date')
                    ->pluck('count', 'date')
                    ->toArray();

                // Appointment trend - single grouped query
                $appointmentData = Appointment::whereBetween('starts_at', [$startDate, $endDate])
                    ->selectRaw('DATE(starts_at) as date, COUNT(*) as count')
                    ->groupBy('date')
                    ->orderBy('date')
                    ->pluck('count', 'date')
                    ->toArray();

                // Revenue trend - single grouped query
                $revenueData = Appointment::whereBetween('starts_at', [$startDate, $endDate])
                    ->where('status', 'completed')
                    ->selectRaw('DATE(starts_at) as date, SUM(COALESCE(price, 0)) as revenue')
                    ->groupBy('date')
                    ->orderBy('date')
                    ->pluck('revenue', 'date')
                    ->toArray();

                // Build arrays with proper date filling
                $customerTrend = [];
                $appointmentTrend = [];
                $revenueTrend = [];

                for ($i = 6; $i >= 0; $i--) {
                    $date = now()->subDays($i)->format('Y-m-d');
                    $customerTrend[] = $customerData[$date] ?? 0;
                    $appointmentTrend[] = $appointmentData[$date] ?? 0;
                    $revenueTrend[] = $revenueData[$date] ?? 0;
                }

                return [
                    // Customer Overview
                    Stat::make('Kunden Gesamt', Number::format($totalCustomers))
                        ->description("{$activeCustomers} aktiv")
                        ->descriptionIcon('heroicon-m-user-group')
                        ->chart($customerTrend)
                        ->color('primary'),

                    Stat::make('Neue Kunden', Number::format($newCustomersThisMonth))
                        ->description('Diesen Monat')
                        ->descriptionIcon('heroicon-m-user-plus')
                        ->chart($customerTrend)
                        ->color('success')
                        ->extraAttributes([
                            'class' => 'cursor-pointer',
                        ]),

                    Stat::make('VIP Kunden', Number::format($vipCustomers))
                        ->description($vipCustomers > 0 ?
                            Number::percentage($vipCustomers / $totalCustomers * 100, 1) . ' der Kunden' :
                            'Noch keine VIPs')
                        ->descriptionIcon('heroicon-m-star')
                        ->color('warning'),

                    // Revenue Statistics
                    Stat::make('Gesamtumsatz', Number::currency($totalRevenue, 'EUR'))
                        ->description('Umsatz diesen Monat: ' . Number::currency($revenueThisMonth, 'EUR'))
                        ->descriptionIcon('heroicon-m-currency-euro')
                        ->chart($revenueTrend)
                        ->color('success'),

                    // Appointment Statistics
                    Stat::make('Termine Heute', Number::format($todayAppointments))
                        ->description("{$upcomingAppointments} anstehend gesamt")
                        ->descriptionIcon('heroicon-m-calendar')
                        ->chart($appointmentTrend)
                        ->color('info'),

                    // Call Statistics
                    Stat::make('Anrufe Heute', Number::format($callsToday))
                        ->description($missedCallsToday > 0 ?
                            "{$missedCallsToday} verpasst" :
                            'Alle beantwortet')
                        ->descriptionIcon('heroicon-m-phone')
                        ->color($missedCallsToday > 0 ? 'danger' : 'success'),

                    // Journey Status Distribution
                    Stat::make('Customer Journey', 'Aktiv')
                        ->description($this->getJourneyDescription($journeyDistribution))
                        ->descriptionIcon('heroicon-m-map')
                        ->color('primary'),
                ];
            });
        } catch (\Exception $e) {
            \Log::error('CustomerStatsOverview Widget Error: ' . $e->getMessage());
            Cache::forget('customer_stats_overview'); // Clear cache on error
            return [
                Stat::make('Fehler', 'Widget-Fehler')
                    ->description('Bitte Widget neu laden')
                    ->color('danger'),
            ];
        }
    }

    protected function getJourneyDescription(array $distribution): string
    {
        $descriptions = [];

        $mapping = [
            'initial_contact' => 'Erstkontakt',
            'appointment_scheduled' => 'Termin geplant',
            'appointment_completed' => 'Termin wahrgenommen',
            'regular_customer' => 'Stammkunden',
            'vip_customer' => 'VIP',
            'inactive' => 'Inaktiv',
        ];

        foreach ($mapping as $key => $label) {
            if (isset($distribution[$key]) && $distribution[$key] > 0) {
                $descriptions[] = "{$distribution[$key]} {$label}";
            }
        }

        return implode(' | ', array_slice($descriptions, 0, 3));
    }
}
<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Call;
use App\Models\Appointment;
use App\Models\Customer;
use App\Models\RetellAgent;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ConversionDashboard extends Command
{
    protected $signature = 'dashboard:conversions
                            {--days=30 : Number of days to analyze}
                            {--export : Export to JSON file}';

    protected $description = 'Display conversion metrics dashboard';

    public function handle()
    {
        $days = $this->option('days');
        $startDate = now()->subDays($days)->startOfDay();

        $this->info('📊 CONVERSION TRACKING DASHBOARD');
        $this->info('Period: ' . $startDate->format('Y-m-d') . ' to ' . now()->format('Y-m-d'));
        $this->info(str_repeat('=', 80));

        $metrics = [
            'overall' => $this->getOverallMetrics($startDate),
            'by_channel' => $this->getChannelMetrics($startDate),
            'by_agent' => $this->getAgentMetrics($startDate),
            'by_time' => $this->getTimeMetrics($startDate),
            'customer_journey' => $this->getCustomerJourneyMetrics($startDate),
        ];

        // Display metrics
        $this->displayOverallMetrics($metrics['overall']);
        $this->displayChannelMetrics($metrics['by_channel']);
        $this->displayAgentMetrics($metrics['by_agent']);
        $this->displayTimeMetrics($metrics['by_time']);
        $this->displayCustomerJourney($metrics['customer_journey']);

        if ($this->option('export')) {
            $this->exportMetrics($metrics);
        }

        return 0;
    }

    private function getOverallMetrics($startDate): array
    {
        $totalCalls = Call::where('created_at', '>=', $startDate)->count();
        $totalAppointments = Appointment::where('created_at', '>=', $startDate)->count();
        $totalCustomers = Customer::where('created_at', '>=', $startDate)->count();

        // Calls that resulted in appointments
        $callsWithAppointments = Call::where('calls.created_at', '>=', $startDate)
            ->join('appointments', function ($join) {
                $join->on('appointments.customer_id', '=', 'calls.customer_id')
                     ->whereRaw('appointments.created_at >= calls.created_at')
                     ->whereRaw('appointments.created_at <= DATE_ADD(calls.created_at, INTERVAL 7 DAY)');
            })
            ->distinct('calls.id')
            ->count('calls.id');

        $conversionRate = $totalCalls > 0 ? round(($callsWithAppointments / $totalCalls) * 100, 2) : 0;

        // Average time from call to appointment
        $avgTimeToAppointment = DB::select("
            SELECT AVG(TIMESTAMPDIFF(HOUR, c.created_at, a.created_at)) as avg_hours
            FROM calls c
            JOIN appointments a ON a.customer_id = c.customer_id
            WHERE c.created_at >= ?
            AND a.created_at >= c.created_at
            AND a.created_at <= DATE_ADD(c.created_at, INTERVAL 7 DAY)
        ", [$startDate])[0]->avg_hours ?? 0;

        return [
            'total_calls' => $totalCalls,
            'total_appointments' => $totalAppointments,
            'total_customers' => $totalCustomers,
            'calls_converted' => $callsWithAppointments,
            'conversion_rate' => $conversionRate,
            'avg_time_to_appointment_hours' => round($avgTimeToAppointment, 1),
        ];
    }

    private function getChannelMetrics($startDate): array
    {
        $channels = Appointment::where('created_at', '>=', $startDate)
            ->selectRaw('source, COUNT(*) as count')
            ->groupBy('source')
            ->get()
            ->keyBy('source')
            ->map(fn($item) => $item->count)
            ->toArray();

        // Calculate conversion rates per channel
        $phoneCallCount = Call::where('created_at', '>=', $startDate)->count();
        $phoneAppointments = $channels['phone'] ?? 0;
        $phoneConversion = $phoneCallCount > 0 ? round(($phoneAppointments / $phoneCallCount) * 100, 2) : 0;

        return [
            'appointments_by_source' => $channels,
            'phone_calls' => $phoneCallCount,
            'phone_appointments' => $phoneAppointments,
            'phone_conversion_rate' => $phoneConversion,
            'web_appointments' => $channels['cal.com'] ?? 0,
            'app_appointments' => $channels['app'] ?? 0,
        ];
    }

    private function getAgentMetrics($startDate): array
    {
        $agents = RetellAgent::all();
        $agentMetrics = [];

        foreach ($agents as $agent) {
            $agentCalls = Call::where('agent_id', $agent->id)
                ->where('created_at', '>=', $startDate)
                ->count();

            $agentConversions = Call::where('calls.agent_id', $agent->id)
                ->where('calls.created_at', '>=', $startDate)
                ->join('appointments', function ($join) {
                    $join->on('appointments.customer_id', '=', 'calls.customer_id')
                         ->whereRaw('appointments.created_at >= calls.created_at')
                         ->whereRaw('appointments.created_at <= DATE_ADD(calls.created_at, INTERVAL 7 DAY)');
                })
                ->distinct('calls.id')
                ->count('calls.id');

            $conversionRate = $agentCalls > 0 ? round(($agentConversions / $agentCalls) * 100, 2) : 0;

            $agentMetrics[] = [
                'agent_name' => $agent->name,
                'total_calls' => $agentCalls,
                'conversions' => $agentConversions,
                'conversion_rate' => $conversionRate,
            ];
        }

        return array_filter($agentMetrics, fn($m) => $m['total_calls'] > 0);
    }

    private function getTimeMetrics($startDate): array
    {
        // Best performing hours
        $hourlyStats = Appointment::where('created_at', '>=', $startDate)
            ->selectRaw('HOUR(starts_at) as hour, COUNT(*) as count')
            ->groupBy('hour')
            ->orderBy('count', 'desc')
            ->limit(5)
            ->get();

        // Best performing days
        $dailyStats = Appointment::where('created_at', '>=', $startDate)
            ->selectRaw('DAYNAME(starts_at) as day, COUNT(*) as count')
            ->groupBy('day')
            ->orderBy('count', 'desc')
            ->get();

        return [
            'best_hours' => $hourlyStats->toArray(),
            'best_days' => $dailyStats->toArray(),
        ];
    }

    private function getCustomerJourneyMetrics($startDate): array
    {
        // Customers with multiple touchpoints
        $multiTouch = Customer::where('customers.created_at', '>=', $startDate)
            ->join('calls', 'calls.customer_id', '=', 'customers.id')
            ->selectRaw('customers.id, customers.name, COUNT(DISTINCT calls.id) as call_count')
            ->groupBy('customers.id', 'customers.name')
            ->having('call_count', '>', 1)
            ->get();

        // Average calls before appointment
        $avgCallsBeforeAppointment = DB::select("
            SELECT AVG(call_count) as avg_calls
            FROM (
                SELECT a.customer_id, COUNT(DISTINCT c.id) as call_count
                FROM appointments a
                JOIN calls c ON c.customer_id = a.customer_id
                WHERE a.created_at >= ?
                AND c.created_at <= a.created_at
                GROUP BY a.customer_id
            ) as subquery
        ", [$startDate])[0]->avg_calls ?? 0;

        return [
            'customers_with_multiple_calls' => $multiTouch->count(),
            'avg_calls_before_appointment' => round($avgCallsBeforeAppointment, 1),
        ];
    }

    private function displayOverallMetrics($metrics): void
    {
        $this->info('');
        $this->info('🎯 OVERALL CONVERSION METRICS');
        $this->info(str_repeat('-', 60));

        $this->table(
            ['Metric', 'Value'],
            [
                ['Total Calls', $metrics['total_calls']],
                ['Total Appointments', $metrics['total_appointments']],
                ['Calls Converted', $metrics['calls_converted']],
                ['Conversion Rate', $metrics['conversion_rate'] . '%'],
                ['Avg Time to Appointment', $metrics['avg_time_to_appointment_hours'] . ' hours'],
                ['Total New Customers', $metrics['total_customers']],
            ]
        );
    }

    private function displayChannelMetrics($metrics): void
    {
        $this->info('');
        $this->info('📱 CHANNEL PERFORMANCE');
        $this->info(str_repeat('-', 60));

        $this->table(
            ['Channel', 'Appointments', 'Conversion Rate'],
            [
                ['Phone (Retell)', $metrics['phone_appointments'], $metrics['phone_conversion_rate'] . '%'],
                ['Web (Cal.com)', $metrics['web_appointments'], 'N/A (direct booking)'],
                ['App', $metrics['app_appointments'], 'N/A'],
            ]
        );
    }

    private function displayAgentMetrics($metrics): void
    {
        if (empty($metrics)) {
            return;
        }

        $this->info('');
        $this->info('🤖 AGENT PERFORMANCE');
        $this->info(str_repeat('-', 60));

        $this->table(
            ['Agent', 'Calls', 'Conversions', 'Rate'],
            array_map(fn($m) => [
                substr($m['agent_name'], 0, 40),
                $m['total_calls'],
                $m['conversions'],
                $m['conversion_rate'] . '%'
            ], $metrics)
        );
    }

    private function displayTimeMetrics($metrics): void
    {
        $this->info('');
        $this->info('⏰ TIME ANALYSIS');
        $this->info(str_repeat('-', 60));

        if (!empty($metrics['best_hours'])) {
            $this->info('Best Appointment Hours:');
            foreach ($metrics['best_hours'] as $hour) {
                $this->info("  • {$hour['hour']}:00 - {$hour['count']} appointments");
            }
        }

        if (!empty($metrics['best_days'])) {
            $this->info('');
            $this->info('Best Days:');
            foreach ($metrics['best_days'] as $day) {
                $this->info("  • {$day['day']} - {$day['count']} appointments");
            }
        }
    }

    private function displayCustomerJourney($metrics): void
    {
        $this->info('');
        $this->info('🛤️ CUSTOMER JOURNEY');
        $this->info(str_repeat('-', 60));

        $this->table(
            ['Metric', 'Value'],
            [
                ['Customers with Multiple Calls', $metrics['customers_with_multiple_calls']],
                ['Avg Calls Before Appointment', $metrics['avg_calls_before_appointment']],
            ]
        );
    }

    private function exportMetrics($metrics): void
    {
        $filename = 'conversion_metrics_' . now()->format('Y-m-d_His') . '.json';
        $path = storage_path('app/exports/' . $filename);

        if (!is_dir(storage_path('app/exports'))) {
            mkdir(storage_path('app/exports'), 0755, true);
        }

        file_put_contents($path, json_encode($metrics, JSON_PRETTY_PRINT));

        $this->info('');
        $this->info("✅ Metrics exported to: $path");
    }
}
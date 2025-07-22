<?php

namespace App\Filament\Admin\Widgets;

use App\Models\Call;
use Filament\Widgets\Widget;
use Illuminate\Support\Carbon;

class RealTimeCallMonitorWidget extends Widget
{
    protected static string $view = 'filament.admin.widgets.real-time-call-monitor';

    protected static ?int $sort = 5;

    protected int|string|array $columnSpan = 'full';

    protected static ?string $heading = 'Real-Time Call Monitor';

    protected static ?string $pollingInterval = '5s';

    protected function getViewData(): array
    {
        $companyId = auth()->user()->company_id;

        // Get active calls (in-progress)
        $activeCalls = Call::where('company_id', $companyId)
            ->where('direction', 'outbound')
            ->where('status', 'in-progress')
            ->with(['customer', 'branch'])
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($call) {
                $duration = Carbon::parse($call->created_at)->diffInSeconds(now());

                return [
                    'id' => $call->id,
                    'to_number' => $this->maskPhoneNumber($call->to_number),
                    'customer_name' => $call->customer?->full_name ?? 'Unknown',
                    'purpose' => $call->metadata['purpose'] ?? 'outbound_call',
                    'campaign' => $call->metadata['campaign_id'] ?
                        \App\Models\RetellAICallCampaign::find($call->metadata['campaign_id'])?->name : null,
                    'duration' => $this->formatDuration($duration),
                    'duration_seconds' => $duration,
                    'branch' => $call->branch?->name,
                    'agent_id' => $call->metadata['agent_id'] ?? null,
                ];
            });

        // Get recent completed calls (last 10)
        $recentCalls = Call::where('company_id', $companyId)
            ->where('direction', 'outbound')
            ->whereIn('status', ['completed', 'failed', 'no-answer'])
            ->with(['customer'])
            ->orderBy('updated_at', 'desc')
            ->take(10)
            ->get()
            ->map(function ($call) {
                return [
                    'id' => $call->id,
                    'to_number' => $this->maskPhoneNumber($call->to_number),
                    'customer_name' => $call->customer?->full_name ?? 'Unknown',
                    'status' => $call->status,
                    'duration' => $call->duration_sec ? $this->formatDuration($call->duration_sec) : 'N/A',
                    'ended_at' => $call->updated_at->diffForHumans(),
                    'outcome' => $this->getCallOutcome($call),
                ];
            });

        // Calculate current stats
        $todayStats = $this->getTodayStats($companyId);

        return [
            'activeCalls' => $activeCalls,
            'recentCalls' => $recentCalls,
            'todayStats' => $todayStats,
            'hasActiveCalls' => $activeCalls->count() > 0,
        ];
    }

    protected function getTodayStats($companyId): array
    {
        $today = Call::where('company_id', $companyId)
            ->where('direction', 'outbound')
            ->whereDate('created_at', today())
            ->selectRaw('
                COUNT(*) as total,
                SUM(CASE WHEN status = "in-progress" THEN 1 ELSE 0 END) as active,
                SUM(CASE WHEN status = "completed" THEN 1 ELSE 0 END) as completed,
                SUM(CASE WHEN status IN ("failed", "no-answer") THEN 1 ELSE 0 END) as failed,
                AVG(CASE WHEN status = "completed" THEN duration_sec ELSE NULL END) as avg_duration
            ')
            ->first();

        return [
            'total' => $today->total ?? 0,
            'active' => $today->active ?? 0,
            'completed' => $today->completed ?? 0,
            'failed' => $today->failed ?? 0,
            'avg_duration' => $today->avg_duration ? round($today->avg_duration) : 0,
            'success_rate' => $today->total > 0 ?
                round(($today->completed / $today->total) * 100) : 0,
        ];
    }

    protected function maskPhoneNumber(string $phone): string
    {
        // Keep first 3 and last 2 digits, mask the rest
        if (strlen($phone) > 8) {
            return substr($phone, 0, 3) . str_repeat('*', strlen($phone) - 5) . substr($phone, -2);
        }

        return $phone;
    }

    protected function formatDuration(int $seconds): string
    {
        if ($seconds < 60) {
            return "{$seconds}s";
        }

        $minutes = floor($seconds / 60);
        $remainingSeconds = $seconds % 60;

        return sprintf('%d:%02d', $minutes, $remainingSeconds);
    }

    protected function getCallOutcome($call): string
    {
        if ($call->status === 'completed') {
            // Check metadata for specific outcomes
            if (isset($call->metadata['outcome'])) {
                return $call->metadata['outcome'];
            }

            // Check if appointment was booked
            if (isset($call->metadata['appointment_id'])) {
                return 'Appointment Booked';
            }

            return 'Completed';
        }

        return match ($call->status) {
            'no-answer' => 'No Answer',
            'failed' => $call->metadata['failure_reason'] ?? 'Failed',
            default => ucfirst($call->status),
        };
    }
}

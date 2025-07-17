<?php

namespace App\Repositories;

use App\Models\Call;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class CallRepository extends BaseRepository
{
    /**
     * Specify Model class name
     */
    public function model(): string
    {
        return Call::class;
    }

    /**
     * Get calls by status
     */
    public function getByStatus(string $status): Collection
    {
        return $this->model
            ->where('status', $status)
            ->with(['customer', 'appointment'])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Get recent calls
     */
    public function getRecent(int $limit = 50): Collection
    {
        return $this->model
            ->with(['customer', 'appointment'])
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get calls by phone number
     */
    public function getByPhoneNumber(string $phoneNumber): Collection
    {
        return $this->model
            ->where('from_number', $phoneNumber)
            ->orWhere('to_number', $phoneNumber)
            ->with(['customer', 'appointment'])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Get calls for date range
     */
    public function getByDateRange(Carbon $startDate, Carbon $endDate): Collection
    {
        return $this->model
            ->whereBetween('created_at', [$startDate, $endDate])
            ->with(['customer', 'appointment'])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Get calls with appointments
     */
    public function getWithAppointments(): Collection
    {
        return $this->model
            ->whereNotNull('appointment_id')
            ->with(['appointment.customer', 'appointment.staff', 'appointment.service'])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Get failed calls
     */
    public function getFailed(Carbon $since = null): Collection
    {
        $query = $this->model->where('status', 'failed');
        
        if ($since) {
            $query->where('created_at', '>=', $since);
        }
        
        return $query
            ->with(['customer'])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Get call statistics
     */
    public function getStatistics(Carbon $startDate, Carbon $endDate): array
    {
        $calls = $this->model
            ->whereBetween('created_at', [$startDate, $endDate])
            ->get();
            
        $completedCalls = $calls->where('status', 'completed');
        
        return [
            'total_calls' => $calls->count(),
            'completed_calls' => $completedCalls->count(),
            'failed_calls' => $calls->where('status', 'failed')->count(),
            'total_duration_seconds' => $completedCalls->sum('duration_sec') ?? 0,
            'average_duration_seconds' => $completedCalls->count() > 0 
                ? round($completedCalls->sum('duration_sec') / $completedCalls->count()) 
                : 0,
            'total_cost_cents' => $completedCalls->sum('cost') * 100 ?? 0,
            'appointments_booked' => $calls->whereNotNull('appointment_id')->count(),
            'conversion_rate' => $calls->count() > 0 
                ? round(($calls->whereNotNull('appointment_id')->count() / $calls->count()) * 100, 2) 
                : 0,
        ];
    }

    /**
     * Get calls by agent
     */
    public function getByAgent(string $agentId): Collection
    {
        return $this->model
            ->where('agent_id', $agentId)
            ->with(['customer', 'appointment'])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Search calls
     */
    public function search(string $term): Collection
    {
        return $this->model
            ->where(function ($query) use ($term) {
                $query->where('from_number', 'like', "%{$term}%")
                      ->orWhere('to_number', 'like', "%{$term}%")
                      ->orWhere('call_id', 'like', "%{$term}%")
                      ->orWhere('retell_call_id', 'like', "%{$term}%");
            })
            ->orWhereHas('customer', function ($query) use ($term) {
                $query->where('name', 'like', "%{$term}%")
                      ->orWhere('email', 'like', "%{$term}%");
            })
            ->with(['customer', 'appointment'])
            ->orderBy('created_at', 'desc')
            ->limit(50)
            ->get();
    }

    /**
     * Get calls with transcripts
     */
    public function getWithTranscripts(int $limit = 50): Collection
    {
        return $this->model
            ->whereNotNull('transcript')
            ->with(['customer', 'appointment'])
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Update call data from webhook
     */
    public function updateFromWebhook(string $callId, array $webhookData): bool
    {
        $call = $this->model->where('retell_call_id', $callId)->first();
        
        if (!$call) {
            return false;
        }
        
        $updateData = [
            'status' => $webhookData['status'] ?? $call->status,
            'duration_sec' => $webhookData['duration'] ?? $call->duration_sec,
            'transcript' => $webhookData['transcript'] ?? $call->transcript,
            'analysis' => $webhookData['analysis'] ?? $call->analysis,
            'cost' => $webhookData['cost'] ?? $call->cost,
            'webhook_data' => array_merge($call->webhook_data ?? [], $webhookData),
        ];
        
        return $call->update($updateData);
    }
}
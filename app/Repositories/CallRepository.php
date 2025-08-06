<?php

namespace App\Repositories;

use App\Models\Call;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

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
     * Get calls by status (paginated)
     */
    public function getByStatus(string $status, int $perPage = 100): LengthAwarePaginator
    {
        return $this->model
            ->where('status', $status)
            ->with(['customer', 'appointment'])
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }
    
    /**
     * Get calls by status (all - use for exports)
     */
    public function getByStatusAll(string $status): Collection
    {
        return $this->pushCriteria(function ($query) use ($status) {
            $query->where('status', $status)
                  ->with(['customer', 'appointment'])
                  ->orderBy('created_at', 'desc');
        })->allSafe();
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
     * Get calls by phone number (paginated)
     */
    public function getByPhoneNumber(string $phoneNumber, int $perPage = 50): LengthAwarePaginator
    {
        return $this->model
            ->where('from_number', $phoneNumber)
            ->orWhere('to_number', $phoneNumber)
            ->with(['customer', 'appointment'])
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }
    
    /**
     * Get calls by phone number (all)
     */
    public function getByPhoneNumberAll(string $phoneNumber): Collection
    {
        return $this->pushCriteria(function ($query) use ($phoneNumber) {
            $query->where('from_number', $phoneNumber)
                  ->orWhere('to_number', $phoneNumber)
                  ->with(['customer', 'appointment'])
                  ->orderBy('created_at', 'desc');
        })->allSafe();
    }

    /**
     * Get calls for date range (paginated)
     */
    public function getByDateRange(Carbon $startDate, Carbon $endDate, int $perPage = 100): LengthAwarePaginator
    {
        return $this->model
            ->whereBetween('created_at', [$startDate, $endDate])
            ->with(['customer', 'appointment'])
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }
    
    /**
     * Get calls for date range (chunked processing)
     */
    public function processCallsByDateRange(Carbon $startDate, Carbon $endDate, callable $processor): bool
    {
        return $this->pushCriteria(function ($query) use ($startDate, $endDate) {
            $query->whereBetween('created_at', [$startDate, $endDate])
                  ->with(['customer', 'appointment'])
                  ->orderBy('created_at', 'desc');
        })->chunkSafe(500, $processor);
    }

    /**
     * Get calls with appointments (paginated)
     */
    public function getWithAppointments(int $perPage = 100): LengthAwarePaginator
    {
        return $this->model
            ->whereNotNull('appointment_id')
            ->with(['appointment.customer', 'appointment.staff', 'appointment.service'])
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }
    
    /**
     * Process calls with appointments in chunks
     */
    public function processCallsWithAppointments(callable $processor): bool
    {
        return $this->pushCriteria(function ($query) {
            $query->whereNotNull('appointment_id')
                  ->with(['appointment.customer', 'appointment.staff', 'appointment.service'])
                  ->orderBy('created_at', 'desc');
        })->chunkSafe(200, $processor);
    }

    /**
     * Get failed calls (paginated)
     */
    public function getFailed(Carbon $since = null, int $perPage = 100): LengthAwarePaginator
    {
        $query = $this->model->where('status', 'failed');
        
        if ($since) {
            $query->where('created_at', '>=', $since);
        }
        
        return $query
            ->with(['customer'])
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    /**
     * Get call statistics (optimized with single query)
     */
    public function getStatistics(Carbon $startDate, Carbon $endDate): array
    {
        // Use a single aggregation query for better performance
        $stats = $this->model
            ->whereBetween('created_at', [$startDate, $endDate])
            ->selectRaw('
                COUNT(*) as total_calls,
                COUNT(CASE WHEN status = "completed" THEN 1 END) as completed_calls,
                COUNT(CASE WHEN status = "failed" THEN 1 END) as failed_calls,
                SUM(CASE WHEN status = "completed" THEN duration_sec ELSE 0 END) as total_duration_seconds,
                SUM(CASE WHEN status = "completed" THEN cost ELSE 0 END) as total_cost,
                COUNT(CASE WHEN appointment_id IS NOT NULL THEN 1 END) as appointments_booked
            ')
            ->first();
            
        $totalCalls = $stats->total_calls ?? 0;
        $completedCalls = $stats->completed_calls ?? 0;
        $totalDuration = $stats->total_duration_seconds ?? 0;
        
        return [
            'total_calls' => $totalCalls,
            'completed_calls' => $completedCalls,
            'failed_calls' => $stats->failed_calls ?? 0,
            'total_duration_seconds' => $totalDuration,
            'average_duration_seconds' => $completedCalls > 0 
                ? round($totalDuration / $completedCalls) 
                : 0,
            'total_cost_cents' => ($stats->total_cost ?? 0) * 100,
            'appointments_booked' => $stats->appointments_booked ?? 0,
            'conversion_rate' => $totalCalls > 0 
                ? round((($stats->appointments_booked ?? 0) / $totalCalls) * 100, 2) 
                : 0,
        ];
    }

    /**
     * Get calls by agent (paginated)
     */
    public function getByAgent(string $agentId, int $perPage = 100): LengthAwarePaginator
    {
        return $this->model
            ->where('agent_id', $agentId)
            ->with(['customer', 'appointment'])
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
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
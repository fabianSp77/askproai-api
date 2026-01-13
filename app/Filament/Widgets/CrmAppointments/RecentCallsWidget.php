<?php

namespace App\Filament\Widgets\CrmAppointments;

use App\Filament\Resources\CallResource;
use App\Filament\Widgets\CrmAppointments\Concerns\HasCrmFilters;
use App\Models\Call;
use App\Models\RetellAgent;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\Widget;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Recent Calls Widget
 *
 * Shows the 10 most recent calls with quick access to details.
 * Displays call status, duration, customer info, and appointment status.
 *
 * NOTE: Extends Widget (not TableWidget) to avoid Livewire hydration issues.
 * The table is rendered via a custom Blade view.
 *
 * SECURITY: Filters by company_id for multi-tenancy isolation.
 * FEATURE: Supports company, agent, and time_range filters.
 */
class RecentCallsWidget extends Widget
{
    use InteractsWithPageFilters;
    use HasCrmFilters;

    protected static string $view = 'filament.widgets.crm-appointments.recent-calls-widget';

    protected int|string|array $columnSpan = [
        'default' => 1,
        'md' => 2,
        'xl' => 2,
    ];

    protected static bool $isLazy = true;

    /**
     * Cached agent names mapping.
     */
    protected ?array $agentNames = null;

    /**
     * Get recent calls for display.
     *
     * @return Collection<Call>
     */
    public function getCalls(): Collection
    {
        try {
            $companyId = $this->getEffectiveCompanyId();
            $agentId = $this->getEffectiveAgentId();
            $timeRangeStart = $this->getTimeRangeStart();
            $cacheKey = "crm_recent_calls_{$this->getFilterCacheKey()}";

            return Cache::remember($cacheKey, 60, function () use ($companyId, $agentId, $timeRangeStart) {
                $query = Call::query()
                    ->with(['customer', 'company'])
                    ->latest()
                    ->limit(10);

                if ($companyId) {
                    $query->where('company_id', $companyId);
                }
                if ($agentId) {
                    $query->where('retell_agent_id', $agentId);
                }
                if ($timeRangeStart) {
                    $query->where('created_at', '>=', $timeRangeStart);
                }

                return $query->get();
            });
        } catch (\Throwable $e) {
            Log::error('[RecentCallsWidget] getCalls failed', ['error' => $e->getMessage()]);
            return collect();
        }
    }

    /**
     * Check if company column should be visible.
     */
    public function shouldShowCompanyColumn(): bool
    {
        return $this->getEffectiveCompanyId() === null;
    }

    /**
     * Get URL to view a specific call.
     */
    public function getCallUrl(Call $call): string
    {
        try {
            return CallResource::getUrl('view', ['record' => $call]);
        } catch (\Throwable $e) {
            return '#';
        }
    }

    /**
     * Get status badge color.
     */
    public function getStatusColor(?string $status): string
    {
        if ($status === null) {
            return 'gray';
        }

        return match ($status) {
            'completed' => 'success',
            'failed' => 'danger',
            'ongoing', 'in-progress', 'in_progress', 'active' => 'info',
            'missed' => 'warning',
            'busy', 'no_answer' => 'gray',
            'error' => 'danger',
            default => 'gray',
        };
    }

    /**
     * Get status label.
     */
    public function getStatusLabel(?string $status): string
    {
        if ($status === null) {
            return 'Unbekannt';
        }

        return match ($status) {
            'completed' => 'Erfolgreich',
            'failed' => 'Fehlgeschlagen',
            'ongoing', 'in-progress', 'in_progress' => 'Laufend',
            'active' => 'Aktiv',
            'missed' => 'Verpasst',
            'busy' => 'Besetzt',
            'no_answer' => 'Keine Antwort',
            'error' => 'Fehler',
            default => ucfirst($status),
        };
    }

    /**
     * Format duration as Xm Ys.
     */
    public function formatDuration(?int $seconds): string
    {
        if ($seconds === null || $seconds === 0) {
            return '—';
        }

        $minutes = floor($seconds / 60);
        $secs = $seconds % 60;

        if ($minutes > 0) {
            return "{$minutes}m {$secs}s";
        }

        return "{$secs}s";
    }

    /**
     * Get customer display name.
     */
    public function getCustomerName(Call $call): string
    {
        if ($call->customer) {
            return $call->customer->name ?? $call->customer->phone ?? 'Unbekannt';
        }

        // Check if there's caller info in call data
        if ($call->customer_name) {
            return $call->customer_name;
        }

        if ($call->from_number) {
            return $call->from_number;
        }

        return 'Unbekannt';
    }

    /**
     * Get agent display name.
     */
    public function getAgentName(Call $call): string
    {
        if ($this->agentNames === null) {
            $this->agentNames = RetellAgent::pluck('name', 'agent_id')->toArray();
        }

        return $this->agentNames[$call->retell_agent_id] ?? 'Agent';
    }

    /**
     * Check if call has an appointment.
     */
    public function hasAppointment(Call $call): bool
    {
        return (bool) $call->has_appointment;
    }
}

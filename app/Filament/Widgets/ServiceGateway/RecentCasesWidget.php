<?php

namespace App\Filament\Widgets\ServiceGateway;

use App\Filament\Resources\ServiceCaseResource;
use App\Models\ServiceCase;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\Widget;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Recent Cases Widget
 *
 * Shows the 10 most recent service cases with quick access.
 * ServiceNow-style activity feed for dashboard.
 *
 * NOTE: This widget extends Widget (not TableWidget) to avoid
 * the typed property initialization issue with Livewire hydration.
 * The table is rendered via a custom Blade view.
 *
 * SECURITY: Filters by company_id for multi-tenancy isolation.
 * FEATURE: Supports company filter from dashboard for super-admins.
 */
class RecentCasesWidget extends Widget
{
    use InteractsWithPageFilters;

    protected static string $view = 'filament.widgets.service-gateway.recent-cases-widget';

    protected int|string|array $columnSpan = 'full';

    protected static ?string $pollingInterval = '30s';

    /**
     * Enable lazy loading to defer widget rendering.
     */
    protected static bool $isLazy = true;

    /**
     * Get the effective company ID based on filter or user context.
     */
    protected function getEffectiveCompanyId(): ?int
    {
        try {
            $filteredCompanyId = $this->filters['company_id'] ?? null;
            if ($filteredCompanyId) {
                return (int) $filteredCompanyId;
            }

            $user = Auth::user();
            if ($user && $user->hasAnyRole(['super_admin', 'super-admin', 'Admin', 'reseller_admin'])) {
                return null;
            }

            return $user?->company_id;
        } catch (\Throwable $e) {
            Log::warning('[RecentCasesWidget] getEffectiveCompanyId failed', [
                'error' => $e->getMessage(),
            ]);
            return Auth::user()?->company_id;
        }
    }

    /**
     * Get recent cases for display.
     *
     * @return Collection<ServiceCase>
     */
    public function getCases(): Collection
    {
        try {
            $companyId = $this->getEffectiveCompanyId();
            $cacheKey = $companyId
                ? "service_gateway_recent_cases_{$companyId}"
                : 'service_gateway_recent_cases_all';

            return Cache::remember($cacheKey, config('gateway.cache.recent_activity_seconds'), function () use ($companyId) {
                $query = ServiceCase::query()
                    ->with(['category', 'assignedTo', 'assignedGroup', 'company'])
                    ->latest()
                    ->limit(10);

                if ($companyId) {
                    $query->where('company_id', $companyId);
                }

                return $query->get();
            });
        } catch (\Throwable $e) {
            Log::error('[RecentCasesWidget] getCases failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

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
     * Get URL to view a specific case.
     */
    public function getCaseUrl(ServiceCase $case): string
    {
        try {
            return ServiceCaseResource::getUrl('view', ['record' => $case]);
        } catch (\Throwable $e) {
            return '#';
        }
    }

    /**
     * Get status badge color.
     */
    public function getStatusColor(string $status): string
    {
        return match ($status) {
            ServiceCase::STATUS_NEW => 'danger',
            ServiceCase::STATUS_OPEN => 'info',
            ServiceCase::STATUS_PENDING => 'warning',
            ServiceCase::STATUS_RESOLVED => 'success',
            ServiceCase::STATUS_CLOSED => 'gray',
            default => 'gray',
        };
    }

    /**
     * Get status label.
     */
    public function getStatusLabel(string $status): string
    {
        return ServiceCase::STATUS_LABELS[$status] ?? $status;
    }

    /**
     * Get priority badge color.
     */
    public function getPriorityColor(string $priority): string
    {
        return match ($priority) {
            ServiceCase::PRIORITY_CRITICAL => 'danger',
            ServiceCase::PRIORITY_HIGH => 'warning',
            ServiceCase::PRIORITY_NORMAL => 'info',
            ServiceCase::PRIORITY_LOW => 'gray',
            default => 'gray',
        };
    }

    /**
     * Get priority label.
     */
    public function getPriorityLabel(string $priority): string
    {
        return ServiceCase::PRIORITY_LABELS[$priority] ?? $priority;
    }

    /**
     * Get assigned to display text.
     */
    public function getAssignedToText(ServiceCase $case): string
    {
        if ($case->assignedTo) {
            return $case->assignedTo->name;
        }

        if ($case->assignedGroup) {
            return $case->assignedGroup->name;
        }

        return '—';
    }

    /**
     * Check if case has a direct assignment (vs group).
     */
    public function hasDirectAssignment(ServiceCase $case): bool
    {
        return $case->assigned_to !== null;
    }
}

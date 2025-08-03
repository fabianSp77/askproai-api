<?php

namespace App\Filament\Admin\Widgets;

use Filament\Widgets\Widget;
use App\Models\Appointment;
use App\Models\Call;
use App\Models\ApiCallLog;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;

class CriticalAlertsWidget extends Widget
{
    protected static string $view = 'filament.admin.widgets.critical-alerts-widget';
    protected int | string | array $columnSpan = 'full';
    protected static ?int $sort = 5;

    public ?int $companyId = null;
    public ?int $selectedBranchId = null;

    protected function getListeners(): array
    {
        return [
            'tenantFilterUpdated' => 'handleTenantFilterUpdate',
            'alertDismissed' => 'handleAlertDismiss',
        ];
    }

    public function handleTenantFilterUpdate($companyId, $branchId): void
    {
        $this->companyId = $companyId;
        $this->selectedBranchId = $branchId;
    }

    public function handleAlertDismiss($alertId): void
    {
        $dismissedAlerts = session('dismissed_alerts', []);
        $dismissedAlerts[] = $alertId;
        session(['dismissed_alerts' => $dismissedAlerts]);
    }

    protected function getViewData(): array
    {
        $alerts = $this->getActiveAlerts();
        
        return [
            'alerts' => $alerts,
            'hasAlerts' => count($alerts) > 0,
            'alertCount' => count($alerts),
        ];
    }

    protected function getActiveAlerts(): array
    {
        $alerts = [];
        $dismissedAlerts = session('dismissed_alerts', []);
        
        // No-Show Alert
        $noShowRate = $this->getNoShowRate();
        if ($noShowRate > 15 && !in_array('no_show_high', $dismissedAlerts)) {
            $alerts[] = [
                'id' => 'no_show_high',
                'type' => 'warning',
                'title' => 'Hohe No-Show-Rate',
                'message' => "Die No-Show-Rate liegt bei {$noShowRate}% (Schwellenwert: 15%)",
                'action' => 'Erinnerungen aktivieren',
                'actionUrl' => "/admin/companies/{$this->companyId}/edit",
            ];
        }
        
        // API Fehler Alert
        $apiErrors = $this->getAPIErrors();
        if ($apiErrors > 5 && !in_array('api_errors', $dismissedAlerts)) {
            $alerts[] = [
                'id' => 'api_errors',
                'type' => 'danger',
                'title' => 'API-Verbindungsprobleme',
                'message' => "{$apiErrors} fehlgeschlagene API-Aufrufe in der letzten Stunde",
                'action' => 'Status prüfen',
                'actionUrl' => '/admin/api-health-monitor',
            ];
        }
        
        // Kapazitäts Alert
        $capacityInfo = $this->getCapacityInfo();
        if ($capacityInfo['utilization'] > 90 && !in_array('capacity_high', $dismissedAlerts)) {
            $alerts[] = [
                'id' => 'capacity_high',
                'type' => 'warning',
                'title' => 'Hohe Auslastung',
                'message' => "Die Kapazität ist zu {$capacityInfo['utilization']}% ausgelastet. Nur noch {$capacityInfo['available']} freie Termine heute.",
                'action' => 'Kapazität erweitern',
                'actionUrl' => '/admin/staff',
            ];
        }
        
        // Lange Wartezeit Alert
        $avgWaitTime = $this->getAverageWaitTime();
        if ($avgWaitTime > 300 && !in_array('wait_time_high', $dismissedAlerts)) { // > 5 Minuten
            $alerts[] = [
                'id' => 'wait_time_high',
                'type' => 'info',
                'title' => 'Lange Wartezeiten',
                'message' => "Die durchschnittliche Wartezeit beträgt " . round($avgWaitTime / 60) . " Minuten",
                'action' => 'Personal aufstocken',
                'actionUrl' => '/admin/staff',
            ];
        }
        
        // Compliance Alert
        $complianceIssues = $this->getComplianceIssues();
        if (count($complianceIssues) > 0 && !in_array('compliance', $dismissedAlerts)) {
            $alerts[] = [
                'id' => 'compliance',
                'type' => 'danger',
                'title' => 'Compliance-Probleme',
                'message' => implode(', ', $complianceIssues),
                'action' => 'Compliance prüfen',
                'actionUrl' => '/admin#compliance',
            ];
        }
        
        return array_slice($alerts, 0, 5); // Maximal 5 Alerts
    }

    protected function getNoShowRate(): float
    {
        $total = Appointment::query()
            ->when($this->companyId, fn($q) => $q->where('company_id', $this->companyId))
            ->when($this->selectedBranchId, fn($q) => $q->where('branch_id', $this->selectedBranchId))
            ->where('starts_at', '<=', Carbon::now())
            ->where('starts_at', '>=', Carbon::now()->subDays(7))
            ->count();

        if ($total === 0) return 0;

        $noShows = Appointment::query()
            ->when($this->companyId, fn($q) => $q->where('company_id', $this->companyId))
            ->when($this->selectedBranchId, fn($q) => $q->where('branch_id', $this->selectedBranchId))
            ->where('starts_at', '<=', Carbon::now())
            ->where('starts_at', '>=', Carbon::now()->subDays(7))
            ->where('status', 'no_show')
            ->count();

        return round(($noShows / $total) * 100);
    }

    protected function getAPIErrors(): int
    {
        return ApiCallLog::query()
            ->when($this->companyId, fn($q) => $q->where('company_id', $this->companyId))
            ->where('created_at', '>=', Carbon::now()->subHour())
            ->where('status', 'failed')
            ->count();
    }

    protected function getCapacityInfo(): array
    {
        $totalSlots = 16 * 5; // 16 slots per day * 5 staff (example)
        
        $bookedSlots = Appointment::query()
            ->when($this->companyId, fn($q) => $q->where('company_id', $this->companyId))
            ->when($this->selectedBranchId, fn($q) => $q->where('branch_id', $this->selectedBranchId))
            ->whereDate('starts_at', today())
            ->whereIn('status', ['scheduled', 'confirmed'])
            ->count();
        
        $utilization = $totalSlots > 0 ? round(($bookedSlots / $totalSlots) * 100) : 0;
        
        return [
            'utilization' => $utilization,
            'available' => $totalSlots - $bookedSlots,
        ];
    }

    protected function getAverageWaitTime(): float
    {
        return Call::query()
            ->when($this->companyId, function ($q) {
                $q->whereHas('branch', fn($q) => $q->where('company_id', $this->companyId));
            })
            ->when($this->selectedBranchId, fn($q) => $q->where('branch_id', $this->selectedBranchId))
            ->where('created_at', '>=', Carbon::now()->subHour())
            ->avg('wait_time_sec') ?? 0;
    }

    protected function getComplianceIssues(): array
    {
        $issues = [];
        
        // Check GDPR consent rate
        $consentRate = Cache::get("gdpr-consent-rate-{$this->companyId}", 100);
        if ($consentRate < 95) {
            $issues[] = 'DSGVO-Einwilligungen unvollständig';
        }
        
        // Check backup age
        $lastBackup = Cache::get("last-backup-{$this->companyId}", Carbon::now());
        if ($lastBackup < Carbon::now()->subDays(1)) {
            $issues[] = 'Backup überfällig';
        }
        
        return $issues;
    }
}
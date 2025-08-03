<?php

namespace App\Filament\Admin\Widgets;

use Filament\Widgets\Widget;
use App\Models\Customer;
use App\Models\Appointment;
use App\Models\Company;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class GermanComplianceWidget extends Widget
{
    protected static string $view = 'filament.admin.widgets.german-compliance-widget';
    protected int | string | array $columnSpan = 1;
    protected static ?int $sort = 5;

    public ?int $companyId = null;
    public ?int $selectedBranchId = null;

    protected function getListeners(): array
    {
        return [
            'tenantFilterUpdated' => 'handleTenantFilterUpdate',
        ];
    }

    public function handleTenantFilterUpdate($companyId, $branchId): void
    {
        $this->companyId = $companyId;
        $this->selectedBranchId = $branchId;
    }

    protected function getViewData(): array
    {
        $cacheKey = "compliance-{$this->companyId}-{$this->selectedBranchId}";
        
        $data = Cache::remember($cacheKey, 300, function () {
            return $this->getComplianceStatus();
        });

        return $data;
    }

    protected function getComplianceStatus(): array
    {
        $company = Company::find($this->companyId);
        
        return [
            'gdpr' => $this->getGDPRStatus(),
            'kassenbuch' => $this->getKassenbuchStatus(),
            'tax' => $this->getTaxStatus(),
            'data_security' => $this->getDataSecurityStatus(),
            'overall_compliance' => $this->calculateOverallCompliance(),
            'next_actions' => $this->getNextActions(),
        ];
    }

    protected function getGDPRStatus(): array
    {
        // DSGVO/GDPR Status prüfen
        $totalCustomers = Customer::query()
            ->when($this->companyId, fn($q) => $q->where('company_id', $this->companyId))
            ->count();

        $customersWithConsent = Customer::query()
            ->when($this->companyId, fn($q) => $q->where('company_id', $this->companyId))
            ->whereNotNull('privacy_consent_at')
            ->count();

        $dataRetentionCompliant = $this->checkDataRetentionCompliance();
        $rightToErasureCompliant = $this->checkRightToErasureCompliance();

        $consentRate = $totalCustomers > 0 ? ($customersWithConsent / $totalCustomers) * 100 : 0;

        return [
            'status' => $consentRate >= 95 && $dataRetentionCompliant && $rightToErasureCompliant ? 'compliant' : 
                       ($consentRate >= 80 ? 'warning' : 'critical'),
            'consent_rate' => round($consentRate),
            'data_retention' => $dataRetentionCompliant,
            'right_to_erasure' => $rightToErasureCompliant,
            'last_audit' => Carbon::now()->subDays(7)->format('d.m.Y'), // TODO: Implement real audit tracking
        ];
    }

    protected function getKassenbuchStatus(): array
    {
        // Kassenbuch Integration Status
        $todayRevenue = Appointment::query()
            ->when($this->companyId, fn($q) => $q->where('company_id', $this->companyId))
            ->when($this->selectedBranchId, fn($q) => $q->where('branch_id', $this->selectedBranchId))
            ->whereDate('starts_at', today())
            ->where('status', 'completed')
            ->sum('price');

        $syncedTransactions = 0; // TODO: Implement Kassenbuch sync tracking
        $totalTransactions = Appointment::query()
            ->when($this->companyId, fn($q) => $q->where('company_id', $this->companyId))
            ->when($this->selectedBranchId, fn($q) => $q->where('branch_id', $this->selectedBranchId))
            ->whereDate('starts_at', today())
            ->where('status', 'completed')
            ->count();

        $syncRate = $totalTransactions > 0 ? ($syncedTransactions / $totalTransactions) * 100 : 100;

        return [
            'status' => $syncRate === 100 ? 'synced' : ($syncRate >= 90 ? 'partial' : 'error'),
            'sync_rate' => round($syncRate),
            'last_sync' => Carbon::now()->subMinutes(15)->format('H:i'),
            'today_revenue' => $todayRevenue,
            'pending_entries' => max(0, $totalTransactions - $syncedTransactions),
        ];
    }

    protected function getTaxStatus(): array
    {
        // Steuer-Status (USt-Voranmeldung)
        $currentMonth = Carbon::now()->format('m/Y');
        $deadline = Carbon::now()->startOfMonth()->addMonth()->addDays(10);
        $daysUntilDeadline = Carbon::now()->diffInDays($deadline, false);

        $monthlyRevenue = Appointment::query()
            ->when($this->companyId, fn($q) => $q->where('company_id', $this->companyId))
            ->whereMonth('starts_at', Carbon::now()->month)
            ->whereYear('starts_at', Carbon::now()->year)
            ->where('status', 'completed')
            ->sum('price');

        $vatAmount = $monthlyRevenue * 0.19; // 19% MwSt

        return [
            'status' => $daysUntilDeadline > 5 ? 'on_track' : ($daysUntilDeadline > 0 ? 'urgent' : 'overdue'),
            'current_period' => $currentMonth,
            'deadline' => $deadline->format('d.m.Y'),
            'days_until_deadline' => max(0, $daysUntilDeadline),
            'monthly_revenue' => $monthlyRevenue,
            'vat_amount' => $vatAmount,
            'prepared' => false, // TODO: Implement tax preparation tracking
        ];
    }

    protected function getDataSecurityStatus(): array
    {
        // Datensicherheit Status
        $lastBackup = Carbon::now()->subHours(2); // TODO: Implement real backup tracking
        $encryptionEnabled = true; // TODO: Check real encryption status
        $ssl_valid = true; // TODO: Check SSL certificate
        $access_logs_enabled = true; // TODO: Check access logging

        $backupAge = $lastBackup->diffInHours(Carbon::now());

        return [
            'status' => $backupAge <= 24 && $encryptionEnabled && $ssl_valid ? 'secure' : 
                       ($backupAge <= 48 ? 'warning' : 'critical'),
            'last_backup' => $lastBackup->format('d.m.Y H:i'),
            'backup_age_hours' => $backupAge,
            'encryption' => $encryptionEnabled,
            'ssl_certificate' => $ssl_valid,
            'access_logging' => $access_logs_enabled,
        ];
    }

    protected function calculateOverallCompliance(): int
    {
        $gdprScore = $this->getGDPRStatus()['status'] === 'compliant' ? 100 : 
                    ($this->getGDPRStatus()['status'] === 'warning' ? 70 : 30);
        
        $kassenbuchScore = $this->getKassenbuchStatus()['status'] === 'synced' ? 100 : 
                          ($this->getKassenbuchStatus()['status'] === 'partial' ? 70 : 30);
        
        $taxScore = $this->getTaxStatus()['status'] === 'on_track' ? 100 : 
                   ($this->getTaxStatus()['status'] === 'urgent' ? 70 : 30);
        
        $securityScore = $this->getDataSecurityStatus()['status'] === 'secure' ? 100 : 
                        ($this->getDataSecurityStatus()['status'] === 'warning' ? 70 : 30);

        return round(($gdprScore + $kassenbuchScore + $taxScore + $securityScore) / 4);
    }

    protected function getNextActions(): array
    {
        $actions = [];

        // GDPR Actions
        if ($this->getGDPRStatus()['consent_rate'] < 95) {
            $actions[] = [
                'type' => 'gdpr',
                'priority' => 'high',
                'action' => 'Datenschutzeinwilligungen einholen',
                'count' => Customer::query()
                    ->when($this->companyId, fn($q) => $q->where('company_id', $this->companyId))
                    ->whereNull('privacy_consent_at')
                    ->count(),
            ];
        }

        // Tax Actions
        if ($this->getTaxStatus()['days_until_deadline'] <= 5) {
            $actions[] = [
                'type' => 'tax',
                'priority' => 'urgent',
                'action' => 'USt-Voranmeldung vorbereiten',
                'deadline' => $this->getTaxStatus()['deadline'],
            ];
        }

        // Backup Actions
        if ($this->getDataSecurityStatus()['backup_age_hours'] > 24) {
            $actions[] = [
                'type' => 'security',
                'priority' => 'medium',
                'action' => 'Backup durchführen',
                'overdue_hours' => $this->getDataSecurityStatus()['backup_age_hours'] - 24,
            ];
        }

        return array_slice($actions, 0, 3); // Max 3 actions
    }

    protected function checkDataRetentionCompliance(): bool
    {
        // Prüfen ob Daten älter als 10 Jahre gelöscht wurden
        $oldDataCount = Customer::query()
            ->when($this->companyId, fn($q) => $q->where('company_id', $this->companyId))
            ->where('created_at', '<', Carbon::now()->subYears(10))
            ->count();

        return $oldDataCount === 0;
    }

    protected function checkRightToErasureCompliance(): bool
    {
        // Prüfen ob Löschanfragen bearbeitet wurden
        // TODO: Implement deletion request tracking
        return true;
    }
}
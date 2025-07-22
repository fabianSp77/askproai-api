<?php

namespace App\Filament\Admin\Pages;

use Filament\Pages\Page;

class QuickDocsSimple extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationLabel = 'Quick Docs (Simple)';

    protected static ?string $slug = 'quick-docs-simple';

    protected static string $view = 'filament.admin.pages.quick-docs-simple';

    protected static ?int $navigationSort = 2;

    public string $search = '';

    public static function getNavigationGroup(): ?string
    {
        return 'System';
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->hasAnyRole([
            'super_admin',
            'Super Admin',
            'company_admin',
            'Company Admin',
        ]) ?? false;
    }

    public function getDocuments(): array
    {
        return [
            [
                'id' => 'onboarding-5min',
                'title' => '🚀 5-Min Onboarding Excellence',
                'description' => 'Transform new customers into success stories in record time',
                'url' => '/mkdocs/5-MINUTEN_ONBOARDING_PLAYBOOK/',
                'category' => 'critical',
                'color' => 'success',
            ],
            [
                'id' => 'customer-success',
                'title' => '💎 Customer Success Runbook',
                'description' => 'Ready-to-use solutions for top 10 customer problems',
                'url' => '/mkdocs/CUSTOMER_SUCCESS_RUNBOOK/',
                'category' => 'critical',
                'color' => 'info',
            ],
            [
                'id' => 'emergency-response',
                'title' => '🚨 Emergency Response Playbook',
                'description' => '24/7 on-call guide with recovery scripts',
                'url' => '/mkdocs/EMERGENCY_RESPONSE_PLAYBOOK/',
                'category' => 'critical',
                'color' => 'danger',
            ],
            [
                'id' => 'troubleshooting',
                'title' => '🔍 Smart Troubleshooting Decision Trees',
                'description' => 'Solve any problem in 5 clicks or less',
                'url' => '/mkdocs/TROUBLESHOOTING_DECISION_TREE/',
                'category' => 'critical',
                'color' => 'warning',
            ],
            [
                'id' => 'phone-to-appointment',
                'title' => '📞 Phone-to-Appointment Flow Mastery',
                'description' => 'Deep dive into the complete data flow',
                'url' => '/mkdocs/PHONE_TO_APPOINTMENT_FLOW/',
                'category' => 'process',
                'color' => 'info',
            ],
            [
                'id' => 'kpi-dashboard',
                'title' => '📊 KPI Dashboard & ROI Calculator',
                'description' => 'Track success metrics and prove value instantly',
                'url' => '/mkdocs/KPI_DASHBOARD_TEMPLATE/',
                'category' => 'process',
                'color' => 'success',
            ],
            [
                'id' => 'integration-monitor',
                'title' => '🔌 Integration Health Monitor',
                'description' => 'Real-time status of Cal.com & Retell.ai connections',
                'url' => '/mkdocs/INTEGRATION_HEALTH_MONITOR/',
                'category' => 'technical',
                'color' => 'primary',
            ],
            [
                'id' => 'architecture',
                'title' => '🏗️ Service Architecture Guide',
                'description' => 'Complete system overview with 69 services explained',
                'url' => '/mkdocs/architecture/services/',
                'category' => 'technical',
                'color' => 'secondary',
            ],
        ];
    }

    public function getFilteredDocuments(): array
    {
        $documents = $this->getDocuments();

        if (empty($this->search)) {
            return $documents;
        }

        $searchLower = strtolower($this->search);

        return array_filter($documents, function ($doc) use ($searchLower) {
            return str_contains(strtolower($doc['title']), $searchLower) ||
                   str_contains(strtolower($doc['description']), $searchLower) ||
                   str_contains(strtolower($doc['category']), $searchLower);
        });
    }
}

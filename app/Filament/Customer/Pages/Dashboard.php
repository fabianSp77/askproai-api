<?php

namespace App\Filament\Customer\Pages;

use Filament\Pages\Dashboard as BaseDashboard;

/**
 * Customer Portal Dashboard
 *
 * Main dashboard for customer portal showing overview statistics
 * and quick access to key features.
 *
 * Phase 1 Widgets:
 * - Recent Calls (last 5 calls with transcripts)
 * - Upcoming Appointments (next 7 days)
 * - Quick Stats (total calls, total appointments this month)
 *
 * Phase 2 Widgets (Future):
 * - Customer Growth Chart
 * - Revenue Overview
 * - Popular Services
 *
 * @see app/Filament/Customer/Widgets/
 */
class Dashboard extends BaseDashboard
{
    /**
     * Dashboard route path
     */
    protected static string $routePath = '';

    /**
     * Navigation configuration
     */
    protected static ?string $navigationIcon = 'heroicon-o-home';
    protected static ?string $navigationLabel = 'Dashboard';
    protected static ?int $navigationSort = 1;

    /**
     * Page title
     */
    public function getTitle(): string
    {
        return 'Willkommen im Kundenportal';
    }

    /**
     * Page heading
     */
    public function getHeading(): string
    {
        $user = auth()->user();
        $companyName = $user->company->name ?? 'Kundenportal';

        return "Willkommen, {$companyName}";
    }

    /**
     * Subheading with last login info
     */
    public function getSubheading(): ?string
    {
        $user = auth()->user();
        $lastLogin = $user->last_login_at?->diffForHumans() ?? 'zum ersten Mal';

        return "Letzte Anmeldung: {$lastLogin}";
    }

    /**
     * Dashboard widgets
     *
     * Widgets will be auto-discovered from app/Filament/Customer/Widgets/
     * and can be arranged here or users can customize via dashboard
     */
    public function getWidgets(): array
    {
        return [
            // Widgets will be added in next phase
            // \App\Filament\Customer\Widgets\StatsOverview::class,
            // \App\Filament\Customer\Widgets\RecentCallsWidget::class,
            // \App\Filament\Customer\Widgets\UpcomingAppointmentsWidget::class,
        ];
    }

    /**
     * Widget columns on dashboard
     */
    public function getColumns(): int | string | array
    {
        return 2; // 2-column layout
    }
}

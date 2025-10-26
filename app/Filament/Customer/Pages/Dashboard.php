<?php

namespace App\Filament\Customer\Pages;

use Filament\Pages\Dashboard as BaseDashboard;
use Illuminate\Contracts\Support\Htmlable;

/**
 * Customer Portal Dashboard
 *
 * Main dashboard for customer portal showing comprehensive overview statistics,
 * recent activity, and actionable insights.
 *
 * Widgets:
 * - CustomerPortalStatsWidget: 4 stat cards with trends (Appointments, Invoices, Balance, Calls)
 * - RecentAppointmentsWidget: Next 5 upcoming appointments table
 * - RecentCallsWidget: Last 5 calls from past 24h with transcripts
 * - BalanceOverviewWidget: 30-day balance history line chart
 * - OutstandingInvoicesWidget: Open/overdue invoices with payment alerts
 * - CustomerJourneyWidget: Customer distribution by journey stage (pie chart)
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
        return 'Dashboard';
    }

    /**
     * Page heading with personalized greeting
     */
    public function getHeading(): string|Htmlable
    {
        $user = auth()->user();
        $greeting = $this->getGreeting();
        $name = $user->name ?? 'User';

        return "{$greeting}, {$name}! 👋";
    }

    /**
     * Subheading with current date in German
     */
    public function getSubheading(): string|Htmlable|null
    {
        $date = now()->locale('de')->isoFormat('dddd, D. MMMM YYYY');
        return "Heute ist {$date}";
    }

    /**
     * Get time-based greeting in German
     */
    protected function getGreeting(): string
    {
        $hour = now()->hour;

        if ($hour < 6) {
            return 'Gute Nacht';
        } elseif ($hour < 11) {
            return 'Guten Morgen';
        } elseif ($hour < 14) {
            return 'Guten Tag';
        } elseif ($hour < 18) {
            return 'Guten Nachmittag';
        } else {
            return 'Guten Abend';
        }
    }

    /**
     * Responsive column layout
     */
    public function getColumns(): int | string | array
    {
        return [
            'sm' => 1,
            'md' => 2,
            'lg' => 3,
            'xl' => 4,
            '2xl' => 5,
        ];
    }

    /**
     * Dashboard widgets in display order
     *
     * All widgets are company-scoped and include:
     * - 5-minute caching where appropriate
     * - German localization
     * - Responsive layouts
     * - Empty states with helpful messages
     */
    public function getWidgets(): array
    {
        return [
            // Stats Overview Widget (4 cards with trends)
            \App\Filament\Customer\Widgets\CustomerPortalStatsWidget::class,

            // Recent Appointments Table (Next 5 appointments)
            \App\Filament\Customer\Widgets\RecentAppointmentsWidget::class,

            // Recent Calls Table (Last 5 calls from 24h)
            \App\Filament\Customer\Widgets\RecentCallsWidget::class,

            // Balance Overview Chart (30-day history)
            \App\Filament\Customer\Widgets\BalanceOverviewWidget::class,

            // Outstanding Invoices Table (Open/overdue with alerts)
            \App\Filament\Customer\Widgets\OutstandingInvoicesWidget::class,

            // Customer Journey Status Distribution Chart
            \App\Filament\Customer\Widgets\CustomerJourneyWidget::class,
        ];
    }

    /**
     * View data
     */
    protected function getViewData(): array
    {
        return [
            'hasFiltersLayout' => false,
        ];
    }
}

{{--
    Premium Dashboard - Financial Analytics
    Design Reference: Outcrowd Financial Analytics Dashboard

    This view extends the default Filament page layout with premium dark theme styling.
    The header/footer widgets are rendered by Filament's built-in page component.
--}}
<x-filament-panels::page class="premium-dashboard-page">
    {{-- Filters Form --}}
    @if (method_exists($this, 'filtersForm'))
        {{ $this->filtersForm }}
    @endif

    {{-- Main widgets slot - renders widgets from getVisibleWidgets() --}}
    <x-filament-widgets::widgets
        :columns="$this->getColumns()"
        :data="
            [
                ...(property_exists($this, 'filters') ? ['filters' => $this->filters] : []),
                ...$this->getWidgetData(),
            ]
        "
        :widgets="$this->getVisibleWidgets()"
    />

    {{-- Inline styles for premium dark theme --}}
    <style>
        /* Force dark background for this page */
        .premium-dashboard-page {
            --fi-body-bg: #0D0D0F !important;
        }

        /* Override Filament's main content background */
        .premium-dashboard-page .fi-main {
            background: transparent !important;
        }

        /* Radial gradient effect */
        .premium-dashboard-page::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            height: 100vh;
            background:
                radial-gradient(ellipse 80% 50% at 50% -20%, rgba(59, 130, 246, 0.12), transparent 50%),
                radial-gradient(ellipse 60% 40% at 100% 50%, rgba(139, 92, 246, 0.08), transparent 50%),
                radial-gradient(ellipse 50% 30% at 0% 80%, rgba(34, 197, 94, 0.05), transparent 50%);
            pointer-events: none;
            z-index: 0;
        }

        /* Ensure content is above gradient */
        .premium-dashboard-page > * {
            position: relative;
            z-index: 1;
        }

        /* Override section/widget backgrounds */
        .premium-dashboard-page .fi-section {
            background: #18181B !important;
            border: 1px solid rgba(255, 255, 255, 0.05) !important;
            border-radius: 16px !important;
        }

        .premium-dashboard-page .fi-section:hover {
            background: #1F1F23 !important;
            border-color: rgba(255, 255, 255, 0.08) !important;
        }

        /* Chart widget backgrounds */
        .premium-dashboard-page .fi-wi-chart {
            background: #18181B !important;
            border: 1px solid rgba(255, 255, 255, 0.05) !important;
            border-radius: 16px !important;
        }

        /* Widget wrapper backgrounds */
        .premium-dashboard-page .fi-wi {
            background: transparent !important;
        }

        /* Filters section styling */
        .premium-dashboard-page .fi-fo-component-ctn {
            background: #18181B !important;
            border: 1px solid rgba(255, 255, 255, 0.05) !important;
            border-radius: 12px !important;
        }

        .premium-dashboard-page .fi-input,
        .premium-dashboard-page .fi-select-input {
            background: #27272A !important;
            border-color: rgba(255, 255, 255, 0.08) !important;
            color: #FFFFFF !important;
        }

        /* Text colors */
        .premium-dashboard-page .fi-section-heading-text,
        .premium-dashboard-page .fi-wi-stats-stat-value {
            color: #FFFFFF !important;
        }

        .premium-dashboard-page .fi-section-description,
        .premium-dashboard-page .fi-wi-stats-stat-description {
            color: #A1A1AA !important;
        }

        /* Page header */
        .premium-dashboard-page .fi-page-header {
            border-bottom: none !important;
        }

        .premium-dashboard-page .fi-page-header-heading {
            color: #FFFFFF !important;
        }

        /* Hide default breadcrumbs for cleaner look */
        .premium-dashboard-page .fi-page-header nav {
            display: none;
        }

        /* Premium card styling for custom widgets */
        .premium-card {
            background: #18181B !important;
            border: 1px solid rgba(255, 255, 255, 0.05) !important;
            border-radius: 16px !important;
            padding: 1.5rem;
        }

        .premium-card:hover {
            background: #1F1F23 !important;
            border-color: rgba(255, 255, 255, 0.08) !important;
        }
    </style>
</x-filament-panels::page>

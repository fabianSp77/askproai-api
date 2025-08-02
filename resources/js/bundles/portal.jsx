// Portal React Bundle
// Consolidates all portal React components and optimized versions

import React from 'react';
import ReactDOM from 'react-dom/client';
import { BrowserRouter, Routes, Route, Navigate } from 'react-router-dom';

// Import components - use optimized versions if available, fallback to regular
let DashboardOptimized, CallsOptimized, AppointmentsOptimized, CustomersOptimized;
let TeamOptimized, AnalyticsOptimized, SettingsOptimized, BillingOptimized;

// Try to load optimized components with fallbacks
try {
    // For now, create placeholder components until optimized versions are built
    const PlaceholderComponent = ({ title = 'Loading...' }) => (
        <div className="min-h-screen flex items-center justify-center">
            <div className="text-center">
                <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600 mx-auto"></div>
                <p className="mt-4 text-gray-600">{title}</p>
            </div>
        </div>
    );
    
    // Use placeholder components for now
    DashboardOptimized = () => <PlaceholderComponent title="Dashboard wird geladen..." />;
    CallsOptimized = () => <PlaceholderComponent title="Anrufe werden geladen..." />;
    AppointmentsOptimized = () => <PlaceholderComponent title="Termine werden geladen..." />;
    CustomersOptimized = () => <PlaceholderComponent title="Kunden werden geladen..." />;
    TeamOptimized = () => <PlaceholderComponent title="Team wird geladen..." />;
    AnalyticsOptimized = () => <PlaceholderComponent title="Analytics wird geladen..." />;
    SettingsOptimized = () => <PlaceholderComponent title="Einstellungen werden geladen..." />;
    BillingOptimized = () => <PlaceholderComponent title="Abrechnung wird geladen..." />;
} catch (error) {
    console.error('Error loading portal components:', error);
}

// Fallback imports for non-optimized components (if optimized versions fail)
const componentMap = {
    dashboard: DashboardOptimized,
    calls: CallsOptimized,
    appointments: AppointmentsOptimized,
    customers: CustomersOptimized,
    team: TeamOptimized,
    analytics: AnalyticsOptimized,
    settings: SettingsOptimized,
    billing: BillingOptimized,
};

// Error Boundary Component
class ErrorBoundary extends React.Component {
    constructor(props) {
        super(props);
        this.state = { hasError: false, error: null };
    }

    static getDerivedStateFromError(error) {
        return { hasError: true, error };
    }

    componentDidCatch(error, errorInfo) {
        console.error('Portal Error:', error, errorInfo);
    }

    render() {
        if (this.state.hasError) {
            return (
                <div className="min-h-screen flex items-center justify-center">
                    <div className="text-center">
                        <h1 className="text-2xl font-bold text-red-600 mb-4">
                            Something went wrong
                        </h1>
                        <p className="text-gray-600 mb-4">
                            We're sorry, but something went wrong. Please try refreshing the page.
                        </p>
                        <button
                            onClick={() => window.location.reload()}
                            className="px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-600"
                        >
                            Refresh Page
                        </button>
                    </div>
                </div>
            );
        }

        return this.props.children;
    }
}

// Main Portal App Component
function PortalApp() {
    return (
        <ErrorBoundary>
            <BrowserRouter basename="/business">
                <Routes>
                    <Route path="/" element={<Navigate to="/dashboard" replace />} />
                    <Route path="/dashboard" element={<DashboardOptimized />} />
                    <Route path="/calls" element={<CallsOptimized />} />
                    <Route path="/calls/:id" element={<CallsOptimized />} />
                    <Route path="/appointments" element={<AppointmentsOptimized />} />
                    <Route path="/customers" element={<CustomersOptimized />} />
                    <Route path="/customers/:id" element={<CustomersOptimized />} />
                    <Route path="/team" element={<TeamOptimized />} />
                    <Route path="/analytics" element={<AnalyticsOptimized />} />
                    <Route path="/settings" element={<SettingsOptimized />} />
                    <Route path="/billing" element={<BillingOptimized />} />
                    <Route path="*" element={<Navigate to="/dashboard" replace />} />
                </Routes>
            </BrowserRouter>
        </ErrorBoundary>
    );
}

// Mount function for different entry points
export function mountPortal(elementId = 'app') {
    const container = document.getElementById(elementId);
    if (!container) {
        console.error(`Mount point #${elementId} not found`);
        return;
    }

    const root = ReactDOM.createRoot(container);
    root.render(<PortalApp />);
}

// Auto-mount if there's a portal app container
if (document.getElementById('portal-app')) {
    mountPortal('portal-app');
}

// Export for manual mounting
export default PortalApp;
export { componentMap };
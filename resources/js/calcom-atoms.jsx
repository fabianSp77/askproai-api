import React from 'react';
import { createRoot } from 'react-dom/client';
import '@calcom/atoms/globals.min.css';

// Import components
import LoadingState from './components/calcom/LoadingState';
import ErrorState from './components/calcom/ErrorState';
// Import BookerWidget eagerly (not lazy) because it's needed immediately
import CalcomBookerWidget from './components/calcom/CalcomBookerWidget';

// Error boundary component for catching render errors
class CalcomErrorBoundary extends React.Component {
    constructor(props) {
        super(props);
        this.state = { hasError: false };
    }

    static getDerivedStateFromError(error) {
        console.error('CalcomErrorBoundary caught error:', error);
        return { hasError: true };
    }

    componentDidCatch(error, errorInfo) {
        console.error('CalcomErrorBoundary componentDidCatch:', error, errorInfo);
    }

    render() {
        if (this.state.hasError) {
            return (
                <ErrorState
                    message="Failed to initialize Cal.com widget. Please try refreshing the page."
                    onRetry={() => window.location.reload()}
                />
            );
        }

        return this.props.children;
    }
}

const CalcomRescheduleWidget = React.lazy(() =>
    import('./components/calcom/CalcomRescheduleWidget')
        .catch((error) => {
            console.error('Failed to load CalcomRescheduleWidget:', error);
            return { default: () => <ErrorState message="Failed to load reschedule component" onRetry={null} /> };
        })
);

const CalcomCancelWidget = React.lazy(() =>
    import('./components/calcom/CalcomCancelWidget')
        .catch((error) => {
            console.error('Failed to load CalcomCancelWidget:', error);
            return { default: () => <ErrorState message="Failed to load cancel component" onRetry={null} /> };
        })
);

// Track initialized elements to prevent duplicate mounting
const initializedElements = new WeakSet();

// Initialize Cal.com widgets
function initializeCalcomWidgets() {
    console.log('🎯 initializeCalcomWidgets() called');

    // Booker widgets
    document.querySelectorAll('[data-calcom-booker]').forEach((el) => {
        if (initializedElements.has(el)) return;
        initializedElements.add(el);

        try {
            console.log('📦 Mounting CalcomBookerWidget to:', el);
            const root = createRoot(el);
            const props = JSON.parse(el.dataset.calcomBooker || '{}');
            root.render(
                <CalcomErrorBoundary>
                    <CalcomBookerWidget {...props} />
                </CalcomErrorBoundary>
            );
            console.log('✅ CalcomBookerWidget rendered successfully');
        } catch (error) {
            console.error('❌ Failed to initialize CalcomBookerWidget:', error);
            el.innerHTML = '<div style="padding: 1rem; background: #fee; color: #c00; border: 1px solid #f99; border-radius: 4px;">Failed to load booking widget. Please try refreshing the page.</div>';
        }
    });

    // Reschedule widgets
    document.querySelectorAll('[data-calcom-reschedule]').forEach((el) => {
        if (initializedElements.has(el)) return;
        initializedElements.add(el);

        try {
            console.log('📦 Mounting CalcomRescheduleWidget to:', el);
            const root = createRoot(el);
            const props = JSON.parse(el.dataset.calcomReschedule || '{}');
            root.render(
                <CalcomErrorBoundary>
                    <React.Suspense fallback={<LoadingState />}>
                        <CalcomRescheduleWidget {...props} />
                    </React.Suspense>
                </CalcomErrorBoundary>
            );
            console.log('✅ CalcomRescheduleWidget rendered successfully');
        } catch (error) {
            console.error('❌ Failed to initialize CalcomRescheduleWidget:', error);
            el.innerHTML = '<div style="padding: 1rem; background: #fee; color: #c00; border: 1px solid #f99; border-radius: 4px;">Failed to load reschedule widget. Please try refreshing the page.</div>';
        }
    });

    // Cancel widgets
    document.querySelectorAll('[data-calcom-cancel]').forEach((el) => {
        if (initializedElements.has(el)) return;
        initializedElements.add(el);

        try {
            console.log('📦 Mounting CalcomCancelWidget to:', el);
            const root = createRoot(el);
            const props = JSON.parse(el.dataset.calcomCancel || '{}');
            root.render(
                <CalcomErrorBoundary>
                    <React.Suspense fallback={<LoadingState />}>
                        <CalcomCancelWidget {...props} />
                    </React.Suspense>
                </CalcomErrorBoundary>
            );
            console.log('✅ CalcomCancelWidget rendered successfully');
        } catch (error) {
            console.error('❌ Failed to initialize CalcomCancelWidget:', error);
            el.innerHTML = '<div style="padding: 1rem; background: #fee; color: #c00; border: 1px solid #f99; border-radius: 4px;">Failed to load cancel widget. Please try refreshing the page.</div>';
        }
    });
}

// Auto-initialize on DOMContentLoaded (for non-Livewire pages)
document.addEventListener('DOMContentLoaded', initializeCalcomWidgets);

// Initialize on Livewire navigation (Filament uses Livewire for page navigation)
document.addEventListener('livewire:navigated', initializeCalcomWidgets);

// Watch for dynamically added elements (Filament/Livewire content)
const observer = new MutationObserver((mutations) => {
    const hasCalcomElements = mutations.some(mutation =>
        Array.from(mutation.addedNodes).some(node =>
            node.nodeType === 1 && (
                node.matches?.('[data-calcom-booker], [data-calcom-reschedule], [data-calcom-cancel]') ||
                node.querySelector?.('[data-calcom-booker], [data-calcom-reschedule], [data-calcom-cancel]')
            )
        )
    );

    if (hasCalcomElements) {
        initializeCalcomWidgets();
    }
});

// Start observing when DOM is ready
if (document.body) {
    observer.observe(document.body, { childList: true, subtree: true });
} else {
    document.addEventListener('DOMContentLoaded', () => {
        observer.observe(document.body, { childList: true, subtree: true });
    });
}

// Expose for manual initialization if needed
window.CalcomWidgets = { initialize: initializeCalcomWidgets };

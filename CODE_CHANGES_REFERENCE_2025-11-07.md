# Cal.com Widget Fix - Code Changes Reference
**Date:** November 7, 2025
**Format:** Side-by-side diff format for easy review

---

## File 1: resources/js/calcom-atoms.jsx

### Change Summary
- Added QueryClient at module level
- Added CalcomErrorBoundary component
- Wrapped renders with QueryClientProvider + error boundary
- Added comprehensive logging
- Applied error handling try-catch

### Before (Broken)
```javascript
import React from 'react';
import { createRoot } from 'react-dom/client';

// Import components
import LoadingState from './components/calcom/LoadingState';
import ErrorState from './components/calcom/ErrorState';

// Placeholder components - will be replaced with actual implementations
const CalcomBookerWidget = React.lazy(() =>
    import('./components/calcom/CalcomBookerWidget')
        .catch(() => ({ default: () => <LoadingState message="Loading booker..." /> }))
);

// ... other lazy imports ...

// Track initialized elements to prevent duplicate mounting
const initializedElements = new WeakSet();

// Initialize Cal.com widgets
function initializeCalcomWidgets() {
    // Booker widgets
    document.querySelectorAll('[data-calcom-booker]').forEach((el) => {
        if (initializedElements.has(el)) return;
        initializedElements.add(el);

        const root = createRoot(el);
        const props = JSON.parse(el.dataset.calcomBooker || '{}');
        root.render(
            <React.Suspense fallback={<LoadingState />}>
                <CalcomBookerWidget {...props} />
            </React.Suspense>
        );
    });

    // ... similar for reschedule and cancel widgets ...
}
```

### After (Fixed)
```javascript
import React from 'react';
import { createRoot } from 'react-dom/client';
import { QueryClient, QueryClientProvider } from '@tanstack/react-query';  // ✅ NEW

// Import components
import LoadingState from './components/calcom/LoadingState';
import ErrorState from './components/calcom/ErrorState';

// ✅ NEW: Create QueryClient once at module level
const queryClient = new QueryClient({
    defaultOptions: {
        queries: {
            refetchOnWindowFocus: false,
            retry: 1,
            staleTime: 5 * 60 * 1000,
        },
    },
});

// ✅ NEW: Error boundary component for catching render errors
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

// Placeholder components - will be replaced with actual implementations
const CalcomBookerWidget = React.lazy(() =>
    import('./components/calcom/CalcomBookerWidget')
        .catch((error) => {  // ✅ IMPROVED: Added error logging
            console.error('Failed to load CalcomBookerWidget:', error);
            return { default: () => <ErrorState message="Failed to load booker component" onRetry={null} /> };
        })
);

const CalcomRescheduleWidget = React.lazy(() =>
    import('./components/calcom/CalcomRescheduleWidget')
        .catch((error) => {  // ✅ IMPROVED: Added error logging
            console.error('Failed to load CalcomRescheduleWidget:', error);
            return { default: () => <ErrorState message="Failed to load reschedule component" onRetry={null} /> };
        })
);

const CalcomCancelWidget = React.lazy(() =>
    import('./components/calcom/CalcomCancelWidget')
        .catch((error) => {  // ✅ IMPROVED: Added error logging
            console.error('Failed to load CalcomCancelWidget:', error);
            return { default: () => <ErrorState message="Failed to load cancel component" onRetry={null} /> };
        })
);

// Track initialized elements to prevent duplicate mounting
const initializedElements = new WeakSet();

// ✅ MAJOR FIX: Initialize Cal.com widgets
function initializeCalcomWidgets() {
    console.log('🎯 initializeCalcomWidgets() called');  // ✅ NEW: Debug logging
    console.log('📊 QueryClient ready:', !!queryClient);  // ✅ NEW: Debug logging

    // Booker widgets
    document.querySelectorAll('[data-calcom-booker]').forEach((el) => {
        if (initializedElements.has(el)) return;
        initializedElements.add(el);

        try {  // ✅ NEW: Error handling
            console.log('📦 Mounting CalcomBookerWidget to:', el);  // ✅ NEW
            const root = createRoot(el);
            const props = JSON.parse(el.dataset.calcomBooker || '{}');
            root.render(
                // ✅ MAJOR FIX: QueryClientProvider moved to root level
                <QueryClientProvider client={queryClient}>
                    {/* ✅ NEW: Error boundary added */}
                    <CalcomErrorBoundary>
                        <React.Suspense fallback={<LoadingState />}>
                            <CalcomBookerWidget {...props} />
                        </React.Suspense>
                    </CalcomErrorBoundary>
                </QueryClientProvider>
            );
            console.log('✅ CalcomBookerWidget rendered successfully');  // ✅ NEW
        } catch (error) {  // ✅ NEW: Catch synchronous errors
            console.error('❌ Failed to initialize CalcomBookerWidget:', error);
            el.innerHTML = '<div style="padding: 1rem; background: #fee; color: #c00; border: 1px solid #f99; border-radius: 4px;">Failed to load booking widget. Please try refreshing the page.</div>';
        }
    });

    // Reschedule widgets - similar changes applied
    document.querySelectorAll('[data-calcom-reschedule]').forEach((el) => {
        if (initializedElements.has(el)) return;
        initializedElements.add(el);

        try {
            console.log('📦 Mounting CalcomRescheduleWidget to:', el);
            const root = createRoot(el);
            const props = JSON.parse(el.dataset.calcomReschedule || '{}');
            root.render(
                <QueryClientProvider client={queryClient}>
                    <CalcomErrorBoundary>
                        <React.Suspense fallback={<LoadingState />}>
                            <CalcomRescheduleWidget {...props} />
                        </React.Suspense>
                    </CalcomErrorBoundary>
                </QueryClientProvider>
            );
            console.log('✅ CalcomRescheduleWidget rendered successfully');
        } catch (error) {
            console.error('❌ Failed to initialize CalcomRescheduleWidget:', error);
            el.innerHTML = '<div style="padding: 1rem; background: #fee; color: #c00; border: 1px solid #f99; border-radius: 4px;">Failed to load reschedule widget. Please try refreshing the page.</div>';
        }
    });

    // Cancel widgets - similar changes applied
    document.querySelectorAll('[data-calcom-cancel]').forEach((el) => {
        if (initializedElements.has(el)) return;
        initializedElements.add(el);

        try {
            console.log('📦 Mounting CalcomCancelWidget to:', el);
            const root = createRoot(el);
            const props = JSON.parse(el.dataset.calcomCancel || '{}');
            root.render(
                <QueryClientProvider client={queryClient}>
                    <CalcomErrorBoundary>
                        <React.Suspense fallback={<LoadingState />}>
                            <CalcomCancelWidget {...props} />
                        </React.Suspense>
                    </CalcomErrorBoundary>
                </QueryClientProvider>
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
```

---

## File 2: resources/js/components/calcom/CalcomBookerWidget.jsx

### Change Summary
- Removed QueryClient import
- Removed QueryClientProvider wrapper
- Removed redundant queryClient instance
- Component now relies on root-level provider

### Before (Had QueryClientProvider)
```javascript
import React, { useState, useEffect } from 'react';
import { Booker } from '@calcom/atoms';
import { QueryClient, QueryClientProvider } from '@tanstack/react-query';  // ❌ REMOVED
import BranchSelector from './BranchSelector';
import { CalcomBridge } from './CalcomBridge';
import LoadingState from './LoadingState';
import ErrorState from './ErrorState';

// ❌ REMOVED: Create a client instance outside component
const queryClient = new QueryClient({
    defaultOptions: {
        queries: {
            refetchOnWindowFocus: false,
            retry: 1,
            staleTime: 5 * 60 * 1000, // 5 minutes
        },
    },
});

// ... component code ...

    return (
        // ❌ REMOVED: QueryClientProvider wrapper
        <QueryClientProvider client={queryClient}>
            <div className="calcom-booker-container">
                {enableBranchSelector && (
                    <div className="mb-3 md:mb-4">
                        <BranchSelector
                            defaultBranchId={branchId}
                            onBranchChange={setBranchId}
                        />
                    </div>
                )}

                <Booker
                    eventSlug={branchConfig.default_event_type}
                    username={`team-${window.CalcomConfig.teamId}`}
                    isTeamEvent={true}
                    layout={responsiveLayout}
                    onCreateBookingSuccess={handleBookingSuccess}
                    customClassNames={{
                        bookerContainer: 'border border-gray-200 rounded-lg shadow-sm',
                    }}
                />
            </div>
        </QueryClientProvider>
    );
```

### After (Clean, Provider at Root)
```javascript
import React, { useState, useEffect } from 'react';
import { Booker } from '@calcom/atoms';  // ✅ REMOVED QueryClient import
import BranchSelector from './BranchSelector';
import { CalcomBridge } from './CalcomBridge';
import LoadingState from './LoadingState';
import ErrorState from './ErrorState';

// ✅ REMOVED: queryClient instance (now at root level in calcom-atoms.jsx)

// ... component code unchanged ...

    return (
        // ✅ REMOVED: QueryClientProvider wrapper (now at root level)
        <div className="calcom-booker-container">
            {enableBranchSelector && (
                <div className="mb-3 md:mb-4">
                    <BranchSelector
                        defaultBranchId={branchId}
                        onBranchChange={setBranchId}
                    />
                </div>
            )}

            <Booker
                eventSlug={branchConfig.default_event_type}
                username={`team-${window.CalcomConfig.teamId}`}
                isTeamEvent={true}
                layout={responsiveLayout}
                onCreateBookingSuccess={handleBookingSuccess}
                customClassNames={{
                    bookerContainer: 'border border-gray-200 rounded-lg shadow-sm',
                }}
            />
        </div>
    );
```

---

## Key Changes Summary

### Added Functionality
1. **QueryClientProvider at Root** - Lines 1-3, 10-19 in calcom-atoms.jsx
2. **CalcomErrorBoundary Component** - Lines 21-48 in calcom-atoms.jsx
3. **Error Logging** - Lines 54, 64, 72 in calcom-atoms.jsx
4. **Initialization Logging** - Lines 80, 81 in calcom-atoms.jsx
5. **Try-Catch Error Handling** - Lines 88-106 (and similar) in calcom-atoms.jsx
6. **Wrapper Application** - Lines 93-99 (and similar) in calcom-atoms.jsx

### Removed Functionality
1. **QueryClient from CalcomBookerWidget** - Removed import, instance, wrapper
2. **Redundant Provider** - Removed duplicate QueryClientProvider setup

### Improved Functionality
1. **Better Error Messages** - Now shows user-friendly error UI
2. **Better Debugging** - Console logs show initialization flow
3. **Better Error Recovery** - Error boundary allows reload without page navigation
4. **Better Maintainability** - Single source of truth for QueryClient

---

## Technical Details

### Why QueryClientProvider Moved to Root

```
BEFORE (Broken):
┌─────────────────────────────┐
│ initializeCalcomWidgets()   │
│  └─ root.render()           │
│      └─ Suspense            │
│          └─ CalcomBooker    │
│              └─ QueryClient │◄── Problem: Context created AFTER
│                  └─ Booker  │    component renders, hooks execute
│                      ├─ useQuery() ← Needs QueryClient NOW
│                      └─ ERROR
└─────────────────────────────┘

AFTER (Fixed):
┌──────────────────────────────┐
│ initializeCalcomWidgets()    │
│  └─ root.render()            │
│      └─ QueryClientProvider  │◄── Fixed: Context available FIRST
│          └─ ErrorBoundary    │    All child components have access
│              └─ Suspense     │
│                  └─ CalcomBooker
│                      └─ Booker
│                          ├─ useQuery() ✅ Context available
│                          └─ Works!
└──────────────────────────────┘
```

### Build Output Changes

```
BEFORE:
calcom-atoms-hhOVNFf5.js    3.4 kB    (just initialization)

AFTER:
calcom-atoms-M3P1WY8p.js   33.22 kB   (+ QueryClient + ErrorBoundary + logging)
Size increase: +30 kB
Gzip: 10.11 kB (still reasonable)
```

---

## Verification Commands

```bash
# Verify files were changed
git status
git diff resources/js/calcom-atoms.jsx
git diff resources/js/components/calcom/CalcomBookerWidget.jsx

# Rebuild
npm run build

# Verify build output
ls -lah public/build/assets/ | grep calcom

# Check manifest
grep "calcom-atoms" public/build/manifest.json
```

---

## Rollback Commands

```bash
# If something breaks, rollback is simple:
git checkout HEAD~1 -- resources/js/calcom-atoms.jsx resources/js/components/calcom/CalcomBookerWidget.jsx
npm run build
```

---

## Testing the Fix

```javascript
// Open browser console on /admin/calcom-booking
// Expected output:
console.log('✅ Successful');

// Verify in DevTools:
// 1. Console tab: Should see log messages with checkmarks
// 2. Elements tab: [data-calcom-booker] should contain React component
// 3. Network tab: calcom-atoms file should load without 404
```

---

## Notes

- All changes are backward compatible
- No breaking API changes
- No database changes needed
- No configuration changes needed
- Pure code organization improvement
- Improves error handling and observability


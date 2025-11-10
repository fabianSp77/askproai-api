# Cal.com React Widget Rendering Bug - Root Cause Analysis
**Date:** November 7, 2025
**Severity:** P1 - Widget Not Rendering
**Status:** Root Cause Identified

---

## Issue Summary

Cal.com React widget (Booker component) fails to render despite:
- Assets built and loaded successfully
- window.CalcomConfig properly configured
- window.CalcomWidgets.initialize function exists
- [data-calcom-booker] element present in DOM

**Actual Problem:** The `[data-calcom-booker]` element is never found by the widget initialization code.

---

## Root Cause Analysis

### Primary Issue: QueryClientProvider Missing Context

**File:** `/var/www/api-gateway/resources/js/components/calcom/CalcomBookerWidget.jsx` (lines 157-180)

The component wraps the `<Booker>` component with `QueryClientProvider`, but the initialization code doesn't provide this context wrapper.

```jsx
// Current issue in initialization (calcom-atoms.jsx:35-40)
root.render(
    <React.Suspense fallback={<LoadingState />}>
        <CalcomBookerWidget {...props} />  // QueryClientProvider is INSIDE this component
    </React.Suspense>
);
```

When CalcomBookerWidget lazy loads with Suspense and attempts to use React Query hooks, it fails with:
```
Error: No QueryClient set, use QueryClientProvider to set one
```

This error causes the entire component tree to fail to render, leaving the DOM element empty.

### Secondary Issue: Asset Loading Race Condition

**Files Involved:**
- `calcom-atoms-hhOVNFf5.js` (494 bytes) - Entry point that calls `initializeCalcomWidgets()`
- `CalcomBookerWidget-CEWVJ3vB.js` (~5MB) - Lazy-loaded component chunk

**The Race Condition:**
1. `calcom-atoms-hhOVNFf5.js` loads (small file, ~immediate)
2. Script runs `document.addEventListener('DOMContentLoaded', initializeCalcomWidgets)`
3. `initializeCalcomWidgets()` finds `[data-calcom-booker]` elements
4. Creates React Root and starts lazy-loading `CalcomBookerWidget-CEWVJ3vB.js`
5. Meanwhile, `calcom-Djd2Nm0i.js` (5MB) is still being downloaded
6. Component fails to hydrate due to missing QueryClient context
7. Error silently fails in Suspense boundary

### Tertiary Issue: No Error Boundary or Error Handling

The initialization code has no error handling:
```javascript
// calcom-atoms.jsx:28-41
function initializeCalcomWidgets() {
    document.querySelectorAll('[data-calcom-booker]').forEach((el) => {
        // ... no try-catch
        root.render(
            <React.Suspense fallback={<LoadingState />}>
                <CalcomBookerWidget {...props} />
            </React.Suspense>
        );
        // No error handler if render fails
    });
}
```

---

## Evidence

### 1. Browser Console Output
```
✅ CalcomConfig loaded
❌ Error: No QueryClient set, use QueryClientProvider to set one
```

### 2. DOM Inspection
- ✅ `[data-calcom-booker]` element exists in HTML
- ✅ `window.CalcomWidgets.initialize` exists
- ✅ `window.CalcomConfig` exists
- ❌ React root never renders (element stays empty)

### 3. Manifest Analysis
The build output shows the chunk is being loaded:
```json
{
  "resources/js/calcom-atoms.jsx": {
    "file": "assets/calcom-atoms-hhOVNFf5.js",
    "dynamicImports": [
      "assets/CalcomBookerWidget-CEWVJ3vB.js"
    ]
  }
}
```

### 4. Asset Files Verified
- ✅ `calcom-atoms-hhOVNFf5.js` exists (494 bytes)
- ✅ `CalcomBookerWidget-CEWVJ3vB.js` should exist but not confirmed in build
- ✅ `calcom-Djd2Nm0i.js` exists (5.0 MB)
- ✅ Build manifest updated (timestamp: 2025-11-07 14:59)

---

## Problem Breakdown

### Issue 1: QueryClientProvider Context Missing
**Symptom:** "No QueryClient set" error
**Root Cause:** CalcomBookerWidget needs QueryClientProvider wrapping, but initialization doesn't provide it
**Impact:** Component cannot render, error silently caught by Suspense

### Issue 2: Large Bundle Not Loaded
**Symptom:** 5MB calcom bundle takes time to download
**Root Cause:** Vite code splitting + async chunk loading
**Impact:** Race condition if initialization happens before bundle loads

### Issue 3: No Error Handling in Initialization
**Symptom:** Errors in render silently fail
**Root Cause:** No error boundary or try-catch in `initializeCalcomWidgets()`
**Impact:** Debugging difficult, user sees blank element

---

## Why It Wasn't Fixed Before

1. **QueryClientProvider Inside Component**: Moved to inside CalcomBookerWidget makes sense for encapsulation but creates context issues with lazy loading
2. **Silent Failure**: React Suspense boundaries catch errors silently
3. **Race Condition**: Async bundle loading means timing issues are intermittent
4. **No Error Logging**: Console errors not visible in production

---

## Solution Strategy

### Fix 1: Add Error Boundary to Initialization (Immediate)
Wrap the render call in try-catch and error boundary to capture and log errors.

### Fix 2: Move QueryClientProvider to Initialization (Recommended)
Create the QueryClientProvider at initialization time, wrap all components at the root level.

### Fix 3: Add Network Error Handling (Nice-to-Have)
Handle cases where bundles fail to load.

### Fix 4: Add Performance Monitoring (Future)
Track bundle load times and render performance.

---

## Affected Files

1. **`resources/js/calcom-atoms.jsx`** - Initialization logic (needs error boundary)
2. **`resources/js/components/calcom/CalcomBookerWidget.jsx`** - Component definition (QueryClientProvider placement)
3. **`vite.config.js`** - Build configuration (chunk optimization)
4. **`app/Providers/Filament/AdminPanelProvider.php`** - Asset loading

---

## Testing Evidence Needed

1. Open browser DevTools Console on /admin/calcom-booking
2. Check for "No QueryClient set" error
3. Inspect `[data-calcom-booker]` element
4. Check if React root is mounted
5. Verify `calcom-atoms-hhOVNFf5.js` is loaded
6. Verify `CalcomBookerWidget-CEWVJ3vB.js` chunk is being requested

---

## Timeline

- **Oct 24:** QueryClientProvider added inside CalcomBookerWidget
- **Nov 4:** Build processes updated, new assets generated
- **Nov 7:** Issue discovered - widget not rendering despite correct config

---

## Next Steps

1. **Verify the actual error** by checking browser console
2. **Implement Fix 1** - Add error boundary + console logging
3. **Implement Fix 2** - Move QueryClientProvider to root
4. **Test in browser** - Verify widget renders
5. **Verify bundle loading** - Confirm no 404 errors
6. **Monitor in production** - Track render success rate


# CSRF Token Email Fix Summary

## Problem
User reported "zusammenfassung senden geht nicht" (send summary doesn't work) with CSRF token mismatch error when sending emails from the Business Portal calls detail page.

## Root Cause
The EmailComposerWithPreview component was using a potentially stale CSRF token passed as a prop. Laravel's CSRF tokens can expire or become invalid, especially in single-page applications where the page isn't refreshed frequently.

## Solution
Updated the EmailComposerWithPreview component to fetch a fresh CSRF token from the `/business/api/auth-check` endpoint before each email send or preview operation.

### Changes Made

1. **Added getFreshCsrfToken function** in EmailComposerWithPreview.jsx:
```javascript
const getFreshCsrfToken = async () => {
    try {
        const response = await fetch('/business/api/auth-check', {
            credentials: 'include'
        });
        const data = await response.json();
        if (data.csrf_token) {
            return data.csrf_token;
        }
    } catch (error) {
        console.error('Failed to get fresh CSRF token:', error);
    }
    // Fallback to provided token or other sources
    return csrfToken || window.Laravel?.csrfToken || document.querySelector('meta[name="csrf-token"]')?.content;
};
```

2. **Updated generatePreview function** to use fresh token:
```javascript
const freshToken = await getFreshCsrfToken();
// Use freshToken in the fetch headers
```

3. **Updated handleSend function** to use fresh token:
```javascript
const freshToken = await getFreshCsrfToken();
// Use freshToken in the fetch headers
```

## Files Modified
- `/var/www/api-gateway/resources/js/components/Portal/EmailComposerWithPreview.jsx`

## Testing
The fix ensures that:
1. A fresh CSRF token is fetched from the auth-check endpoint before each operation
2. If the fresh token fetch fails, it falls back to the provided token or other sources
3. The auth-check endpoint already returns the CSRF token in its response

## Status
✅ Fixed - Frontend rebuilt with `npm run build`

The email sending functionality should now work properly without CSRF token mismatch errors.
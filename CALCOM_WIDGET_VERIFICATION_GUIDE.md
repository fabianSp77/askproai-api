# Cal.com Widget - Quick Verification Guide
**Updated:** November 7, 2025

---

## Quick Test (5 minutes)

### Step 1: Browser Console (30 seconds)

1. Open your browser's Developer Tools (F12 or Cmd+Option+I)
2. Go to the **Console** tab
3. Navigate to `/admin/calcom-booking`
4. Look for these log messages:

**Expected Output:**
```
🎯 initializeCalcomWidgets() called
📊 QueryClient ready: true
📦 Mounting CalcomBookerWidget to: <div data-calcom-booker>
✅ CalcomBookerWidget rendered successfully
```

**If you see these → SUCCESS** ✅

**If NOT → Check for errors** ❌
```
❌ Failed to initialize CalcomBookerWidget: Error: ...
Error: No QueryClient set, use QueryClientProvider to set one
```

---

### Step 2: DOM Inspection (1 minute)

1. Right-click on the booking widget area
2. Select **Inspect** or **Inspect Element**
3. Look for `<div data-calcom-booker>`

**Expected (Working):**
```html
<div data-calcom-booker="...">
    <div><!-- React root -->
        <div class="calcom-booker-container">
            <!-- Cal.com Booker component rendered here -->
            <div class="border border-gray-200 rounded-lg shadow-sm">
                <!-- Calendar/booking UI -->
            </div>
        </div>
    </div>
</div>
```

**If Element is Empty (Broken):**
```html
<div data-calcom-booker="..."></div>
```

---

### Step 3: Network Tab (1 minute)

1. Open DevTools **Network** tab
2. Reload the page
3. Search for `calcom-atoms` in the filter box

**Expected Files:**
- `calcom-atoms-M3P1WY8p.js` - 33 KB (blue line = OK)
- Look for no 404 errors

**Status Should Be:**
- Green checkmark (200 OK)
- No red X (404 Not Found)

---

### Step 4: Functional Test (2 minutes)

1. If widget displays:
   - Does branch selector appear? (if enabled)
   - Can you see the calendar?
   - Can you click on a date?
   - Does the time picker appear?

2. Try booking:
   - Select a service/time
   - Fill in details
   - Click Book
   - Does success message appear?

---

## Detailed Verification

### Console Message Breakdown

| Message | Meaning | Status |
|---------|---------|--------|
| `🎯 initializeCalcomWidgets() called` | Widget initialization started | Should always appear |
| `📊 QueryClient ready: true` | React Query context available | Should always be true |
| `📦 Mounting CalcomBookerWidget to: <div>` | Component being mounted | Should appear for each widget |
| `✅ CalcomBookerWidget rendered successfully` | Render completed without errors | Should appear if working |

---

### Error Messages (What They Mean)

| Error | Cause | Fix |
|-------|-------|-----|
| `No QueryClient set, use QueryClientProvider` | Provider not wrapping component | Rebuild with latest code |
| `Failed to load CalcomBookerWidget: 404` | Bundle chunk missing | Check build output, rebuild |
| `Failed to fetch /api/calcom-atoms/branch/...` | API request failed | Check API endpoint, auth |
| `JSON.parse: unexpected character` | Invalid data attribute | Check Blade template syntax |

---

## Asset Files Verification

### Expected Build Output

After `npm run build`, you should see:

```
✓ built in 29.28s
✓ public/build/manifest.json
✓ public/build/assets/calcom-atoms-M3P1WY8p.js (33 kB)
✓ public/build/assets/calcom-atoms-C0ZlkIOC.css (2.6 kB)
✓ public/build/assets/react-vendor-C8w-UNLI.js
✓ public/build/assets/calcom-BAv3OAHL.js (5.2 MB)
```

### Check Files Exist

```bash
# Verify asset files exist
ls -lah public/build/assets/calcom-atoms*.js
ls -lah public/build/assets/calcom-atoms*.css
ls -lah public/build/assets/react-vendor*.js

# Check manifest
cat public/build/manifest.json | grep "calcom-atoms"
```

---

## Common Issues & Fixes

### Issue 1: Widget Element Empty (No Content)

**Symptoms:**
- `[data-calcom-booker]` element is completely empty
- Console shows: `❌ Failed to initialize CalcomBookerWidget`

**Causes & Fixes:**
1. **Stale Browser Cache**
   - Open DevTools → Settings → Disable cache while DevTools open
   - Hard reload: Ctrl+Shift+R (or Cmd+Shift+R on Mac)

2. **Old CSS Cached**
   - Clear browser cache: Settings → Clear browsing data
   - Or: Open in Incognito/Private window

3. **Build Not Deployed**
   - Run: `npm run build`
   - Check file timestamps: `ls -la public/build/assets/ | grep calcom`

### Issue 2: "No QueryClient Set" Error

**Symptoms:**
- Console error: `Error: No QueryClient set`
- Widget shows blank/error state

**Cause:**
- Using old code before fix applied

**Fix:**
- Pull latest changes: `git pull`
- Rebuild: `npm run build`
- Clear cache and reload

### Issue 3: Bundle Load Timeout

**Symptoms:**
- Network tab shows: `calcom-Djd2Nm0i.js` (5.2 MB) taking >10 seconds
- Widget stuck on "Loading..."

**Causes:**
1. Slow network connection
2. Large bundle size (5.2 MB is expected)

**Fixes:**
1. Enable HTTP/2 on server (faster parallel downloads)
2. Enable Gzip compression (already enabled)
3. Wait for slow connection to complete

### Issue 4: Multiple Widgets Not All Rendering

**Symptoms:**
- First widget renders, second doesn't
- Or: Booker renders, Reschedule doesn't

**Cause:**
- Widgets reusing same React root

**Fix:**
- Already fixed in code - each element gets its own root
- Verify WeakSet is working in console:
  ```javascript
  console.log(initializedElements); // Should show WeakSet with entries
  ```

---

## Performance Baseline

After fix, expect:

| Metric | Value | Status |
|--------|-------|--------|
| Widget Init Time | <500ms | Good |
| Bundle Load | 30-40MB/s | Depends on network |
| First Render | <1s | Good |
| Interaction | Immediate | Expected |

---

## Rollback Instructions

If something breaks and you need to revert:

```bash
# Check git status
git status

# See what changed
git diff resources/js/calcom-atoms.jsx
git diff resources/js/components/calcom/CalcomBookerWidget.jsx

# Revert to previous version
git checkout HEAD~1 -- resources/js/calcom-atoms.jsx resources/js/components/calcom/CalcomBookerWidget.jsx

# Rebuild
npm run build

# Verify
ls -lah public/build/assets/calcom-atoms*.js
npm run dev  # or run server to test
```

---

## Support Information

### Files to Check

1. **Widget Entry Point:** `resources/js/calcom-atoms.jsx`
2. **Component:** `resources/js/components/calcom/CalcomBookerWidget.jsx`
3. **Config:** `app/Providers/Filament/AdminPanelProvider.php` (asset loading)
4. **Template:** `resources/views/filament/pages/calcom-booking.blade.php`

### Logs to Check

```bash
# Laravel logs
tail -f storage/logs/laravel.log

# Check for any PHP errors
grep -i "error\|exception" storage/logs/laravel.log | tail -20

# Browser console (already covered above)
```

### API Endpoints

Check these are working:
- `GET /api/calcom-atoms/config` - Get branch config
- `GET /api/calcom-atoms/branch/{id}/config` - Get branch-specific config
- `POST /api/calcom-atoms/booking-created` - Log booking

---

## Questions?

1. **Is widget rendering?** → Check console for `✅` message
2. **Bundle loading slowly?** → Check Network tab, wait for 5.2MB file
3. **Getting errors?** → Copy error message and check this guide
4. **Still broken?** → Run `npm run build` and clear browser cache

---

## Final Checklist

- [ ] Console shows `✅ CalcomBookerWidget rendered successfully`
- [ ] Widget displays calendar/booking interface
- [ ] No JavaScript errors in console
- [ ] calcom-atoms file loads without 404
- [ ] Branch selector works (if enabled)
- [ ] Can select dates/times
- [ ] Booking submits successfully
- [ ] Success message appears after booking

**All checked?** → Deploy with confidence! ✅


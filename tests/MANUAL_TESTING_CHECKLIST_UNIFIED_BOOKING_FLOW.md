# Manual Testing Checklist - Unified Booking Flow

## Overview

This checklist ensures the V4 Unified Booking Flow works correctly across all browsers, themes, and edge cases.

**URL:** `/admin/appointments/create`

---

## ✅ Phase 7.1: Browser Compatibility

### Chrome (Desktop)

- [ ] Open Chrome browser
- [ ] Navigate to `/admin/appointments/create`
- [ ] Branch selection works (radio buttons clickable)
- [ ] Customer search shows results within 500ms
- [ ] Service selection updates calendar
- [ ] Calendar renders correctly (grid layout)
- [ ] Slot buttons are clickable
- [ ] Form submission works
- [ ] No console errors (press F12 → Console tab)
- [ ] Screenshot: `chrome-light-mode.png`

### Firefox (Desktop)

- [ ] Open Firefox browser
- [ ] Navigate to `/admin/appointments/create`
- [ ] Branch selection works
- [ ] Customer search shows results
- [ ] Service selection updates calendar
- [ ] Calendar renders correctly
- [ ] Slot buttons are clickable
- [ ] Form submission works
- [ ] No console errors (press F12 → Console tab)
- [ ] Screenshot: `firefox-light-mode.png`

### Safari (Mac/iOS) - if available

- [ ] Open Safari browser
- [ ] Navigate to `/admin/appointments/create`
- [ ] Branch selection works
- [ ] Customer search shows results
- [ ] Service selection updates calendar
- [ ] Calendar renders correctly
- [ ] Slot buttons are clickable
- [ ] Form submission works
- [ ] No console errors (Develop → Show JavaScript Console)
- [ ] Screenshot: `safari-light-mode.png`

---

## ✅ Phase 7.2: Dark Mode Testing

### Enable Dark Mode

**Method 1 (Filament UI):**
- Click user menu (top-right)
- Toggle "Dark Mode"

**Method 2 (Browser DevTools):**
```javascript
document.documentElement.classList.add('dark');
```

### Dark Mode Checklist

- [ ] Section borders are **clearly visible** (not gray-on-gray)
- [ ] Radio button borders have good contrast
- [ ] Search input border is visible
- [ ] Calendar grid lines are visible
- [ ] Text is readable (no light-gray-on-light-gray)
- [ ] Slot buttons stand out
- [ ] Focus indicators are visible (tab through elements)
- [ ] Error states are prominent
- [ ] Loading spinner is visible
- [ ] No "washed out" or invisible elements
- [ ] Screenshot: `dark-mode-contrast-test.png`

### Contrast Measurements (WCAG AA)

Use browser DevTools Contrast Checker:

1. Chrome DevTools → Elements → Styles → Color Picker → Contrast Ratio
2. Target: **3:1 minimum** for UI components

Check these elements in dark mode:
- [ ] `.fi-section` border: ≥ 3:1 contrast
- [ ] `.fi-radio-option` border: ≥ 3:1 contrast
- [ ] `.fi-search-input` border: ≥ 3:1 contrast
- [ ] `.fi-button-nav` border: ≥ 3:1 contrast
- [ ] Text colors: ≥ 4.5:1 contrast (body text)

---

## ✅ Phase 7.3: Functional Testing

### Branch Selection

- [ ] Single branch: Auto-selected and shown
- [ ] Multiple branches: All options visible
- [ ] Selecting branch updates form field `branch_id`
- [ ] Selected state is visually clear (highlighted)

**Console Check:**
```
[BookingFlowWrapper] Branch selected: <id>
[BookingFlowWrapper] branch_id updated: <id>
```

### Customer Search

- [ ] Search input accepts text
- [ ] Typing ≥ 3 characters triggers search
- [ ] Debounce works (waits 300ms before searching)
- [ ] Search results appear
- [ ] Clicking result populates customer
- [ ] Selected customer shown with green checkmark
- [ ] "Ändern" button works to clear selection
- [ ] No results message appears if no match

**Console Check:**
```
[BookingFlowWrapper] Customer selected: <id>
[BookingFlowWrapper] customer_id updated: <id>
```

### Service Selection

- [ ] All services displayed
- [ ] Service duration shown (e.g., "45 Minuten")
- [ ] Selecting service highlights it
- [ ] Calendar reloads with new slots
- [ ] Service name appears in calendar header

**Console Check:**
```
[BookingFlowWrapper] Service selected: <id>
[BookingFlowWrapper] service_id updated: <id>
```

### Employee Preference

- [ ] "Nächster verfügbarer" option visible
- [ ] Specific employees listed
- [ ] Selecting employee updates calendar
- [ ] Employee name confirmed in selection

**Console Check (if specific employee):**
```
[BookingFlowWrapper] Employee selected: <id>
[BookingFlowWrapper] staff_id updated: <id>
```

### Calendar & Slot Selection

- [ ] Week navigation works (← Vorherige / Nächste →)
- [ ] Current week shown (date range)
- [ ] 7 columns (Mo-So) visible
- [ ] Time labels (08:00-18:00) visible
- [ ] Available slots shown as blue buttons
- [ ] Clicking slot highlights it green
- [ ] Selected slot summary appears at bottom
- [ ] "Ändern" button works to deselect

**Console Check:**
```
[BookingFlowWrapper] Slot selected
[BookingFlowWrapper] starts_at updated: <datetime>
```

### Form Submission

- [ ] All required fields populated
- [ ] Submit button is enabled
- [ ] No validation errors shown
- [ ] Form can be submitted successfully
- [ ] Redirects to appointment detail page

---

## ✅ Phase 7.4: No Duplicate Fields

### Create Mode

- [ ] Navigate to `/admin/appointments/create`
- [ ] OLD service dropdown is **NOT visible**
- [ ] OLD staff dropdown is **NOT visible**
- [ ] Only NEW booking flow component visible
- [ ] No duplicate selection UI

### Edit Mode

- [ ] Navigate to `/admin/appointments/{id}/edit`
- [ ] OLD service dropdown **IS visible** (for reference)
- [ ] OLD staff dropdown **IS visible** (for reference)
- [ ] Week picker calendar also shown
- [ ] No conflicts between old and new UI

---

## ✅ Phase 7.5: Edge Cases

### No Data Scenarios

- [ ] **No branches:** Shows "Keine Filiale verfügbar"
- [ ] **No services:** Shows "Keine Services verfügbar"
- [ ] **No employees:** Only "Nächster verfügbarer" option
- [ ] **No slots:** Calendar empty, shows info message
- [ ] **Customer not found:** Shows "Kein Kunde gefunden"

### Network Errors

- [ ] Slow connection: Loading spinner appears
- [ ] API error: Error message shown (red alert)
- [ ] Error message is clear and helpful
- [ ] No JavaScript exceptions in console

### Keyboard Navigation (Accessibility)

- [ ] Tab through all interactive elements
- [ ] Focus indicators are **clearly visible**
- [ ] Can select branch with keyboard (Space/Enter)
- [ ] Can type in search input
- [ ] Can select service with keyboard
- [ ] Can navigate calendar with Tab
- [ ] Can select slot with Enter key
- [ ] No focus traps or inaccessible elements

### Mobile Responsive (Optional)

- [ ] Resize browser to 375px width
- [ ] Radio options stack vertically
- [ ] Search input full width
- [ ] Calendar scrolls horizontally
- [ ] All elements accessible
- [ ] Text is readable (no tiny fonts)

---

## ✅ Phase 7.6: Console Error Check

### Expected Console Logs

✅ **GOOD** (these are expected):
```
[BookingFlowWrapper] Branch selected: 123
[BookingFlowWrapper] Customer selected: 456
[BookingFlowWrapper] Service selected: 789
[BookingFlowWrapper] Slot selected
Livewire event dispatched
```

❌ **BAD** (these indicate problems):
```
Uncaught TypeError: Cannot read property 'value' of null
Failed to fetch
404 Not Found
ReferenceError: <variable> is not defined
Unhandled Promise Rejection
```

### How to Check

1. Press **F12** to open DevTools
2. Go to **Console** tab
3. Clear console (trash icon)
4. Complete booking flow
5. Review all messages
6. Report any red errors

---

## ✅ Phase 7.7: Performance Check

### Page Load Time

- [ ] Page loads within 2 seconds
- [ ] Livewire component initializes quickly
- [ ] No "flash of unstyled content"
- [ ] Smooth transitions

### Interaction Response Time

- [ ] Branch selection: Instant feedback
- [ ] Customer search: Results within 500ms
- [ ] Service selection: Calendar updates within 1s
- [ ] Slot selection: Instant highlight
- [ ] No lag or freezing

### Network Tab (Optional)

1. F12 → Network tab
2. Reload page
3. Check:
   - [ ] No failed requests (red)
   - [ ] Total load time < 3s
   - [ ] Livewire assets load correctly

---

## ✅ Phase 7.8: Visual Polish Check

### Typography

- [ ] All text is readable
- [ ] Font sizes appropriate
- [ ] No text overflow or clipping
- [ ] Consistent font weights

### Spacing & Layout

- [ ] Consistent padding/margins
- [ ] No overlapping elements
- [ ] Proper alignment
- [ ] Breathing room between sections

### Colors & Branding

- [ ] Consistent with Filament theme
- [ ] Primary color used correctly
- [ ] Success green for confirmations
- [ ] Error red for alerts
- [ ] No harsh color combinations

### Icons & Emojis

- [ ] Emojis render correctly (🏢 👤 💇 ⏰)
- [ ] Appropriate usage
- [ ] Not distracting

---

## 📊 Testing Summary

### Sign-Off Checklist

I have tested and verified:

- [ ] ✅ Chrome (Light Mode) - No issues
- [ ] ✅ Chrome (Dark Mode) - Good contrast
- [ ] ✅ Firefox (Light Mode) - No issues
- [ ] ✅ Firefox (Dark Mode) - Good contrast
- [ ] ✅ Safari (if available) - No issues
- [ ] ✅ Branch Selection - Works correctly
- [ ] ✅ Customer Search - Works correctly
- [ ] ✅ Service Selection - Works correctly
- [ ] ✅ Employee Preference - Works correctly
- [ ] ✅ Calendar & Slots - Works correctly
- [ ] ✅ Form Submission - Works correctly
- [ ] ✅ No Duplicate Fields - Verified
- [ ] ✅ Dark Mode Contrast - WCAG AA compliant
- [ ] ✅ Edge Cases - Handled gracefully
- [ ] ✅ Console Errors - None found
- [ ] ✅ Keyboard Navigation - Fully accessible
- [ ] ✅ Performance - Acceptable
- [ ] ✅ Visual Polish - Professional

### Issues Found

| Issue | Severity | Description | Screenshot |
|-------|----------|-------------|------------|
| #1    | High/Med/Low | Description | filename.png |
| #2    | High/Med/Low | Description | filename.png |

### Recommendations

- Issue #1: [Fix description]
- Issue #2: [Fix description]

---

**Tester Name:** _________________
**Date:** _________________
**Time Spent:** _______ minutes
**Overall Status:** ✅ Pass / ⚠️ Pass with Issues / ❌ Fail

---

## 📸 Required Screenshots

Please capture and save:

1. `chrome-light-mode-branch-selected.png`
2. `chrome-light-mode-customer-search.png`
3. `chrome-light-mode-service-selected.png`
4. `chrome-light-mode-calendar.png`
5. `chrome-dark-mode-full-page.png`
6. `firefox-dark-mode-full-page.png`
7. `console-events-log.png`
8. `no-duplicate-fields-create-mode.png`

Save to: `tests/puppeteer/screenshots/manual/`

---

**Phase 7 Complete when all checkboxes are ✅**

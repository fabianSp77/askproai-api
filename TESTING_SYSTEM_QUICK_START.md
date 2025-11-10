# Testing System - Quick Start Guide

**File**: `/var/www/api-gateway/public/docs/friseur1/agent-v50-interactive-complete.html`

---

## 🚀 5-Minute Quick Start

### 1. Open the Documentation
```
URL: https://api.askproai.de/docs/friseur1/agent-v50-interactive-complete.html
```

### 2. Configure API Access (Header Section)

**Bearer Token** (already set):
```
key_6ff998ba48e842092e04a5455d19
```
✅ Auto-saved to localStorage

**Test Mode Toggle**:
- ☐ Unchecked = Production Mode (real company, `call_xxx` IDs)
- ☑ Checked = Test Mode (test company, `test_call_xxx` IDs)

---

## 🎯 Testing Methods

### Method 1: Feature Matrix (Fastest)

1. Click **"📋 Feature Matrix"** tab
2. Click **"🧪 Test"** button on any function row
3. See inline result: ✅ 234ms or ❌ 1523ms

**Test All Functions**:
1. Click **"🧪 Test All Functions"** button
2. Watch progress bar
3. Pause/Resume/Stop as needed
4. Get summary notification

---

### Method 2: Quick Test Examples (Recommended)

1. Click **"⚙️ Functions"** tab
2. Select a function (e.g., check_availability)
3. Click **"🧪 Interactive Test"** sub-tab
4. Click a quick test button:
   - "Morgen 10 Uhr"
   - "Nächste Woche"
   - "Heute"
5. View results below

---

### Method 3: Custom Parameters

1. Go to **"⚙️ Functions"** tab
2. Select function
3. Click **"🧪 Interactive Test"** sub-tab
4. Fill form with custom values
5. Click **"🧪 Function Testen"**
6. View response

---

### Method 4: Webhook Testing

1. Click **"🔗 Webhooks & APIs"** tab
2. Scroll to webhook card
3. Click **"🧪 Test Endpoint"**
4. View inline result

---

## 📊 View Test Results

### Open Results Modal
1. Click **"📋 Show Test Results"** (anywhere)
2. Modal opens with all test history

### Filter Results
- **All** - Show all tests
- **✅ Success** - Only successful tests
- **❌ Error** - Only failed tests

### Investigate Failed Test
1. Click on red (error) result item
2. Item expands showing:
   - Full request payload
   - Error response
   - Stack trace (if available)

---

## 📋 Copy/Export Results

### Copy Full Report
1. Open Test Results modal
2. Click **"📋 Copy Full Report"**
3. Report copied to clipboard
4. Paste into email/Slack/ticket

### Export as JSON
1. Open Test Results modal
2. Click **"💾 Export JSON"**
3. Downloads `test-results-[timestamp].json`
4. For analysis in other tools

---

## 🧹 Clear Results

**Option 1**: From button
```
Click "🗑️ Clear Results" button
```

**Option 2**: From modal
```
Open modal → Click "🗑️ Clear All"
```

---

## 🎮 Keyboard Shortcuts

### While Testing
- **Pause**: Click ⏸️ button (or manually stop browser)
- **Resume**: Click ▶️ button
- **Stop**: Click ⏹️ button

### In Results Modal
- **Esc**: Close modal (click X or outside)
- **Click result**: Expand/collapse details

---

## 📈 Understanding Results

### Success Result ✅
```
✅ check_availability
234ms | Status: 200
```
- Green indicator
- Duration in ms
- HTTP status 200

### Error Result ❌
```
❌ start_booking
1523ms | Status: 500
```
- Red indicator
- Duration in ms
- HTTP status 4xx/5xx

### Result Details (Expanded)
```
Request:
{
  "name": "check_availability",
  "args": { ... }
}

Response:
{
  "success": true,
  "data": { ... }
}

Error: (if applicable)
"Database connection failed"
```

---

## ⚡ Pro Tips

### 1. Use Test Mode for Experimentation
- Safe testing without affecting production
- Test company context isolated
- Call IDs prefixed with `test_call_`

### 2. Quick Test Examples First
- Pre-configured with valid data
- Cover common use cases
- Faster than manual form filling

### 3. Test All for Comprehensive Check
- Tests all 13 live functions
- Takes ~30 seconds
- Good for release validation

### 4. Keep Test Results
- Auto-saved to localStorage
- Last 50 tests preserved
- Survives browser refresh

### 5. Copy Reports for Bug Reports
- Full formatted text report
- Include in GitHub issues
- Share with team in Slack

---

## 🐛 Troubleshooting

### Test Button Disabled
- **Cause**: Test already running
- **Fix**: Wait for current test to complete

### No Response Displayed
- **Cause**: Network error or timeout
- **Fix**: Check console, verify API is up

### "Unauthorized" Error
- **Cause**: Missing/invalid Bearer token
- **Fix**: Check token in header configuration

### Test Mode Not Working
- **Cause**: Checkbox not saving
- **Fix**: Clear localStorage, reload page

### Progress Bar Stuck
- **Cause**: API not responding
- **Fix**: Click Stop button, check API logs

---

## 🎯 Common Workflows

### Quick Validation
```
1. Open doc
2. Feature Matrix tab
3. Test All Functions
4. Check notification for success count
```

### Debug Failed Function
```
1. Feature Matrix tab
2. Click Test on specific function
3. If fails, click Show Test Results
4. Filter by Error
5. Click failed test to expand
6. Copy error details
```

### Test New Feature
```
1. Functions tab
2. Navigate to new function
3. Try Quick Test examples
4. If all pass, try custom parameters
5. Document any edge cases
```

### Create Bug Report
```
1. Reproduce issue with test
2. Open Test Results modal
3. Find failed test
4. Expand to see details
5. Click Copy Full Report
6. Paste in GitHub issue
```

---

## 📞 Support

### Documentation
- Full implementation details: `TESTING_SYSTEM_COMPLETE_2025-11-06.md`
- Code location: `/var/www/api-gateway/public/docs/friseur1/agent-v50-interactive-complete.html`

### Issues
- Create GitHub issue with:
  - Full error report (copy from modal)
  - Browser/OS details
  - Steps to reproduce

---

## ✅ Quick Reference

| Action | Location | Button |
|--------|----------|--------|
| Test one function | Feature Matrix | 🧪 Test |
| Test all functions | Feature Matrix | 🧪 Test All Functions |
| Quick test example | Functions → Interactive Test | Quick test buttons |
| Custom test | Functions → Interactive Test | 🧪 Function Testen |
| Test webhook | Webhooks & APIs | 🧪 Test Endpoint |
| View results | Any tab | 📋 Show Test Results |
| Copy report | Results modal | 📋 Copy Full Report |
| Export JSON | Results modal | 💾 Export JSON |
| Clear results | Any tab / Results modal | 🗑️ Clear Results |

---

**Last Updated**: 2025-11-06
**Version**: 1.0

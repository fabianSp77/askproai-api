# V87 Deployment Guide - Complete Context-Aware Booking Flow

**Date**: 2025-10-19
**Status**: ✅ Ready for Deployment
**Target**: Agent v116 with V87 Prompt

---

## 📋 What Changed

### Phase 1: V87 Prompt (Retell Agent)
- **File**: `/tmp/v87_retell_prompt_complete.json`
- **Key Addition**: PHASE 2b Logic for context-aware time updates
- **Impact**: Agent now understands time-only updates when date is already known
- **Example**: User says "14 Uhr" after "13 Uhr nicht verfügbar" → Agent uses known date + new time

### Phase 2: Backend Enhancement (DateTimeParser)
- **File**: `/var/www/api-gateway/app/Services/Retell/DateTimeParser.php`
- **New Methods**:
  - `parseTimeOnly($timeString, $contextDate)` - Parse time with context date
  - `isTimeOnly($input)` - Detect if input is time-only
- **Impact**: Backend can now distinguish between full date and time-only inputs

### Phase 3: Retell Tool Configuration
- **File**: `/tmp/v87_parse_date_tool_definition.json`
- **New Parameter**: `context_date` (optional) in parse_date tool
- **Impact**: Allows agent to pass context to backend when updating only time

---

## 🚀 Deployment Steps

### Step 1: Verify Backend Code (Already Done ✅)
```bash
# Check DateTimeParser has new methods
grep -A 10 "parseTimeOnly" app/Services/Retell/DateTimeParser.php
grep -A 10 "isTimeOnly" app/Services/Retell/DateTimeParser.php

# Result: Both methods present and fully implemented
```

### Step 2: Deploy V87 Prompt to Retell

**Option A: Via Retell Dashboard (Manual)**
1. Log into Retell.ai Dashboard
2. Open Agent: `agent_9a8202a740cd3120d96fcfda1e`
3. Go to: Agent Settings → System Instructions
4. Copy content from: `/tmp/v87_retell_prompt_complete.json` → `general_prompt` field
5. Save as draft
6. Review all 5 PHASES are present
7. Click "Publish" to activate v116

**Option B: Via Retell API (Automated)**
```bash
# Update LLM with V87 Prompt
curl -X PATCH "https://api.retellai.com/update-retell-llm/llm_f3209286ed1caf6a75906d2645b9" \
  -H "Authorization: Bearer key_6ff998ba48e842092e04a5455d19" \
  -H "Content-Type: application/json" \
  -d @/tmp/v87_retell_prompt_complete.json

# Then publish agent
curl -X PATCH "https://api.retellai.com/update-agent/agent_9a8202a740cd3120d96fcfda1e" \
  -H "Authorization: Bearer key_6ff998ba48e842092e04a5455d19" \
  -H "Content-Type: application/json" \
  -d '{"is_published": true}'
```

### Step 3: Verify V87 Deployment
```bash
# Check agent is published
curl -s -X GET "https://api.retellai.com/get-agent/agent_9a8202a740cd3120d96fcfda1e" \
  -H "Authorization: Bearer key_6ff998ba48e842092e04a5455d19" | jq '.is_published, .version'

# Expected output:
# true
# 116
```

### Step 4: Test All 5 Phases

#### Test PHASE 1: Greeting
```
Call agent
Agent: "Willkommen bei Ask Pro AI..."
✅ PASS: Greeting works
```

#### Test PHASE 2: Date Parsing
```
You: "Ich möchte einen Termin für Montag um 13 Uhr"
Agent: "Sehr gerne! Montag, 20. Oktober um 13 Uhr - ist das richtig?"
✅ PASS: Date confirmed
```

#### Test PHASE 3: Availability Check
```
You: "Ja, genau"
Agent: "Leider ist 13:00 Uhr nicht verfügbar. Welche Zeit würde Ihnen passen?"
✅ PASS: Availability rejected correctly
```

#### Test PHASE 2b: Time-Only Update (THE CRITICAL TEST!)
```
You: "Vierzehn Uhr wäre auch ok"
Agent: "Super! 14:00 Uhr am 20. Oktober ist verfügbar."
✅ PASS: Time-only update works WITHOUT asking for date again!

Look for in logs:
- "PHASE 2b activated"
- "check_availability called with confirmed_date and new_time"
- "Agent did NOT call parse_date for time-only input"
```

#### Test PHASE 4: Customer Info
```
Agent: "Auf welchen Namen soll der Termin laufen?"
You: "Max Müller"
Agent: "Unter welcher Nummer können wir Sie erreichen?"
You: "030 1234567"
Agent: "Und Ihre Email?"
You: "max@email.com"
✅ PASS: Customer info collected
```

#### Test PHASE 5: Booking Complete
```
Agent: "Perfekt! Ihr Termin am Montag, 20. Oktober um 14:00 Uhr ist gebucht."
✅ PASS: Appointment created in database
```

---

## 🧪 Error Scenarios to Test

### Error A: Time-only WITHOUT known date
```
You: "14 Uhr" (immediately at start)
Agent: "Welcher Tag passt denn für Sie?"
✅ PASS: Agent recognizes missing date and asks for it
```

### Error B: User changes date
```
Agent: "Montag, 20. Oktober - richtig?"
You: "Nein, lieber Dienstag"
Agent: Calls parse_date("Dienstag") again
✅ PASS: Recognizes new date required
```

### Error C: Frustrated customer repeats
```
Agent: "Leider 13:00 nicht verfügbar. Welche Zeit passt?"
You: "Ich sage doch zwanzigster Oktober vierzehn Uhr!"
Agent: Accepts and books
✅ PASS: Error recovery works
```

### Error D: Multiple time changes
```
You: "14 Uhr"
(check_availability) "Not available"
You: "15 Uhr?"
(check_availability) "Available!"
✅ PASS: Multiple PHASE 2b calls work correctly
```

---

## 📊 Monitoring After Deployment

### Key Metrics to Track

#### Success Metrics (Should INCREASE)
- `booking_success_rate` - Target: >85%
- `average_call_duration` - Target: <5 min/booking
- `phase_2b_activations` - Should be visible in logs
- `customer_satisfaction` - No more "repeat questions about date"

#### Error Metrics (Should DECREASE)
- `parse_date_failure_rate` - Should drop (less called)
- `context_loss_errors` - Should be 0 (was the main problem)
- `customer_frustration_exits` - Should decrease
- `agent_asks_for_known_info` - Should be 0

### Log Patterns to Watch

```bash
# Good logs after V87 deployment:
tail -f storage/logs/laravel.log | grep -E "PHASE 2b|confirmed_date reused|check_availability called directly"

# Expected pattern for time-only update:
# [INFO] PHASE 2b activated
# [INFO] Confirmed date reused: 2025-10-20
# [INFO] check_availability called with confirmed_date and new_time
# [INFO] ✅ EXACT slot match FOUND for 14:00

# BAD pattern (old behavior):
# [INFO] parse_date called with "Vierzehn Uhr"
# [ERROR] Date parsing failed: invalid_date_format
# [INFO] Agent asks customer to provide date again
```

### Performance Metrics

```php
// Monitor in Laravel logs
$metrics = [
    'phase_2b_activations' => 'Number of time-only updates handled',
    'parse_date_calls_skipped' => 'How many parse_date calls avoided',
    'context_preservation_success' => 'Times confirmed date was kept',
    'user_satisfaction_improvement' => 'Fewer repeat questions'
];
```

---

## 🔄 Rollback Plan

If critical issues arise:

### Option 1: Revert to V86
```bash
# Restore previous prompt to LLM
curl -X PATCH "https://api.retellai.com/update-retell-llm/llm_f3209286ed1caf6a75906d2645b9" \
  -H "Authorization: Bearer key_6ff998ba48e842092e04a5455d19" \
  -H "Content-Type: application/json" \
  -d @/tmp/v86_retell_update.json

# Republish with old prompt
curl -X PATCH "https://api.retellai.com/update-agent/agent_9a8202a740cd3120d96fcfda1e" \
  -H "Authorization: Bearer key_6ff998ba48e842092e04a5455d19" \
  -d '{"is_published": true}'
```

### Option 2: Disable PHASE 2b in Backend
If backend has issues with new methods:
```bash
# Temporarily disable parseTimeOnly in DateTimeParser
# Return null or raise error
# Agent will fall back to normal parse_date flow
```

---

## 📝 Checklist Before Going Live

- [ ] V87 Prompt file: `/tmp/v87_retell_prompt_complete.json` verified
- [ ] Backend code: DateTimeParser methods `parseTimeOnly()` and `isTimeOnly()` present
- [ ] Tool definition: `/tmp/v87_parse_date_tool_definition.json` reviewed
- [ ] Agent version: v116 ready to publish
- [ ] Test PHASE 1: Greeting works ✓
- [ ] Test PHASE 2: Date parsing works ✓
- [ ] Test PHASE 3: Availability check works ✓
- [ ] Test PHASE 2b: Time-only update works WITHOUT re-asking date ✓
- [ ] Test PHASE 4: Customer info collection works ✓
- [ ] Test PHASE 5: Booking completes successfully ✓
- [ ] Test Error A: Time-only without date ✓
- [ ] Test Error B: Date change ✓
- [ ] Test Error C: Error recovery ✓
- [ ] Test Error D: Multiple time changes ✓
- [ ] Monitoring dashboard: Logs being captured ✓
- [ ] Rollback procedure: Documented and tested ✓

---

## 🎯 Success Criteria

After V87 deployment, all these should be TRUE:

1. ✅ Agent responds to "14 Uhr" (time-only) WITHOUT asking for date again
2. ✅ Agent correctly rejects "13:00" when not available
3. ✅ Agent correctly accepts "14:00" when available
4. ✅ Anonymous customers create NEW records (not matched)
5. ✅ Booking completes end-to-end
6. ✅ Logs show "PHASE 2b" activations
7. ✅ No "parse_date" errors for time-only updates
8. ✅ No context loss between turns
9. ✅ Call duration <5 min
10. ✅ Booking success rate >85%

---

## 📞 Support

If issues occur:

1. **Check logs**: `tail -f storage/logs/laravel.log | grep -i "phase 2b\|error"`
2. **Test flow**: Call agent and go through all 5 phases
3. **Verify deployment**: `curl get-agent...` and check `is_published: true`
4. **Backend status**: Test `parseTimeOnly()` and `isTimeOnly()` methods exist
5. **Reach out**: Share call transcript + logs if issues persist

---

## Timeline

- **Now**: V87 Prompt & Backend ready
- **T+5 min**: Deploy to Retell
- **T+10 min**: Verify deployment
- **T+15 min**: Test PHASE 1-5
- **T+30 min**: Test error scenarios
- **T+45 min**: Monitor logs for issues
- **T+60 min**: Production ready

**Estimated time to full production: ~1 hour**

---

**Generated**: 2025-10-19
**Status**: ✅ Ready for Immediate Deployment
**Next Step**: Run Step 2 above to deploy V87 to Retell

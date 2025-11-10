# Test Call Logging - Quick Start Guide

**⏱️ Setup Time**: < 5 minutes
**💰 Cost**: Zero performance impact
**📊 Value**: Full real-time visibility into webhook → agent → function → Cal.com data flow

---

## 🚀 Ultra-Quick Start (For Immediate Test Call)

```bash
# 1. Enable logging (2 minutes)
./scripts/enable_testcall_logging.sh

# 2. Start monitoring in new terminal
tail -f storage/logs/laravel.log | grep -E "(WEBHOOK|FUNCTION_CALL|CALCOM_API|ERROR)"

# 3. Make your test call NOW

# 4. Watch data flow in real-time! 📺
```

That's it! You'll see:
- 🔔 WEBHOOK events (call_started, function calls, call_ended)
- 📤 DYNAMIC_VARS sent to agent (current_date, available slots)
- ⚡ FUNCTION_CALL executions (check_availability, book_appointment)
- 🔗 CALCOM_API requests/responses
- ❌ ERROR messages (if any)

---

## 📋 Step-by-Step Setup

### 1. Enable Test Call Logging Mode

```bash
cd /var/www/api-gateway
./scripts/enable_testcall_logging.sh
```

**What it does:**
- Enables `APP_DEBUG=true`
- Clears config cache
- Creates monitoring aliases
- Prepares log file

**Output:**
```
✅ Test Call Logging Enabled!

REAL-TIME MONITORING COMMANDS
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

Load monitoring aliases:
  source /tmp/testcall_monitor_commands.sh

Quick start (no aliases):
  tail -f storage/logs/laravel.log | grep -E '(WEBHOOK|FUNCTION_CALL|CALCOM_API)'

📞 Ready for test call! Watch the logs stream in real-time.
```

### 2. Start Real-Time Monitoring

**Option A: Simple grep (works everywhere)**
```bash
# New terminal window
tail -f storage/logs/laravel.log | grep -E "(WEBHOOK|FUNCTION_CALL|CALCOM_API|ERROR)"
```

**Option B: With aliases (more convenient)**
```bash
# Load aliases
source /tmp/testcall_monitor_commands.sh

# Use convenient commands
monitor-all          # All activity
monitor-webhooks     # Only webhooks
monitor-functions    # Only function calls
monitor-calcom       # Only Cal.com API
monitor-errors       # Only errors
```

**Option C: Monitor specific call (after first webhook)**
```bash
# Get call_id from first webhook, then:
export CALL_ID="call_793088ed9a076628abd3e5c6244"
tail -f storage/logs/laravel.log | grep "$CALL_ID"

# Or with aliases:
source /tmp/testcall_monitor_commands.sh
export CALL_ID="call_793088ed9a076628abd3e5c6244"
monitor-call
```

### 3. Make Test Call

**Call your Retell number** and watch the logs stream!

You'll see data flowing through the system:

```
[2025-11-04 09:41:25] 🔔 WEBHOOK {"event":"call_started","call_id":"call_793088..."}
[2025-11-04 09:41:25] 📤 DYNAMIC_VARS {"current_date":"2025-11-04","verfuegbare_termine_heute":["10:00","14:00"]}
[2025-11-04 09:42:15] ⚡ FUNCTION_CALL {"function":"check_availability","arguments":{"datum":"2025-11-05"},"duration_ms":234.56}
[2025-11-04 09:42:16] 🔗 CALCOM_API {"method":"GET","endpoint":"/slots/available","status_code":200,"duration_ms":187.32}
[2025-11-04 09:43:00] ⚡ FUNCTION_CALL {"function":"book_appointment","duration_ms":456.78}
[2025-11-04 09:43:01] 🔗 CALCOM_API {"method":"POST","endpoint":"/bookings","status_code":201,"duration_ms":312.45}
[2025-11-04 09:45:00] 🔔 WEBHOOK {"event":"call_ended","call_id":"call_793088..."}
```

### 4. Analyze Call After Completion

```bash
# Get your call_id from logs
grep "call_id" storage/logs/laravel.log | grep -oP 'call_[a-f0-9]+' | sort -u | tail -1

# Run analysis script
./scripts/analyze_test_call.sh call_793088ed9a076628abd3e5c6244
```

**Output:**
```
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
  TEST CALL ANALYSIS: call_793088ed9a076628abd3e5c6244
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

📋 1. CALL TIMELINE
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
2025-11-04T09:41:25+01:00 | call_started | WEBHOOK → AGENT
2025-11-04T09:42:15+01:00 | check_availability | AGENT → FUNCTION → AGENT
2025-11-04T09:42:16+01:00 | /slots/available | FUNCTION → CALCOM → FUNCTION
2025-11-04T09:43:00+01:00 | book_appointment | AGENT → FUNCTION → AGENT
2025-11-04T09:43:01+01:00 | /bookings | FUNCTION → CALCOM → FUNCTION
2025-11-04T09:45:00+01:00 | call_ended | WEBHOOK → AGENT

🔔 2. WEBHOOK EVENTS
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
2025-11-04T09:41:25+01:00 | call_started | Payload: 1024 bytes
2025-11-04T09:45:00+01:00 | call_ended | Payload: 2048 bytes

📤 3. DYNAMIC VARIABLES SENT TO AGENT
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
{
  "current_date": "2025-11-04",
  "current_time": "09:41",
  "verfuegbare_termine_heute": ["10:00", "14:00", "16:00"],
  "verfuegbare_termine_morgen": ["09:00", "11:00", "13:00"]
}

⚡ 4. FUNCTION CALLS
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
2025-11-04T09:42:15+01:00 | check_availability | Duration: 234.56ms
2025-11-04T09:43:00+01:00 | book_appointment | Duration: 456.78ms

🔗 5. CAL.COM API CALLS
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
2025-11-04T09:42:16+01:00 | GET /slots/available | Status: 200 | Duration: 187.32ms
2025-11-04T09:43:01+01:00 | POST /bookings | Status: 201 | Duration: 312.45ms

❌ 6. ERRORS
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
✅ No errors found

📊 7. PERFORMANCE METRICS
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
Function Call Durations:
  check_availability: 234.56ms
  book_appointment: 456.78ms

Cal.com API Durations:
  GET /slots/available: 187.32ms
  POST /bookings: 312.45ms
```

### 5. Disable Logging After Test

```bash
./scripts/disable_testcall_logging.sh
```

**What it does:**
- Disables `APP_DEBUG=false`
- Clears config cache
- Removes temporary files
- Preserves log file for analysis

---

## 📊 What You'll See in Real-Time

### 1. Webhook Events (WEBHOOK)
```json
{
  "timestamp": "2025-11-04T09:41:25+01:00",
  "call_id": "call_793088ed9a076628abd3e5c6244",
  "event": "call_started",
  "data_flow": "WEBHOOK → AGENT",
  "payload": { /* full webhook data */ },
  "payload_size": 1024
}
```

### 2. Dynamic Variables (DYNAMIC_VARS)
```json
{
  "timestamp": "2025-11-04T09:41:25+01:00",
  "call_id": "call_793088ed9a076628abd3e5c6244",
  "data_flow": "SYSTEM → AGENT",
  "variables": {
    "current_date": "2025-11-04",
    "current_time": "09:41",
    "verfuegbare_termine_heute": ["10:00", "14:00"],
    "verfuegbare_termine_morgen": ["09:00", "11:00"],
    "naechster_freier_termin": "2025-11-04T10:00:00+01:00"
  }
}
```

### 3. Function Calls (FUNCTION_CALL)
```json
{
  "timestamp": "2025-11-04T09:42:15+01:00",
  "call_id": "call_793088ed9a076628abd3e5c6244",
  "function": "check_availability",
  "data_flow": "AGENT → FUNCTION → AGENT",
  "arguments": {
    "datum": "2025-11-05",
    "uhrzeit": "14:00",
    "service_name": "Haarschnitt"
  },
  "response": {
    "success": true,
    "available": true,
    "slots": ["14:00", "14:30", "15:00"]
  },
  "duration_ms": 234.56
}
```

### 4. Cal.com API Calls (CALCOM_API)
```json
{
  "timestamp": "2025-11-04T09:42:16+01:00",
  "call_id": "call_793088ed9a076628abd3e5c6244",
  "method": "GET",
  "endpoint": "/slots/available",
  "data_flow": "FUNCTION → CALCOM → FUNCTION",
  "request": {
    "eventTypeId": 2563193,
    "startTime": "2025-11-05",
    "endTime": "2025-11-05"
  },
  "response": {
    "data": {
      "slots": {
        "2025-11-05": [
          {"time": "2025-11-05T14:00:00Z"},
          {"time": "2025-11-05T14:30:00Z"}
        ]
      }
    }
  },
  "status_code": 200,
  "duration_ms": 187.32
}
```

### 5. Errors (ERROR)
```json
{
  "timestamp": "2025-11-04T09:43:00+01:00",
  "call_id": "call_793088ed9a076628abd3e5c6244",
  "context": "function_execution",
  "error_message": "Service not available for this branch",
  "error_class": "App\\Exceptions\\ServiceNotFoundException",
  "file": "/var/www/api-gateway/app/Http/Controllers/RetellFunctionCallHandler.php",
  "line": 773,
  "stack_trace": "...",
  "additional_data": {
    "function": "check_availability",
    "parameters": {...}
  }
}
```

---

## 🎯 Common Debugging Scenarios

### Scenario 1: Agent not receiving dynamic variables
**Symptom**: Agent asks for date/time when you already sent it

**Debug:**
```bash
tail -f storage/logs/laravel.log | grep "DYNAMIC_VARS"
```

**Look for:**
- Are variables being sent?
- Are values correct (current_date, current_time)?
- Is call_id matching?

### Scenario 2: Function calls failing silently
**Symptom**: Agent says "appointment booked" but nothing created

**Debug:**
```bash
tail -f storage/logs/laravel.log | grep "FUNCTION_CALL" | grep "book_appointment"
```

**Look for:**
- Function arguments received
- Response success/error
- Duration (timeout if > 5000ms)

### Scenario 3: Cal.com API errors
**Symptom**: "No available slots" when slots exist

**Debug:**
```bash
tail -f storage/logs/laravel.log | grep "CALCOM_API"
```

**Look for:**
- Request parameters (eventTypeId, startTime, endTime)
- Response status_code (200 = success, 400 = bad request, 500 = server error)
- Response body error messages

### Scenario 4: Empty call_id issues
**Symptom**: "Call context not available" errors

**Debug:**
```bash
tail -f storage/logs/laravel.log | grep "CANONICAL_CALL_ID"
```

**Look for:**
- Webhook vs args mismatch
- Empty string warnings
- Fallback attempts

---

## 🔍 Advanced Filtering

### Filter by specific function
```bash
tail -f storage/logs/laravel.log | grep "check_availability"
tail -f storage/logs/laravel.log | grep "book_appointment"
```

### Filter by time range
```bash
# Last 5 minutes
grep "2025-11-04 09:4" storage/logs/laravel.log | grep FUNCTION_CALL

# Between specific times
grep -E "2025-11-04 09:(41|42|43)" storage/logs/laravel.log
```

### Extract specific data points
```bash
# All function names called
grep FUNCTION_CALL storage/logs/laravel.log | grep -oP '"function":"[^"]+"' | sort | uniq -c

# All Cal.com endpoints hit
grep CALCOM_API storage/logs/laravel.log | grep -oP '"endpoint":"[^"]+"' | sort | uniq -c

# All error messages
grep ERROR storage/logs/laravel.log | grep -oP '"error_message":"[^"]+"'
```

### Export for external analysis
```bash
# Export call to JSON file
grep "call_793088ed" storage/logs/laravel.log > testcall_export.log

# Parse with jq
cat testcall_export.log | sed 's/.*FUNCTION_CALL //' | jq '.'
```

---

## 🛠️ Troubleshooting

### Logs not appearing?

1. **Check permissions:**
   ```bash
   ls -la storage/logs/laravel.log
   chmod 664 storage/logs/laravel.log
   ```

2. **Check debug mode:**
   ```bash
   grep APP_DEBUG .env
   php artisan config:clear
   ```

3. **Check if TestCallLogger loaded:**
   ```bash
   php artisan tinker
   >>> class_exists('App\Helpers\TestCallLogger');
   >>> exit
   ```

### Too much output?

1. **Filter by specific log type:**
   ```bash
   tail -f storage/logs/laravel.log | grep "FUNCTION_CALL"
   ```

2. **Use call_id filtering:**
   ```bash
   export CALL_ID="call_xxx"
   tail -f storage/logs/laravel.log | grep "$CALL_ID"
   ```

3. **Disable after test:**
   ```bash
   ./scripts/disable_testcall_logging.sh
   ```

### Want JSON formatting?

```bash
# Install jq
sudo apt-get install jq

# Pipe logs through jq
tail -f storage/logs/laravel.log | grep "FUNCTION_CALL" | sed 's/.*FUNCTION_CALL //' | jq '.'
```

---

## 📝 Next Steps

After your test call, you can:

1. **Share logs with team:**
   ```bash
   grep "call_xxx" storage/logs/laravel.log > testcall_$(date +%Y%m%d_%H%M).log
   ```

2. **Identify bottlenecks:**
   ```bash
   ./scripts/analyze_test_call.sh call_xxx | grep "Performance Metrics"
   ```

3. **Debug specific failures:**
   ```bash
   grep "call_xxx" storage/logs/laravel.log | grep ERROR
   ```

4. **Disable logging:**
   ```bash
   ./scripts/disable_testcall_logging.sh
   ```

---

## 🎓 Understanding Data Flow

```
┌─────────────────────────────────────────────────────────────────┐
│                         TEST CALL FLOW                          │
└─────────────────────────────────────────────────────────────────┘

1. CALL STARTS
   Retell → Webhook (call_started) → Laravel
   📋 Log: 🔔 WEBHOOK

2. DYNAMIC VARIABLES SENT
   Laravel → Retell Agent (current_date, available_slots)
   📋 Log: 📤 DYNAMIC_VARS

3. AGENT PROCESSES REQUEST
   Agent analyzes user speech, decides to check availability

4. FUNCTION CALL
   Agent → Function (check_availability)
   📋 Log: ⚡ FUNCTION_CALL (arguments)

5. CAL.COM API CALL
   Function → Cal.com API (GET /slots/available)
   📋 Log: 🔗 CALCOM_API (request)

6. CAL.COM RESPONSE
   Cal.com → Function (available slots)
   📋 Log: 🔗 CALCOM_API (response)

7. FUNCTION RESPONSE
   Function → Agent (availability results)
   📋 Log: ⚡ FUNCTION_CALL (response)

8. AGENT RESPONDS
   Agent speaks to user: "We have slots at 10:00, 14:00, 16:00"

9. BOOKING (if user confirms)
   Repeat steps 4-7 with book_appointment function
   📋 Logs: ⚡ FUNCTION_CALL → 🔗 CALCOM_API (POST /bookings)

10. CALL ENDS
    Retell → Webhook (call_ended) → Laravel
    📋 Log: 🔔 WEBHOOK
```

---

## 📞 Support

If you need help:

1. **Check this guide first**
2. **Run analysis script:** `./scripts/analyze_test_call.sh call_xxx`
3. **Export logs:** `grep "call_xxx" storage/logs/laravel.log > issue.log`
4. **Share with team** with specific questions

**Ready to test?** Run: `./scripts/enable_testcall_logging.sh` 🚀

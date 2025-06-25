# Comprehensive Phone Test Plan for AskProAI
## Phone Call to Appointment Booking Flow

### Executive Summary
This test plan ensures the complete flow from incoming phone call to successful appointment booking works correctly. It covers pre-test validation, test scenarios, data verification, and success criteria.

## 1. Pre-Test Validation Checklist

### A. Phone Number Configuration
```bash
# Check phone number to branch mapping
php artisan tinker --execute="
\$phoneNumber = PhoneNumber::where('number', '+49 30 837 93 369')->first();
if (\$phoneNumber) {
    echo 'Phone: ' . \$phoneNumber->number . PHP_EOL;
    echo 'Branch ID: ' . \$phoneNumber->branch_id . PHP_EOL;
    echo 'Active: ' . (\$phoneNumber->is_active ? 'Yes' : 'No') . PHP_EOL;
    echo 'Retell Agent ID: ' . \$phoneNumber->retell_agent_id . PHP_EOL;
} else {
    echo 'Phone number not configured!' . PHP_EOL;
}
"
```

### B. Branch Configuration
```bash
# Verify branch has Cal.com event type
php artisan tinker --execute="
\$branch = Branch::find('14b9996c-4ebe-11f0-b9c1-0ad77e7a9793');
if (\$branch) {
    echo 'Branch: ' . \$branch->name . PHP_EOL;
    echo 'Active: ' . (\$branch->is_active ? 'Yes' : 'No') . PHP_EOL;
    echo 'Cal.com Event Type ID: ' . \$branch->calcom_event_type_id . PHP_EOL;
    echo 'Company ID: ' . \$branch->company_id . PHP_EOL;
} else {
    echo 'Branch not found!' . PHP_EOL;
}
"
```

### C. Retell Agent Configuration
```bash
# Check Retell agent has correct functions
php artisan tinker --execute="
\$agent = DB::table('retell_agents')->where('agent_id', 'agent_9a8202a740cd3120d96fcfda1e')->first();
if (\$agent) {
    \$config = json_decode(\$agent->configuration, true);
    echo 'Agent: ' . \$agent->name . PHP_EOL;
    echo 'Active: ' . (\$agent->is_active ? 'Yes' : 'No') . PHP_EOL;
    echo 'Webhook URL: ' . (\$config['webhook_url'] ?? 'NOT SET') . PHP_EOL;
    echo 'Language: ' . (\$config['language'] ?? 'NOT SET') . PHP_EOL;
    
    // Check for collect_appointment_data function
    \$hasCollectFunction = false;
    if (isset(\$config['llm_configuration']['general_tools'])) {
        foreach (\$config['llm_configuration']['general_tools'] as \$tool) {
            if (\$tool['name'] === 'collect_appointment_data') {
                \$hasCollectFunction = true;
                break;
            }
        }
    }
    echo 'Has collect_appointment_data: ' . (\$hasCollectFunction ? 'Yes' : 'No') . PHP_EOL;
} else {
    echo 'Agent not found!' . PHP_EOL;
}
"
```

### D. Service Status Check
```bash
# Verify all services are running
php artisan horizon:status
php artisan queue:monitor webhooks default
systemctl status redis
systemctl status mysql

# Check webhook endpoints
curl -X GET https://api.askproai.de/api/retell/collect-appointment/test
curl -X GET https://api.askproai.de/api/zeitinfo?locale=de
```

### E. Cal.com Integration
```bash
# Test Cal.com API connection
php artisan tinker --execute="
use App\Services\CalcomV2Service;
\$service = new CalcomV2Service();
\$user = \$service->getUser();
if (\$user) {
    echo 'Cal.com connected: ' . \$user['username'] . PHP_EOL;
} else {
    echo 'Cal.com connection FAILED!' . PHP_EOL;
}
"
```

## 2. Test Scenarios

### Scenario 1: Basic Appointment Booking
**Setup:**
- Phone number: +49 30 837 93 369
- Test caller: Any German phone number
- Agent: agent_9a8202a740cd3120d96fcfda1e

**Test Steps:**
1. Call the configured phone number
2. Wait for AI agent greeting
3. Say: "Ich möchte einen Termin buchen"
4. When asked for details, provide:
   - Name: "Max Mustermann"
   - Service: "Beratungsgespräch"
   - Date: "morgen" (tomorrow)
   - Time: "15:00 Uhr"
   - Email: "max@example.com" (when asked)
5. Confirm the appointment when agent repeats details
6. End the call

**Expected Results:**
- Agent greets with appropriate time-based greeting
- Agent collects all required information
- Agent confirms appointment details
- Call ends successfully

### Scenario 2: Phone Number Unknown
**Setup:**
- Use a phone with hidden/suppressed number
- Call the same number

**Test Steps:**
1. Call with suppressed number
2. Request appointment
3. When agent asks for phone number, provide: "+49 30 12345678"
4. Complete booking as in Scenario 1

**Expected Results:**
- Agent recognizes unknown number
- Agent asks for phone number
- Booking proceeds normally after number provided

### Scenario 3: No Availability
**Setup:**
- Request a time that's already booked or outside hours

**Test Steps:**
1. Call and request appointment
2. Ask for "heute um 22:00 Uhr" (today at 10 PM)
3. Listen to agent's response

**Expected Results:**
- Agent politely explains unavailability
- Agent suggests alternative times
- No appointment created

### Scenario 4: Customer Preferences
**Setup:**
- Test time preferences handling

**Test Steps:**
1. Call and request appointment
2. Say: "Ich kann nur vormittags" (I can only come in the morning)
3. Let agent suggest morning times
4. Accept a suggestion

**Expected Results:**
- Agent acknowledges preference
- Only suggests morning slots
- Preference stored in appointment data

### Scenario 5: Email Opt-Out
**Setup:**
- Test email confirmation handling

**Test Steps:**
1. Call and request appointment
2. When asked about email confirmation, say "Nein danke"
3. Complete booking

**Expected Results:**
- Agent accepts no email preference
- Confirms appointment verbally only
- No email field in appointment data

## 3. Data Flow Verification

### A. During Call
Monitor logs in real-time:
```bash
# Terminal 1: Watch webhook logs
tail -f storage/logs/laravel.log | grep -E "RETELL|WEBHOOK|collect_appointment"

# Terminal 2: Watch queue processing
php artisan horizon

# Terminal 3: Monitor cache
php artisan tinker --execute="
while(true) {
    \$keys = Cache::getRedis()->keys('retell_appointment_data:*');
    foreach(\$keys as \$key) {
        echo \$key . ': ' . json_encode(Cache::get(str_replace('laravel_database_', '', \$key))) . PHP_EOL;
    }
    sleep(2);
    system('clear');
}
"
```

### B. After Call Ends
Check webhook processing:
```sql
-- Recent webhook events
SELECT * FROM webhook_events 
WHERE provider = 'retell' 
ORDER BY created_at DESC 
LIMIT 5;

-- Check calls table
SELECT * FROM calls 
WHERE retell_call_id LIKE '%' 
ORDER BY created_at DESC 
LIMIT 1;

-- Check appointments
SELECT * FROM appointments 
WHERE source = 'phone' 
ORDER BY created_at DESC 
LIMIT 1;
```

### C. Verify Cal.com Booking
```bash
# Check Cal.com booking was created
php artisan tinker --execute="
\$appointment = Appointment::latest()->first();
if (\$appointment && \$appointment->calcom_booking_id) {
    echo 'Cal.com Booking ID: ' . \$appointment->calcom_booking_id . PHP_EOL;
    
    // Fetch from Cal.com
    \$service = new \App\Services\CalcomV2Service();
    \$booking = \$service->getBookingById(\$appointment->calcom_booking_id);
    echo 'Cal.com Status: ' . (\$booking ? 'Found' : 'Not Found') . PHP_EOL;
}
"
```

## 4. Success Criteria

### Call Handling
- [ ] AI agent answers within 3 seconds
- [ ] Correct greeting based on time of day
- [ ] Natural conversation flow
- [ ] All required data collected
- [ ] Clear appointment confirmation

### Data Processing
- [ ] Webhook received and logged
- [ ] Call record created in database
- [ ] Customer created/matched correctly
- [ ] Appointment created with all fields
- [ ] Cal.com booking created

### Notifications
- [ ] Email sent (if requested)
- [ ] SMS queued (if configured)
- [ ] Admin notified (if configured)

## 5. Troubleshooting Guide

### Problem: "Time slot no longer available"
**Check:**
```sql
-- Look for stuck locks
SELECT * FROM appointment_locks 
WHERE expires_at < NOW() 
AND branch_id = 'YOUR-BRANCH-ID';

-- Clear expired locks
DELETE FROM appointment_locks WHERE expires_at < NOW();
```

### Problem: Webhook not processing
**Check:**
1. Horizon is running: `php artisan horizon:status`
2. Redis is accessible: `redis-cli ping`
3. Webhook signature: Check logs for signature errors
4. Queue backlog: `php artisan queue:monitor`

### Problem: Cal.com sync failed
**Check:**
```bash
# Check circuit breaker
php artisan tinker --execute="
\$cb = new \App\Services\CircuitBreaker\CircuitBreaker();
echo 'Cal.com Circuit: ' . \$cb->getState('calcom') . PHP_EOL;
"

# Reset if needed
php artisan circuit-breaker:reset calcom
```

### Problem: No appointment created
**Check:**
1. Phone number resolver found branch
2. Branch has Cal.com event type
3. collect_appointment_data was called
4. Cache contains appointment data
5. ProcessRetellCallEndedJob was executed

## 6. Performance Metrics

### Expected Performance:
- Webhook processing: < 500ms
- Appointment creation: < 2s
- Cal.com sync: < 3s
- Total end-to-end: < 5s after call ends

### Monitor with:
```sql
SELECT 
    service,
    AVG(duration_ms) as avg_ms,
    MAX(duration_ms) as max_ms,
    COUNT(*) as total_calls
FROM api_call_logs
WHERE created_at >= NOW() - INTERVAL 1 HOUR
GROUP BY service;
```

## 7. Test Execution Log Template

```markdown
### Test Run: [DATE TIME]
**Tester:** [Name]
**Environment:** [Production/Staging]

#### Pre-Test Validation
- [ ] Phone number configured: ________
- [ ] Branch active: ________
- [ ] Agent active: ________
- [ ] Services running: ________
- [ ] Cal.com connected: ________

#### Test Results
| Scenario | Status | Notes |
|----------|--------|-------|
| Basic Booking | ✅/❌ | |
| Unknown Number | ✅/❌ | |
| No Availability | ✅/❌ | |
| Preferences | ✅/❌ | |
| Email Opt-Out | ✅/❌ | |

#### Issues Found
1. 
2. 

#### Follow-Up Actions
1. 
2. 
```

## 8. Automated Test Script

Save as `test-phone-booking.sh`:
```bash
#!/bin/bash

echo "=== AskProAI Phone Booking Test ==="
echo "Starting at: $(date)"

# Pre-flight checks
echo -e "\n1. Checking services..."
systemctl is-active redis || echo "WARNING: Redis not running"
php artisan horizon:status || echo "WARNING: Horizon not running"

echo -e "\n2. Checking phone configuration..."
php artisan tinker --execute="
\$phone = PhoneNumber::where('number', '+49 30 837 93 369')->first();
echo \$phone ? 'Phone configured' : 'ERROR: Phone not configured';
"

echo -e "\n3. Testing webhook endpoint..."
curl -s -X GET https://api.askproai.de/api/retell/collect-appointment/test | jq .

echo -e "\n4. Monitoring logs..."
echo "Tail the logs in another terminal:"
echo "tail -f storage/logs/laravel.log | grep RETELL"

echo -e "\nReady for phone test. Call: +49 30 837 93 369"
```

## 9. Post-Test Cleanup

```bash
# Clear test data (if needed)
php artisan tinker --execute="
// Delete test appointments
Appointment::where('customer_name', 'LIKE', '%Test%')->delete();

// Clear test calls
Call::where('from_number', '+491234567890')->delete();

// Clear cache
Cache::flush();
"
```

## 10. Success Confirmation

After successful test:
1. Verify appointment in admin panel: `/admin/appointments`
2. Check customer was created: `/admin/customers`
3. Confirm Cal.com booking exists
4. Review call transcript in admin
5. Document any issues or improvements needed

---

**Remember:** Always test in a controlled environment first. Use test phone numbers and test Cal.com calendars when possible.
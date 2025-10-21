# EMERGENCY FIX ACTION PLAN
**Issue**: Agent V117 goes silent after greeting (37-second call freeze)
**Action Required**: Immediate deployment
**Estimated Time**: 5-10 minutes
**Risk Level**: Low (rollback to known working version)

---

## QUICKSTART FIX

### Step 1: Identify Current Agent Version
```bash
cd /var/www/api-gateway
php artisan tinker
>>> DB::table('retell_agents')->where('agent_id', 'agent_9a8202a740cd3120d96fcfda1e')->first();
```

Expected output shows:
- `version: 117`
- `configuration: {...}` with V88 prompt

### Step 2: Check Available Versions
```bash
# See what versions we have
php artisan tinker
>>> DB::table('retell_agents')->where('agent_id', 'agent_9a8202a740cd3120d96fcfda1e')->get(['version', 'sync_status', 'last_synced_at']);
```

### Step 3: Rollback to V115 (WORKING VERSION)
```bash
# Option A: Direct database update
php artisan tinker
>>> $agent = DB::table('retell_agents')->where('agent_id', 'agent_9a8202a740cd3120d96fcfda1e')->first();
>>> $config = json_decode($agent->configuration, true);
>>> $config['prompt'] = '[V115 WORKING PROMPT - GET FROM GIT]';
>>> DB::table('retell_agents')->where('id', $agent->id)->update(['configuration' => json_encode($config), 'version' => 115, 'sync_status' => 'pending']);
```

### Step 4: Sync to Retell API
```bash
php artisan retell:update-agent agent_9a8202a740cd3120d96fcfda1e
```

Or manually:
```bash
php /var/www/api-gateway/scripts/update_retell_agent_prompt.php
```

### Step 5: Verify Deployment
```bash
# Test call - should now work
# Expected: Agent greets, user speaks, agent responds with availability check
curl -X POST https://api.retellai.com/test-call \
  -H "Authorization: Bearer $RETELL_API_KEY" \
  -H "Content-Type: application/json" \
  -d '{"agent_id":"agent_9a8202a740cd3120d96fcfda1e"}'
```

### Step 6: Confirm Fix in Production
```bash
# Make actual test call to German number
# Expected: Multi-turn conversation works, no silence
```

---

## IF ROLLBACK DOESN'T WORK

### Check if Retell Agent Updated
```bash
# Verify agent_version in Retell changed
curl -X GET https://api.retellai.com/get-agent/agent_9a8202a740cd3120d96fcfda1e \
  -H "Authorization: Bearer $RETELL_API_KEY"
```

### Check Sync Status in Our Database
```bash
php artisan tinker
>>> DB::table('retell_agents')->where('agent_id', 'agent_9a8202a740cd3120d96fcfda1e')->select(['version', 'sync_status', 'is_published', 'last_synced_at'])->first();
```

Expected:
- sync_status: "synced"
- is_published: 1
- last_synced_at: recent timestamp

### Manual Retell API Update
```bash
AGENT_ID="agent_9a8202a740cd3120d96fcfda1e"
API_KEY="[YOUR_RETELL_API_KEY]"

curl -X PATCH "https://api.retellai.com/update-agent/${AGENT_ID}" \
  -H "Authorization: Bearer ${API_KEY}" \
  -H "Content-Type: application/json" \
  -d '{
    "agent_name": "Online: Assistent für Fabian Spitzer Rechtliches/V33",
    "llm_config": {
      "model": "gemini-2.5-flash",
      "system_prompt": "[PASTE V115 PROMPT HERE]",
      "initial_message": "Guten Tag! Wie kann ich Ihnen helfen?",
      "temperature": 0.7
    },
    "language": "de-DE"
  }'
```

---

## ROOT CAUSE ANALYSIS

V88 prompt has a syntax error that prevents second-turn responses:

**Evidence**:
- Only 1 LLM request in entire 37-second call
- Greeting works (plays successfully)
- User input: silence follows
- No function calls made
- No error in logs (agent never reached error handler)

**Likely Issue in V88**:
1. Malformed JSON in prompt
2. Invalid function call syntax
3. Unclosed bracket or quote
4. Circular logic that blocks processing

**V115 Status**: Known to work for 2+ turns (even if slot filtering buggy)

---

## MONITORING AFTER FIX

### Check Call Metrics
```bash
# After rollback, verify calls improve
SELECT
  DATE(created_at) as call_date,
  COUNT(*) as total_calls,
  SUM(CASE WHEN call_successful = true THEN 1 ELSE 0 END) as successful,
  AVG(call_time) as avg_duration,
  COUNT(DISTINCT CASE WHEN appointment_made = true THEN 1 END) as appointments_created
FROM calls
WHERE created_at > NOW() - INTERVAL 1 HOUR
  AND agent_id = 'agent_9a8202a740cd3120d96fcfda1e'
GROUP BY call_date;
```

### Alert Conditions
Set up alerts for:
- LLM token requests = 1 (means agent only greeting, no second turn)
- Call time < 40 seconds (likely premature hangup)
- call_successful = false (conversation didn't complete)

---

## COMPREHENSIVE V88 FIX (LATER)

Once rollback confirms V115 works:

### 1. Get V88 Prompt
```bash
php artisan tinker
>>> $agent = DB::table('retell_agents')->where('version', 88)->first();
>>> $config = json_decode($agent->configuration, true);
>>> dd($config['prompt']);
```

### 2. Validate JSON Syntax
```bash
# Save to file
echo '[PASTE V88 PROMPT]' > /tmp/v88_prompt.json
php -r "json_decode(file_get_contents('/tmp/v88_prompt.json'), true) ?: die('INVALID JSON');"
```

### 3. Compare with V115
```bash
# Side-by-side comparison
diff <(echo "[V115 PROMPT]" | jq .) <(echo "[V88 PROMPT]" | jq .)
```

### 4. Look for Common Errors
- Missing closing brackets: `]` or `}`
- Unescaped quotes: `"` inside strings
- Invalid field names in function definitions
- Circular conditions that loop indefinitely

### 5. Create V89 with Fix
- Fix the identified error
- Update database: version = 89
- Update Retell: sync_status = pending
- Run sync script
- Test with staging call first

---

## CONTACT POINTS

If fix doesn't work, escalate to:
1. **Retell Support**: Agent stopped responding after greeting
2. **Check their webhook logs**: Did they receive second-turn request from agent?
3. **Review agent config**: Did our update sync properly?

---

## SUCCESS CRITERIA

After fix, verify:
- [ ] Next test call has 5+ LLM requests (multi-turn)
- [ ] Agent responds to user input (no silence)
- [ ] Appointment created successfully
- [ ] Call time > 60 seconds (natural conversation)
- [ ] No new calls with 1 LLM request only

---

**Status**: READY FOR IMMEDIATE DEPLOYMENT
**Time Estimate**: 5 minutes
**Rollback Risk**: Very Low (reverting to known working version)
**Production Impact**: CRITICAL (agent is broken without fix)

---

**Created**: 2025-10-19 22:15
**Urgency**: EMERGENCY - Deploy immediately
**Next Review**: After first test call post-deployment

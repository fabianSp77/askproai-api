# 🚨 QUICK START: Incident Recovery (< 5 Minutes)

## THE PROBLEM
Agent plays greeting → User speaks → **SILENCE** → User hangs up

## THE FIX (3 STEPS)

### Step 1: Run Restoration Script (2 minutes)
```bash
cd /var/www/api-gateway
bash EMERGENCY_RESTORATION_SCRIPT_2025_10_19.sh
```

### Step 2: Verify Fix (1 minute)
```bash
bash VERIFY_AGENT_RESTORATION_2025_10_19.sh
```

### Step 3: Test Call (2 minutes)
- Call: **+493083793369**
- Say: "Ich möchte einen Termin am Montag um 14 Uhr"
- Expected: **Agent RESPONDS** (not silence)

## SUCCESS INDICATORS
- ✅ Script completes without errors
- ✅ Verification shows "AGENT HEALTHY"
- ✅ Test call: Agent responds after greeting
- ✅ LLM requests ≥ 3 (not just 1)

## IF IT FAILS
Escalate to: support@retellai.com
Include: "Agent V117 freeze - silence after greeting"

## FULL DOCUMENTATION
See: INCIDENT_EXECUTIVE_SUMMARY_2025_10_19.md

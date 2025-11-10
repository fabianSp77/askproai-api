# Flow V31 - Quick Test Guide

## 🚀 Ready to Test Alternative Selection

**Version**: V31
**Status**: ✅ Deployed
**Feature**: Alternative appointment time selection now triggers booking

---

## Quick Test Commands

### 1. Enable Logging
```bash
./scripts/enable_testcall_logging.sh
```

### 2. Make Test Call
**Phone**: +49 30 12345678 (Friseur1)

### 3. Test Scenario
```
Agent: "Ich habe folgende Zeiten gefunden: 06:55, 14:30, oder 19:00"
You: "Um 06:55 bitte"
Expected: Agent books appointment with time 06:55
```

### 4. Verify Execution
```bash
# Find latest call ID
ls -lt storage/logs/testcall_*.log | head -1

# Check if booking was executed
grep -i "book_appointment" storage/logs/testcall_call_*.log

# Should show: Function call with uhrzeit="06:55"
```

### 5. Check Database
```bash
php artisan tinker
>>> $appt = \App\Models\Appointment::latest()->first()
>>> echo $appt->appointment_time
# Should show: 06:55:00
>>> echo $appt->appointment_date
# Should show: 2025-11-0X (selected date)
```

### 6. Disable Logging
```bash
./scripts/disable_testcall_logging.sh
```

---

## What Changed?

**Before**: "Um 06:55" → Agent says "reserviert" but NO booking
**After**: "Um 06:55" → Agent books with correct time ✅

---

## Expected Log Output

```json
{
  "function_name": "book_appointment",
  "parameters": {
    "name": "Max Mustermann",
    "datum": "2025-11-05",
    "dienstleistung": "Herrenhaarschnitt",
    "uhrzeit": "06:55"  ← Should match selected alternative
  }
}
```

---

## Troubleshooting

**No booking executed?**
```bash
# Check flow version
curl -s "https://api.retellai.com/get-agent/agent_45daa54928c5768b52ba3db736" \
  -H "Authorization: Bearer key_6ff998ba48e842092e04a5455d19" | \
  python3 -c "import json, sys; d=json.load(sys.stdin); print(f'Version: {d[\"version\"]}')"
# Should show: Version: 31
```

**Wrong time in booking?**
```bash
# Verify parameter mapping
curl -s "https://api.retellai.com/get-conversation-flow/conversation_flow_a58405e3f67a" \
  -H "Authorization: Bearer key_6ff998ba48e842092e04a5455d19" | \
  python3 -c "import json, sys; d=json.load(sys.stdin); \
  book=[n for n in d['nodes'] if n['id']=='func_book_appointment'][0]; \
  print(book['parameter_mapping']['uhrzeit'])"
# Should show: {{selected_alternative_time || appointment_time}}
```

---

## Success Criteria

- ✅ User selects alternative time
- ✅ Agent confirms booking
- ✅ book_appointment function is called
- ✅ Database entry has correct time
- ✅ No hallucinations about "reserviert"

---

**Need Help?** See `FLOW_V31_DEPLOYMENT_COMPLETE.md` for full documentation.

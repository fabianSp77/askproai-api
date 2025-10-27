# Retell AI Update Process - Quick Reference

**Last Updated:** 2025-10-24

---

## 🚀 Complete Update Workflow (3 Steps + Verify)

### ✅ Step 1: Update Conversation Flow

**Purpose:** Modify nodes, edges, functions in Flow Canvas

```bash
cd /var/www/api-gateway

# Get current flow
php -r "
require 'vendor/autoload.php';
\$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
\$dotenv->load();
\$ch = curl_init();
curl_setopt(\$ch, CURLOPT_URL, 'https://api.retellai.com/get-conversation-flow/conversation_flow_1607b81c8f93');
curl_setopt(\$ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt(\$ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . (\$_ENV['RETELLAI_API_KEY'] ?? \$_ENV['RETELL_TOKEN'])]);
\$flow = json_decode(curl_exec(\$ch), true);
file_put_contents('current_flow.json', json_encode(\$flow, JSON_PRETTY_PRINT));
echo \"✅ Flow saved to current_flow.json (\", count(\$flow['nodes']), \" nodes, version \", \$flow['version'], \")\n\";
"

# Edit current_flow.json with your changes

# Update flow
php -r "
require 'vendor/autoload.php';
\$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
\$dotenv->load();
\$flow = json_decode(file_get_contents('current_flow.json'), true);
\$ch = curl_init();
curl_setopt(\$ch, CURLOPT_URL, 'https://api.retellai.com/update-conversation-flow/conversation_flow_1607b81c8f93');
curl_setopt(\$ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt(\$ch, CURLOPT_CUSTOMREQUEST, 'PATCH');
curl_setopt(\$ch, CURLOPT_POSTFIELDS, json_encode(['nodes' => \$flow['nodes']]));
curl_setopt(\$ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . (\$_ENV['RETELLAI_API_KEY'] ?? \$_ENV['RETELL_TOKEN']), 'Content-Type: application/json']);
\$updated = json_decode(curl_exec(\$ch), true);
echo \"✅ Flow updated to version \", \$updated['version'], \"\n\";
"
```

**Or use automation script:**
```bash
php update_v39_flow_automatically.php
```

---

### ✅ Step 2: Publish Agent

**Purpose:** Create new published version from draft

```bash
php -r "
require 'vendor/autoload.php';
\$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
\$dotenv->load();
\$ch = curl_init();
curl_setopt(\$ch, CURLOPT_URL, 'https://api.retellai.com/publish-agent/agent_f1ce85d06a84afb989dfbb16a9');
curl_setopt(\$ch, CURLOPT_POST, true);
curl_setopt(\$ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt(\$ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . (\$_ENV['RETELLAI_API_KEY'] ?? \$_ENV['RETELL_TOKEN'])]);
curl_exec(\$ch);
echo \"✅ Agent published\n\";
"
```

**Or use script:**
```bash
php publish_agent_v39_correct.php
```

**Verify published versions:**
```bash
php list_agent_versions.php | head -20
```

---

### ✅ Step 3: Update Phone Number Version

**⚠️ CRITICAL:** Phone numbers do NOT automatically update to new versions!

```bash
# Check current version
php list_phone_numbers.php | grep -A 6 "+493033081738"

# Update to latest version (e.g., 42)
php update_phone_number_version.php +493033081738 42
```

**Expected output:**
```
✅ Phone number updated successfully!

📊 Updated Details:
   Phone Number: +493033081738
   Inbound Agent Version: 42
   Last Modified: 2025-10-24 10:39:30 (Berlin)

🎉 SUCCESS!
```

---

### ✅ Step 4: Comprehensive Verification

```bash
# Verify flow changes
php verify_v39_fix.php

# Verify agent versions
php list_agent_versions.php | head -10

# Verify phone number binding
php list_phone_numbers.php | grep -A 6 "+493033081738"

# Expected:
#   Inbound Agent Version: 42  ← Should match latest published version
```

---

## 🧪 Testing

### Wait Period
⏳ **Wait 60 seconds** after phone number update for deployment propagation

### Test Call
```
1. Call: +493033081738
2. Say: "Termin heute 16 Uhr für Herrenhaarschnitt"
3. Expected:
   - Agent says: "Einen Moment bitte, ich prüfe..."
   - 2-3 second pause (function executing)
   - Agent gives CORRECT availability (no hallucination)
```

### Verify in Admin Panel
- **URL:** https://api.askproai.de/admin/retell-call-sessions
- **Check:** RetellFunctionTrace should show `check_availability` with status `success`

### Check Logs
```bash
tail -50 storage/logs/laravel.log | grep check_availability
```

---

## 📋 Quick Commands

```bash
# List all agent versions
php list_agent_versions.php

# List phone numbers
php list_phone_numbers.php

# Update phone number version
php update_phone_number_version.php <phone> <version>

# Verify flow structure
php inspect_flow_structure.php

# Complete verification
php verify_v39_fix.php

# Debug phone numbers
php debug_phone_numbers.php
```

---

## 🔧 Common Scenarios

### Add New Function Call

**Example:** Add check_availability function

```bash
# 1. Create/update script that modifies flow
#    Add Function Node + Edges

# 2. Run update
php update_v39_flow_automatically.php

# 3. Publish
php publish_agent_v39_correct.php

# 4. Update phone number (get latest version from list_agent_versions.php)
php update_phone_number_version.php +493033081738 42

# 5. Verify
php verify_v39_fix.php

# 6. Test
# Call +493033081738 and verify behavior
```

### Modify Prompt

```bash
# 1. Get flow
php -r "..." # See Step 1 above

# 2. Edit current_flow.json
#    Find node, modify prompt.text

# 3. Update flow
php -r "..." # See Step 1 above

# 4. Publish + Update Phone Number
php publish_agent_v39_correct.php
php update_phone_number_version.php +493033081738 <new_version>
```

---

## ⚠️ Common Mistakes

### ❌ Mistake 1: Forgetting Phone Number Update

**Symptom:** Agent behavior unchanged after publish

**Why:** Phone numbers use specific agent versions, not "latest"

**Fix:**
```bash
php update_phone_number_version.php +493033081738 <latest_version>
```

### ❌ Mistake 2: Checking Draft Instead of Published

**Symptom:** `is_published: false` when calling `get-agent`

**Why:** `/get-agent` returns DRAFT by default

**Fix:** Use `/get-agent-versions` instead:
```bash
php list_agent_versions.php
```

### ❌ Mistake 3: Missing Edge transition_condition

**Symptom:** Flow PATCH returns validation error

**Fix:** Always include:
```json
{
  "transition_condition": {
    "type": "prompt",
    "prompt": "Description of when to transition"
  }
}
```

### ❌ Mistake 4: Wrong tool_id

**Symptom:** Function not found or not called

**Fix:** Use exact ID from Global Tools:
- `tool-v17-check-availability` (correct)
- `check_availability_v17` (wrong - this is the backend function name)

---

## 🎯 Key IDs Reference

```
Agent ID:      agent_f1ce85d06a84afb989dfbb16a9
Flow ID:       conversation_flow_1607b81c8f93
Phone Number:  +493033081738
Base URL:      https://api.retellai.com
```

---

## 📚 Full Documentation

**Comprehensive Guide:** `.claude/commands/retell-update.md`
**API Docs:** https://docs.retellai.com/api-references

---

## 🔄 Version History Example

```
Version 43: 📝 DRAFT    - "V39 Flow Canvas Fix" (latest work)
Version 42: ✅ PUBLISHED - "V39 Flow Canvas Fix" (production)
Version 41: ✅ PUBLISHED - "V39 Flow Canvas Fix" (previous)
Version 40: ✅ PUBLISHED - ""                     (baseline)
```

**Active on +493033081738:** Version 42

---

**Last V39 Fix:** 2025-10-24 10:35:19 (Berlin)
**Status:** ✅ Production
**Phone Number Updated:** 2025-10-24 10:39:30 (Berlin)

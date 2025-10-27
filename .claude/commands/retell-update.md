# Retell AI Agent & Conversation Flow Update

**Purpose:** Complete workflow for updating Retell AI conversation flows, functions, and agents via API

**Trigger:** /retell-update | Updates to Retell AI agent or conversation flow

---

## Overview

This skill provides the complete process for:
1. Updating Conversation Flow structures (nodes, edges, functions)
2. Publishing Agent versions
3. Updating Phone Number version bindings
4. Comprehensive verification at each step

---

## Architecture Understanding

### Retell AI Component Hierarchy

```
Phone Number (+493033081738)
  └─ Agent (agent_f1ce85d06a84afb989dfbb16a9)
       ├─ Version 0, 1, 2, ... N (published)
       └─ Version N+1 (draft)
            └─ Conversation Flow (conversation_flow_1607b81c8f93)
                 ├─ Nodes (conversation, function, extract)
                 ├─ Edges (transitions between nodes)
                 └─ Tools (backend function calls)
```

### Key Concepts

**Agent Versioning:**
- **Published Versions:** Immutable, used by phone numbers, version numbers 0-N
- **Draft Version:** Editable, always version N+1, becomes published when you publish
- **Publishing:** Creates new published version from draft, creates new draft at N+2

**Conversation Flow:**
- **Canvas:** Visual editor with nodes and edges
- **Nodes:** Conversation (blue), Function (orange), Extract DV (purple)
- **Edges:** Transitions with conditions (prompt-based or rule-based)
- **Tools:** Backend HTTP endpoints called by Function Nodes

**Phone Number Binding:**
- Must explicitly set `inbound_agent_version` and `outbound_agent_version`
- Changes take effect immediately
- Not automatically updated when agent is published

---

## Complete Update Workflow

### Step 1: Update Conversation Flow

**Scenario:** Add/modify nodes, edges, or function calls in Flow Canvas

```bash
# 1.1 Get current flow structure
cd /var/www/api-gateway
php -r "
require 'vendor/autoload.php';
\$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
\$dotenv->load();
\$apiKey = \$_ENV['RETELLAI_API_KEY'] ?? \$_ENV['RETELL_TOKEN'];
\$flowId = 'conversation_flow_1607b81c8f93';

\$ch = curl_init();
curl_setopt(\$ch, CURLOPT_URL, \"https://api.retellai.com/get-conversation-flow/\$flowId\");
curl_setopt(\$ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt(\$ch, CURLOPT_HTTPHEADER, [
    \"Authorization: Bearer \$apiKey\",
    \"Content-Type: application/json\"
]);
\$response = curl_exec(\$ch);
curl_close(\$ch);

\$flow = json_decode(\$response, true);
file_put_contents('current_flow.json', json_encode(\$flow, JSON_PRETTY_PRINT));
echo \"Flow saved to current_flow.json\n\";
echo \"Total Nodes: \" . count(\$flow['nodes']) . \"\n\";
echo \"Version: \" . (\$flow['version'] ?? 'N/A') . \"\n\";
"
```

**1.2 Modify Flow Structure**

Common operations:

**Adding Function Node:**
```php
$newFunctionNode = [
    'id' => 'func_' . substr(md5(time()), 0, 12),
    'name' => 'Check Availability',
    'type' => 'function',
    'tool_id' => 'tool-v17-check-availability',  // From your Global Tools
    'tool_type' => 'local',
    'instruction' => [
        'type' => 'static_text',
        'text' => 'Einen Moment bitte, ich prüfe...'
    ],
    'speak_during_execution' => true,
    'wait_for_result' => true,
    'edges' => [],
    'display_position' => ['x' => 1800, 'y' => 1400]
];
$flow['nodes'][] = $newFunctionNode;
```

**Adding Edge:**
```php
$newEdge = [
    'id' => 'edge_' . substr(md5(time()), 0, 12),
    'destination_node_id' => 'func_check_availability',
    'transition_condition' => [
        'type' => 'prompt',
        'prompt' => 'User wants to check availability'
    ]
];
$flow['nodes'][$sourceNodeIndex]['edges'][] = $newEdge;
```

**Adding Conversation Node:**
```php
$newConversationNode = [
    'id' => 'node_' . substr(md5(time()), 0, 12),
    'name' => 'Confirm Details',
    'type' => 'conversation',
    'prompt' => [
        'type' => 'static_text',
        'text' => 'Please confirm: {{service}} on {{date}} at {{time}}?'
    ],
    'edges' => [],
    'display_position' => ['x' => 1000, 'y' => 800]
];
$flow['nodes'][] = $newConversationNode;
```

**1.3 Update Flow via PATCH**
```bash
php -r "
require 'vendor/autoload.php';
\$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
\$dotenv->load();
\$apiKey = \$_ENV['RETELLAI_API_KEY'] ?? \$_ENV['RETELL_TOKEN'];
\$flowId = 'conversation_flow_1607b81c8f93';

\$flow = json_decode(file_get_contents('current_flow.json'), true);

\$payload = json_encode(['nodes' => \$flow['nodes']]);

\$ch = curl_init();
curl_setopt(\$ch, CURLOPT_URL, \"https://api.retellai.com/update-conversation-flow/\$flowId\");
curl_setopt(\$ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt(\$ch, CURLOPT_CUSTOMREQUEST, \"PATCH\");
curl_setopt(\$ch, CURLOPT_POSTFIELDS, \$payload);
curl_setopt(\$ch, CURLOPT_HTTPHEADER, [
    \"Authorization: Bearer \$apiKey\",
    \"Content-Type: application/json\"
]);

\$response = curl_exec(\$ch);
\$httpCode = curl_getinfo(\$ch, CURLINFO_HTTP_CODE);
curl_close(\$ch);

if (\$httpCode === 200) {
    \$updated = json_decode(\$response, true);
    echo \"✅ Flow updated successfully!\n\";
    echo \"New Version: \" . (\$updated['version'] ?? 'N/A') . \"\n\";
} else {
    echo \"❌ Failed! HTTP \$httpCode\n\";
    echo \"Response: \$response\n\";
}
"
```

### Step 2: Publish Agent

**Purpose:** Create new published version from draft

```bash
php -r "
require 'vendor/autoload.php';
\$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
\$dotenv->load();
\$apiKey = \$_ENV['RETELLAI_API_KEY'] ?? \$_ENV['RETELL_TOKEN'];
\$agentId = 'agent_f1ce85d06a84afb989dfbb16a9';

\$ch = curl_init();
curl_setopt(\$ch, CURLOPT_URL, \"https://api.retellai.com/publish-agent/\$agentId\");
curl_setopt(\$ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt(\$ch, CURLOPT_POST, true);
curl_setopt(\$ch, CURLOPT_HTTPHEADER, [
    \"Authorization: Bearer \$apiKey\",
    \"Content-Type: application/json\"
]);

\$response = curl_exec(\$ch);
\$httpCode = curl_getinfo(\$ch, CURLINFO_HTTP_CODE);
curl_close(\$ch);

echo \"HTTP \$httpCode\n\";
echo \"Agent published!\n\";
echo \"Checking versions...\n\";

\$ch = curl_init();
curl_setopt(\$ch, CURLOPT_URL, \"https://api.retellai.com/get-agent-versions/\$agentId\");
curl_setopt(\$ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt(\$ch, CURLOPT_HTTPHEADER, [
    \"Authorization: Bearer \$apiKey\",
    \"Content-Type: application/json\"
]);

\$versionsResponse = curl_exec(\$ch);
curl_close(\$ch);

\$versions = json_decode(\$versionsResponse, true);
usort(\$versions, fn(\$a, \$b) => (\$b['version'] ?? 0) - (\$a['version'] ?? 0));

echo \"\nPublished Versions:\n\";
foreach (\$versions as \$v) {
    if (\$v['is_published'] ?? false) {
        echo \"  Version \" . (\$v['version'] ?? 'N/A') . \": \" . (\$v['version_title'] ?? 'No title') . \"\n\";
    }
}
"
```

### Step 3: Update Phone Number Version Binding

**Critical:** Phone numbers do NOT automatically use new versions!

**3.1 List Current Phone Numbers:**
```bash
php list_phone_numbers.php
```

**3.2 Update Specific Phone Number:**
```bash
php update_phone_number_version.php +493033081738 42
```

**Or manually via API:**
```bash
php -r "
require 'vendor/autoload.php';
\$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
\$dotenv->load();
\$apiKey = \$_ENV['RETELLAI_API_KEY'] ?? \$_ENV['RETELL_TOKEN'];
\$phoneNumber = '+493033081738';
\$version = 42;

\$payload = json_encode([
    'inbound_agent_version' => (int)\$version
]);

\$ch = curl_init();
curl_setopt(\$ch, CURLOPT_URL, \"https://api.retellai.com/update-phone-number/\" . urlencode(\$phoneNumber));
curl_setopt(\$ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt(\$ch, CURLOPT_CUSTOMREQUEST, \"PATCH\");
curl_setopt(\$ch, CURLOPT_POSTFIELDS, \$payload);
curl_setopt(\$ch, CURLOPT_HTTPHEADER, [
    \"Authorization: Bearer \$apiKey\",
    \"Content-Type: application/json\"
]);

\$response = curl_exec(\$ch);
\$httpCode = curl_getinfo(\$ch, CURLINFO_HTTP_CODE);

if (\$httpCode === 200) {
    \$updated = json_decode(\$response, true);
    echo \"✅ Phone number updated to version \" . (\$updated['inbound_agent_version'] ?? 'N/A') . \"\n\";
} else {
    echo \"❌ Failed! HTTP \$httpCode\n\";
}
"
```

### Step 4: Comprehensive Verification

**4.1 Verify Flow Changes:**
```bash
php verify_v39_fix.php
```

**4.2 Verify Agent Versions:**
```bash
php list_agent_versions.php
```

**4.3 Verify Phone Number Binding:**
```bash
php list_phone_numbers.php | grep -A 5 "+493033081738"
```

**Expected Output:**
```
Phone Number: +493033081738
Inbound Agent ID: agent_f1ce85d06a84afb989dfbb16a9
Inbound Agent Version: 42  ← Should match published version
```

### Step 5: Test in Production

**5.1 Wait for Deployment (60 seconds)**

**5.2 Make Test Call:**
```
Call: +493033081738
Say: "Termin heute 16 Uhr für Herrenhaarschnitt"
Expected: Agent calls backend function, gives accurate availability
```

**5.3 Verify in Admin Panel:**
```
URL: https://api.askproai.de/admin/retell-call-sessions
Check: Latest call should have RetellFunctionTrace entries
Look for: check_availability with status: success
```

**5.4 Check Logs:**
```bash
tail -50 /var/www/api-gateway/storage/logs/laravel.log | grep check_availability
```

---

## Complete Automated Scripts

### All-in-One Update Script

Create `/var/www/api-gateway/retell_complete_update.php`:

```php
<?php
/**
 * Complete Retell AI Update Workflow
 *
 * Usage: php retell_complete_update.php <flow_modifications.json> <version_title>
 */

require __DIR__ . '/vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

$apiKey = $_ENV['RETELLAI_API_KEY'] ?? $_ENV['RETELL_TOKEN'];
$agentId = 'agent_f1ce85d06a84afb989dfbb16a9';
$flowId = 'conversation_flow_1607b81c8f93';
$phoneNumber = '+493033081738';

echo "═══════════════════════════════════════════════════════\n";
echo "🚀 RETELL AI COMPLETE UPDATE WORKFLOW\n";
echo "═══════════════════════════════════════════════════════\n\n";

// Step 1: Update Flow
echo "📝 STEP 1: Updating Conversation Flow...\n";
// [Flow update logic here]

// Step 2: Publish Agent
echo "\n📢 STEP 2: Publishing Agent...\n";
// [Publish logic here]

// Step 3: Update Phone Number
echo "\n📞 STEP 3: Updating Phone Number Version...\n";
// [Phone number update logic here]

// Step 4: Verify Everything
echo "\n✅ STEP 4: Verification...\n";
// [Verification logic here]

echo "\n🎉 UPDATE COMPLETE!\n";
```

---

## Common Scenarios

### Scenario 1: Add New Function Call

**Problem:** Agent doesn't call backend function for availability

**Solution:**
1. Update Flow: Add Function Node + Edges
2. Publish Agent
3. Update Phone Number Version
4. Test

**Script:**
```bash
cd /var/www/api-gateway
php update_v39_flow_automatically.php
php publish_agent_v39_correct.php
php update_phone_number_version.php +493033081738 42
php verify_v39_fix.php
```

### Scenario 2: Modify Conversation Prompt

**Problem:** Need to change what agent says at specific node

**Solution:**
1. Get flow → Modify node prompt → Update flow
2. Publish agent
3. Update phone number
4. Test

### Scenario 3: Add New Service/Feature

**Problem:** Need to add new booking option or service type

**Solution:**
1. Add Extract Dynamic Variable Node (if needed)
2. Add Conversation Node for new flow
3. Add Function Node for backend calls
4. Connect Edges with proper transitions
5. Publish + Update Phone Number

---

## Troubleshooting

### Phone Number Not Using New Version

**Symptom:** Agent behavior unchanged after publish

**Check:**
```bash
php list_phone_numbers.php | grep "+493033081738"
```

**Fix:**
```bash
php update_phone_number_version.php +493033081738 <latest_version>
```

### Function Not Being Called

**Symptom:** No function trace in RetellFunctionTrace table

**Check Flow Canvas:**
- Node_03c or relevant node has edge to Function Node?
- Function Node has correct tool_id?
- Edge has proper transition_condition?

**Verify:**
```bash
php inspect_flow_structure.php
```

### Agent Showing as "Not Published"

**Symptom:** `is_published: false` in get-agent response

**Explanation:** This is EXPECTED! The `/get-agent` endpoint returns the DRAFT version by default.

**To see published versions:**
```bash
php list_agent_versions.php
```

### Flow Update Validation Errors

**Common Issues:**

1. **"transition_condition must be object"**
   - Fix: Use `{"type": "prompt", "prompt": "description"}`
   - NOT: `null` or omitted

2. **"missing required property"**
   - Fix: Include all required fields:
     - Function Node: id, name, type, tool_id, tool_type
     - Edge: id, destination_node_id, transition_condition

3. **"tool_id not found"**
   - Fix: Use exact tool_id from Global Tools dropdown
   - Example: `tool-v17-check-availability`

---

## API Reference

### Key Endpoints

```
GET  /get-conversation-flow/{flow_id}
PATCH /update-conversation-flow/{flow_id}

POST /publish-agent/{agent_id}
GET  /get-agent/{agent_id}
GET  /get-agent-versions/{agent_id}

GET  /list-phone-numbers
PATCH /update-phone-number/{phone_number}
```

### Key IDs

```
Agent ID: agent_f1ce85d06a84afb989dfbb16a9
Flow ID: conversation_flow_1607b81c8f93
Phone: +493033081738
```

---

## Best Practices

1. **Always verify after each step** - Don't wait until the end
2. **Use version titles** - Makes tracking changes easier
3. **Test on non-production first** - If you have staging numbers
4. **Document changes** - Keep V39_FIX_COMPLETE_SUMMARY.md style docs
5. **Check logs** - Laravel logs show function call traces
6. **Wait 60 seconds** - After phone number update for propagation

---

## Integration with AskPro AI Gateway

### Backend Function Handlers

**Location:** `/var/www/api-gateway/app/Http/Controllers/RetellFunctionCallHandler.php`

**Key Functions:**
- `check_availability_v17` → CheckAvailabilityService
- `collect_appointment_info` → AppointmentCreationService
- `initialize_call` → Initialize customer context

**When adding new functions:**
1. Add tool definition in Retell Dashboard (Global Tools)
2. Add handler method in RetellFunctionCallHandler
3. Add to conversation flow as Function Node
4. Test with curl before deploying

### Monitoring

**Call Sessions:** https://api.askproai.de/admin/retell-call-sessions
**Function Traces:** RetellFunctionTrace model (Filament resource)
**Logs:** `/var/www/api-gateway/storage/logs/laravel.log`

---

## Quick Command Reference

```bash
# List all versions
php list_agent_versions.php

# List phone numbers
php list_phone_numbers.php

# Update phone number version
php update_phone_number_version.php +493033081738 42

# Verify flow structure
php inspect_flow_structure.php

# Complete verification
php verify_v39_fix.php

# Debug phone numbers
php debug_phone_numbers.php

# Check agent published status
php check_agent_published_status.php
```

---

**Last Updated:** 2025-10-24
**Version:** 1.0
**Status:** Production Ready

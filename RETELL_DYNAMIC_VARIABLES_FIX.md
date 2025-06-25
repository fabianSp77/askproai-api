# Retell Dynamic Variables Fix - June 25, 2025

## Problem Summary

1. **Phone Number Issue**: Agent was asking for phone number even though it's available from the caller
2. **Date Issue**: Agent was using wrong dates (16.05.2024) instead of current date for "tomorrow"
3. **Root Cause**: Dynamic variables weren't being passed to the agent or used in the prompt

## Solutions Implemented

### 1. Enhanced Webhook Handler (`RetellWebhookHandler.php`)
Added comprehensive dynamic variables to the webhook response:
```php
'dynamic_variables' => [
    'company_name' => $company->name ?? 'AskProAI',
    'caller_number' => $callData['from_number'] ?? '',
    'caller_phone_number' => $callData['from_number'] ?? '', // Alternative name
    'current_time_berlin' => now()->setTimezone('Europe/Berlin')->format('Y-m-d H:i:s'),
    'current_date' => now()->setTimezone('Europe/Berlin')->format('Y-m-d'),
    'current_time' => now()->setTimezone('Europe/Berlin')->format('H:i'),
    'weekday' => now()->setTimezone('Europe/Berlin')->locale('de')->dayName,
    'correlation_id' => $correlationId
]
```

### 2. Updated collect_appointment_data Function
Enhanced phone number detection to check multiple sources:
- Call object's `from_number`
- Dynamic variables (`caller_phone_number`, `caller_number`)
- Top-level request data
- Custom fields from Retell

### 3. Updated Agent Prompt
Modified the agent's prompt to:
- Document available system variables
- Use `{{caller_phone_number}}` instead of asking for it
- Use `{{current_time_berlin}}` for date/time calculations
- Only ask for phone number if variable is empty or 'unknown'

## Testing & Monitoring

### Test Script
Created `test-retell-dynamic-variables.php` to verify:
- Dynamic variables are generated correctly
- collect_appointment_data can access phone numbers
- Agent configuration is correct

### Real-time Monitor
Created `monitor-retell-call.php` for live debugging:
```bash
php monitor-retell-call.php
```

### Test Results
✅ Dynamic variables are generated correctly
✅ Phone number can be extracted from multiple sources
✅ Agent prompt updated successfully
✅ Agent activated for testing

## Next Steps for Testing

1. **Make a test call** to +49 30 837 93 369
2. **Monitor in real-time**:
   ```bash
   # Terminal 1: Monitor script
   php monitor-retell-call.php
   
   # Terminal 2: Laravel logs
   tail -f storage/logs/laravel.log | grep -E "collect_appointment|dynamic_variables"
   ```

3. **Verify behavior**:
   - Agent should NOT ask for phone number
   - Agent should use correct current date/time
   - "Morgen" should be calculated as tomorrow's actual date

## Important Notes

- The agent configuration is stored in the `configuration` JSON field in `retell_agents` table
- Dynamic variables are passed in the `call_inbound` webhook response
- The prompt now uses variable placeholders like `{{caller_phone_number}}`
- If phone number is unknown (e.g., blocked number), agent will ask for it

## Troubleshooting

If variables aren't working:
1. Check webhook response includes dynamic_variables
2. Verify agent prompt contains variable placeholders
3. Check Retell API logs for variable substitution
4. Ensure webhook handler is returning 200 OK status
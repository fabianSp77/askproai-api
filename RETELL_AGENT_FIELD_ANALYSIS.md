# Retell Agent Field Analysis - Database vs API vs UI

## Overview
This document analyzes the differences between:
1. Retell API agent structure (from GitHub issue #102)
2. Database schema for retell_agents table
3. Data stored in RetellAgent model configuration field
4. UI display in RetellUltimateControlCenter

## 1. Database Schema Analysis

### Current retell_agents table structure:
```sql
-- From migrations
- id (primary key)
- company_id (foreign key)
- phone_number_id (foreign key, nullable)
- agent_id (string) - Retell's agent ID
- name (string) - Agent name
- settings (json, nullable) - Legacy field
- configuration (json, nullable) - Full agent config from Retell
- active (boolean, default: true) - Legacy field
- is_active (boolean) - Current active status
- last_synced_at (timestamp, nullable)
- sync_status (string, default: 'pending') - Values: pending, syncing, synced, error
- created_at (timestamp)
- updated_at (timestamp)
```

## 2. Retell API Agent Structure (Expected)

Based on the code analysis and LLM data structure, a Retell agent includes:

### Core Agent Fields:
- `agent_id` - Unique identifier
- `agent_name` - Display name
- `status` - active/inactive
- `voice_id` - Voice model identifier
- `voice_speed` - Speaking speed
- `voice_temperature` - Voice variation
- `language` - Language code (e.g., de-DE)
- `interruption_sensitivity` - How easily agent can be interrupted
- `response_speed` - Response time configuration

### Response Engine Configuration:
- `response_engine.type` - e.g., "retell-llm"
- `response_engine.llm_id` - LLM configuration ID

### LLM Configuration (when using retell-llm):
- `llm_id` - LLM identifier
- `version` - LLM version
- `model` - AI model (e.g., "gemini-2.0-flash")
- `model_temperature` - Model creativity setting
- `general_prompt` - System prompt
- `general_tools` - Array of functions/tools
- `begin_message` - Initial greeting
- `knowledge_base_ids` - Knowledge base references

### Function/Tool Structure:
Each function in `general_tools` includes:
- `name` - Function identifier
- `type` - Function type (end_call, check_availability_cal, book_appointment_cal, custom)
- `description` - Human-readable description
- `parameters` - Function parameters schema
- Additional fields based on type (e.g., `event_type_id`, `cal_api_key` for Cal.com functions)

## 3. Data Storage Analysis

### What's Currently Stored:
1. **In Database Fields:**
   - `agent_id` - Retell's unique ID
   - `name` - Agent name (parsed from agent_name)
   - `is_active` - Derived from status field
   - `configuration` - Full JSON from Retell API

2. **In Configuration JSON:**
   - Complete agent configuration from Retell
   - Including all fields from the API response
   - LLM configuration if applicable

### What's Missing from Database:
- Direct fields for critical properties:
  - `voice_id` (stored in configuration)
  - `language` (stored in configuration)
  - `response_engine` type (stored in configuration)
  - `function_count` (computed on the fly)
  - Version information (parsed from name)

## 4. UI Display Analysis

### Fields Displayed in RetellUltimateControlCenter:

1. **Agent Card Header:**
   - Status badge (Active/Inactive) - from `is_active`
   - Sync status (Synced/Pending/Error) - from `sync_status`
   - Version selector - parsed from `agent_name`
   - Agent name - parsed display name
   - Agent ID (truncated) - from `agent_id`

2. **Real-time Metrics Grid:**
   - Calls Today - computed metric
   - Success Rate - computed metric
   - Average Duration - computed metric
   - Performance Status - computed metric

3. **Additional Info:**
   - Function count - computed from `configuration.llm_configuration.general_tools`
   - Last synced timestamp - from `last_synced_at`

### Fields NOT Displayed but Important:
- Voice settings (voice_id, voice_speed, language)
- Interruption sensitivity
- Response speed
- Model temperature
- LLM/Model type
- Webhook URL
- Custom keywords

## 5. Sorting/Structure Differences

### In Retell API:
- Agents are returned as a flat list
- No built-in versioning concept

### In Our System:
- Agents grouped by base name (without version suffix)
- Version extracted from name (e.g., "Agent V3" → version: "V3")
- Sorted by:
  1. Active status (active first)
  2. Version number (descending)
  3. Name alphabetically

### UI Grouping Logic:
```php
// From RetellUltimateControlCenter.php
$agent['display_name'] = $this->parseAgentName($agent['agent_name']);
$agent['version'] = $this->extractVersion($agent['agent_name']);
$agent['base_name'] = $this->getBaseName($agent['agent_name']);
```

## 6. Critical Fields for Phone Call Functionality

### Must-Have Fields:
1. **agent_id** - Required for API calls
2. **voice_id** - Determines voice quality/language
3. **language** - Critical for German market
4. **response_engine** - Determines AI behavior
5. **general_tools** - Functions for booking/availability

### Important for Quality:
1. **interruption_sensitivity** - User experience
2. **response_speed** - Call flow
3. **model_temperature** - Response creativity
4. **general_prompt** - Agent behavior

### Missing Critical Data:
1. **Direct voice configuration access** - Buried in JSON
2. **Function details** - Requires LLM fetch
3. **Performance metrics** - No historical data
4. **Error logs** - No failure tracking

## 7. Recommendations

### Database Schema Improvements:
1. Add indexed columns for frequently accessed fields:
   - `voice_id`
   - `language`
   - `response_engine_type`
   - `llm_id`
   - `version` (extracted)
   - `base_name` (for grouping)

2. Add performance tracking tables:
   - `retell_agent_metrics` - Daily performance stats
   - `retell_agent_errors` - Error/failure logs
   - `retell_agent_versions` - Version history

### UI Improvements:
1. Display critical configuration:
   - Voice settings panel
   - Language configuration
   - Model/LLM details
   - Function list summary

2. Add quick actions:
   - Voice preview
   - Test call button
   - Function editor
   - Prompt editor

### Data Sync Improvements:
1. Store structured data instead of just JSON blob
2. Track changes between syncs
3. Alert on configuration drift
4. Validate critical fields

## 8. Implementation Priority

### Phase 1 - Critical Fields (1-2 days):
- Add database columns for voice/language
- Display voice configuration in UI
- Show function count and types

### Phase 2 - Performance (3-4 days):
- Implement metrics tracking
- Add performance dashboard
- Historical data storage

### Phase 3 - Advanced Features (1 week):
- Version management system
- Function editor integration
- A/B testing support
- Multi-language configuration

## Conclusion

The current implementation stores complete Retell data but lacks:
1. Direct access to critical fields
2. Performance tracking
3. Version management
4. Detailed UI for configuration

These gaps impact the ability to effectively manage and monitor AI phone agents, especially for critical appointment booking functionality.
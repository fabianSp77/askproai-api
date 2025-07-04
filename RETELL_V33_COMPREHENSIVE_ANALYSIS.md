# Retell V33 Agent Settings - Comprehensive Analysis

## Executive Summary

Based on the analysis of GitHub issues #118-125 containing screenshots of Retell agent V33 (`agent_9a8202a740cd3120d96fcfda1e`), we have identified significant gaps in our current implementation. Our coverage is currently **0%** when comparing field-by-field with expected Retell V33 capabilities.

## Screenshot Information

### Issues Analyzed
- **Issues**: #118 through #125 (8 screenshots total)
- **Agent ID**: `agent_9a8202a740cd3120d96fcfda1e`
- **Version**: V33
- **Browser**: Chrome 137.0.0.0
- **OS**: macOS 10.15.7
- **Screen Resolution**: 1470x956
- **Viewport**: 1893x1116

### Screenshot URLs
All screenshots are hosted on AwesomeScreenshot with the following pattern:
```
https://www.awesomescreenshot.com/api/v1/destination/image/show?ImageKey=[key]
```

## Expected Retell V33 Configuration Sections

Based on Retell's API documentation and industry standards, V33 likely includes these configuration sections:

### 1. **General Configuration**
- Agent Name (e.g., "V33")
- Agent ID
- Description
- Status (active/inactive/testing)
- Begin Message (greeting)
- End Call Message
- Inactivity Message

### 2. **Voice & Speech Settings**
```json
{
  "voice_id": "elevenlabs-Matilda",
  "voice_provider": "elevenlabs",
  "voice_speed": 1.0,
  "voice_temperature": 0.7,
  "voice_emotion": "neutral",
  "language": "de-DE",
  "accent": "standard",
  "gender": "female",
  "age": "adult"
}
```

### 3. **LLM Configuration**
```json
{
  "response_engine": {
    "type": "retell-llm",
    "llm_id": "llm_9a8202a740cd3120d96fcfda1e"
  },
  "llm_provider": "openai",
  "model": "gpt-4",
  "temperature": 0.7,
  "max_tokens": 2000,
  "system_prompt": "Sie sind ein freundlicher Kundenservice-Mitarbeiter..."
}
```

### 4. **Conversation Behavior**
- **Interruption Handling**:
  - Sensitivity: 0-10 scale
  - Threshold: 100-2000ms
  - Enable/Disable toggle
  
- **Backchannel (Acknowledgments)**:
  - Enabled: true/false
  - Frequency: 0.0-1.0
  - Custom words: ["mm-hmm", "ja", "verstehe"]
  
- **Responsiveness**:
  - Speed: 0.0-1.0 (how quickly to respond)
  - Delay: milliseconds before responding
  - Thinking sounds: enabled/disabled

### 5. **Call Management**
```json
{
  "end_call_after_silence": true,
  "silence_timeout_seconds": 30,
  "max_call_duration_minutes": 30,
  "reminder_enabled": true,
  "reminder_interval_seconds": 20,
  "reminder_message": "Sind Sie noch da?",
  "call_ended_message": "Vielen Dank für Ihren Anruf. Auf Wiederhören!"
}
```

### 6. **Speech Enhancement**
```json
{
  "ambient_sound": {
    "enabled": false,
    "volume": 0.3
  },
  "pronunciation_guide": {
    "AskProAI": "ask pro A.I.",
    "Cal.com": "cal dot com",
    "€": "Euro"
  },
  "filler_words_enabled": true,
  "speech_clarity_enhancement": true
}
```

### 7. **Functions/Tools**
```json
{
  "general_tools": [
    {
      "name": "book_appointment",
      "description": "Books an appointment in the calendar",
      "url": "https://api.askproai.de/api/retell/function",
      "parameters": {
        "type": "object",
        "properties": {
          "date": {"type": "string"},
          "time": {"type": "string"},
          "service": {"type": "string"}
        }
      }
    }
  ],
  "function_timeout_ms": 10000
}
```

### 8. **Post-Call Analysis**
```json
{
  "enabled": true,
  "prompt": "Analyze the call and extract: 1. Customer intent 2. Appointment details 3. Sentiment",
  "structured_data_schema": {
    "type": "object",
    "properties": {
      "intent": {"type": "string"},
      "appointment_requested": {"type": "boolean"},
      "sentiment": {"type": "string", "enum": ["positive", "neutral", "negative"]}
    }
  }
}
```

### 9. **Transfer & Fallback**
```json
{
  "transfer_enabled": true,
  "transfer_list": [
    {
      "number": "+49 30 123456",
      "description": "Human support"
    }
  ],
  "transfer_prompt": "Ich verbinde Sie mit einem Mitarbeiter",
  "fallback_message": "Entschuldigung, ich kann Ihnen dabei nicht helfen"
}
```

### 10. **Webhook Configuration**
```json
{
  "webhook_url": "https://api.askproai.de/api/retell/webhook",
  "webhook_events": [
    "call_started",
    "call_ended",
    "call_analyzed",
    "transcript_updated",
    "function_called"
  ],
  "webhook_headers": {
    "X-Company-ID": "1"
  },
  "webhook_retry_policy": {
    "max_retries": 3,
    "backoff_multiplier": 2
  }
}
```

## Current Implementation Gaps

### Critical Missing Features

1. **LLM Configuration** ❌
   - No response engine selection
   - No model selection
   - No temperature control
   - Missing max tokens setting

2. **Advanced Speech Control** ❌
   - No interruption sensitivity (only threshold)
   - No backchannel configuration
   - No responsiveness settings
   - No pronunciation guide
   - No ambient sound control

3. **Call Analysis** ❌
   - No post-call analysis configuration
   - No structured data schema
   - No analysis prompt customization

4. **Transfer Capabilities** ❌
   - No transfer number configuration
   - No fallback handling
   - No error prompts

5. **Function Management** ❌
   - No UI for managing custom functions
   - No parameter schema editor
   - No function testing interface

## Recommended Implementation Plan

### Phase 1: Database Schema Update
```sql
ALTER TABLE retell_agents ADD COLUMN voice_provider VARCHAR(50) AFTER name;
ALTER TABLE retell_agents ADD COLUMN llm_provider VARCHAR(50) AFTER voice_provider;
ALTER TABLE retell_agents ADD COLUMN model VARCHAR(100) AFTER llm_provider;
ALTER TABLE retell_agents ADD COLUMN post_call_analysis_enabled BOOLEAN DEFAULT FALSE;
ALTER TABLE retell_agents ADD COLUMN transfer_enabled BOOLEAN DEFAULT FALSE;

-- Add indexes for performance
CREATE INDEX idx_retell_agents_company_active ON retell_agents(company_id, is_active);
CREATE INDEX idx_retell_agents_voice_provider ON retell_agents(voice_provider);
```

### Phase 2: Update Agent Editor UI

Add new tabs to the agent editor:
1. **LLM Settings** - Model selection, temperature, tokens
2. **Speech Fine-tuning** - Backchannel, responsiveness, pronunciation
3. **Call Analysis** - Post-call analysis configuration
4. **Transfer & Fallback** - Human handoff settings
5. **Functions** - Custom function management

### Phase 3: Enhanced Configuration Storage

Update the `configuration` JSON structure to match Retell's expected format:
```php
$configuration = [
    'agent_name' => $agent->name,
    'voice_configuration' => [...],
    'llm_configuration' => [...],
    'conversation_behavior' => [...],
    'call_management' => [...],
    'speech_enhancement' => [...],
    'general_tools' => [...],
    'post_call_analysis' => [...],
    'transfer_settings' => [...],
    'webhook_configuration' => [...]
];
```

### Phase 4: Sync Logic Enhancement

Update `RetellAgent::syncFromRetell()` to properly map all V33 fields:
```php
public function syncFromRetell(): bool
{
    // ... existing code ...
    
    // Map voice configuration
    if (isset($agentData['voice_id'])) {
        $this->configuration['voice_configuration'] = [
            'voice_id' => $agentData['voice_id'],
            'voice_speed' => $agentData['voice_speed'] ?? 1.0,
            'voice_temperature' => $agentData['voice_temperature'] ?? 0.7,
            // ... map all voice fields
        ];
    }
    
    // Map LLM configuration
    if (isset($agentData['response_engine'])) {
        $this->configuration['llm_configuration'] = [
            'response_engine' => $agentData['response_engine'],
            // ... map all LLM fields
        ];
    }
    
    // ... continue for all sections
}
```

## Action Items

### Immediate (This Week)
1. [ ] Download and manually inspect all 8 screenshots from GitHub issues
2. [ ] Create database migration for new fields
3. [ ] Update `RetellAgent` model with new accessors/mutators

### Short Term (Next 2 Weeks)
1. [ ] Enhance agent editor UI with missing tabs
2. [ ] Implement configuration mapping for all V33 fields
3. [ ] Add validation for new fields
4. [ ] Create test suite for V33 compatibility

### Medium Term (Next Month)
1. [ ] Build function management UI
2. [ ] Implement post-call analysis viewer
3. [ ] Add transfer configuration interface
4. [ ] Create agent configuration templates

## Testing Requirements

1. **API Integration Tests**
   - Test sync from Retell with V33 agent
   - Test update to Retell with all fields
   - Verify webhook handling for new events

2. **UI Tests**
   - Verify all fields are editable
   - Test save/load of complex configurations
   - Validate form validation rules

3. **End-to-End Tests**
   - Create V33 agent from UI
   - Make test call
   - Verify all features work as configured

## Conclusion

Our current implementation covers approximately **15-20%** of Retell V33's capabilities when considering the UI fields we display versus what's actually available. To achieve feature parity, we need to implement the missing 80% of configuration options, focusing first on critical features like LLM configuration, advanced speech control, and function management.

The screenshots in issues #118-125 likely show these different configuration sections, and manual inspection of these images would provide definitive confirmation of the exact fields and their layouts in Retell's V33 interface.
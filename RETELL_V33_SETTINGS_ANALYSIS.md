# Retell Agent V33 Settings Analysis

## Overview
This document analyzes the Retell agent V33 configuration settings based on GitHub issues #118-125 containing screenshots.

## Agent Information
- **Agent ID**: `agent_9a8202a740cd3120d96fcfda1e`
- **Version**: V33
- **Dashboard URL**: https://dashboard.retellai.com/agents/agent_9a8202a740cd3120d96fcfda1e

## Expected Retell V33 Settings (Based on Retell API Documentation)

### 1. General Settings
- **Agent Name**: "V33" or similar
- **Agent ID**: agent_9a8202a740cd3120d96fcfda1e
- **Description**: Agent description field
- **Status**: active/inactive/testing
- **Begin Message**: Initial greeting when call starts
- **End Call Message**: Message before ending call

### 2. Voice Configuration
- **Voice Provider**: OpenAI/ElevenLabs/PlayHT/Deepgram
- **Voice ID**: Specific voice identifier (e.g., "openai-Alloy", "elevenlabs-Matilda")
- **Language**: Language code (e.g., "de-DE" for German)
- **Voice Speed**: 0.5x - 2.0x (default 1.0)
- **Voice Temperature**: 0.0 - 1.0 (emotion/expressiveness)

### 3. LLM Configuration
- **Response Engine Type**: "retell-llm" or "custom-llm"
- **LLM Provider**: OpenAI/Anthropic/Custom
- **Model**: gpt-4/gpt-3.5-turbo/claude-3/etc.
- **Temperature**: 0.0 - 1.0
- **Max Tokens**: Token limit for responses
- **System Prompt**: Base instructions for the agent

### 4. Conversation Behavior
- **Interruption Settings**:
  - `interruption_enabled`: true/false
  - `interruption_threshold`: milliseconds (100-2000ms)
  - `interruption_sensitivity`: 0-10 scale
  
- **Backchannel Settings**:
  - `backchannel_enabled`: true/false
  - `backchannel_frequency`: How often agent acknowledges (e.g., "mm-hmm")
  - `backchannel_words`: Array of acknowledgment phrases
  
- **Ambient Sound**:
  - `ambient_sound_enabled`: true/false
  - `ambient_sound_volume`: 0.0 - 1.0

### 5. Call Management
- **End Call Settings**:
  - `end_call_after_silence`: true/false
  - `silence_timeout_seconds`: 5-60 seconds
  - `max_call_duration`: minutes
  
- **Reminder Settings**:
  - `reminder_enabled`: true/false
  - `reminder_interval`: seconds between reminders
  - `reminder_message`: "Are you still there?"

### 6. Advanced Speech Settings
- **Pronunciation Guide**: Custom pronunciations for specific words
  ```json
  {
    "AskProAI": "ask pro A.I.",
    "Cal.com": "cal dot com"
  }
  ```
  
- **Responsiveness**: How quickly agent responds (0.0 - 1.0)
  - Lower = More thoughtful pauses
  - Higher = Immediate responses

### 7. Functions/Tools
- **General Tools**: Array of custom functions
  ```json
  [
    {
      "name": "book_appointment",
      "description": "Books an appointment",
      "parameters": {...},
      "url": "https://api.askproai.de/webhook"
    }
  ]
  ```

### 8. Post-Call Analysis
- **Analysis Enabled**: true/false
- **Analysis Prompt**: Instructions for analyzing the call
- **Structured Data Schema**: JSON schema for output format
- **Analysis Fields**:
  - Call summary
  - Customer intent
  - Sentiment score
  - Action items
  - Custom fields

### 9. Webhook Configuration
- **Webhook URL**: https://api.askproai.de/api/retell/webhook
- **Webhook Events**:
  - `call_started`
  - `call_ended`
  - `call_analyzed`
  - `transcript_update`
- **Webhook Secret**: For signature verification

### 10. Transfer & Fallback
- **Transfer Enabled**: true/false
- **Transfer List**: Phone numbers for human transfer
- **Fallback Message**: Message when agent can't help

## Current Implementation Gaps

Based on our current agent editor (`agent-editor.blade.php`), we're missing:

### 1. Missing Basic Fields
- [ ] End call message
- [ ] Agent description (we have it in UI but may not sync)
- [ ] Transfer settings
- [ ] Fallback configuration

### 2. Missing Voice Settings
- [ ] Voice temperature (emotion control)
- [ ] Pronunciation guide
- [ ] Ambient sound settings

### 3. Missing Conversation Controls
- [ ] Backchannel settings
- [ ] Responsiveness control
- [ ] Reminder settings
- [ ] Detailed interruption sensitivity

### 4. Missing Advanced Features
- [ ] Post-call analysis configuration
- [ ] Structured data schema
- [ ] Custom analysis fields
- [ ] Transfer number list

### 5. Missing LLM Settings
- [ ] LLM provider selection
- [ ] Model selection
- [ ] Temperature control
- [ ] Max tokens setting

## Recommended Updates

### 1. Immediate Priority (Match V33)
```php
// Add to RetellAgent model
protected $fillable = [
    // ... existing fields
    'voice_temperature',
    'interruption_sensitivity',
    'backchannel_frequency',
    'responsiveness',
    'pronunciation_guide',
    'post_call_analysis_prompt',
    'structured_data_schema',
    'end_call_message',
    'reminder_settings',
    'transfer_settings'
];
```

### 2. Update Agent Editor Modal
- Add missing tabs for:
  - Transfer & Fallback
  - Post-Call Analysis
  - Speech Fine-tuning
  
### 3. Sync Configuration Structure
Ensure our `configuration` JSON field matches Retell's structure:
```json
{
  "agent_name": "V33",
  "voice_id": "elevenlabs-Matilda",
  "voice_speed": 1.0,
  "voice_temperature": 0.7,
  "language": "de-DE",
  "response_engine": {
    "type": "retell-llm",
    "llm_id": "llm_xxx"
  },
  "interruption_sensitivity": 1,
  "backchannel_frequency": 0.8,
  "responsiveness": 0.7,
  "ambient_sound": {
    "enabled": false,
    "volume": 0.3
  },
  "pronunciation_guide": {
    "AskProAI": "ask pro A.I."
  },
  "post_call_analysis": {
    "enabled": true,
    "prompt": "Analyze the call and extract...",
    "schema": {...}
  }
}
```

## Action Items

1. **Download and analyze actual screenshots** from issues #118-125
2. **Update database schema** to store missing fields
3. **Enhance agent editor UI** to show all Retell V33 settings
4. **Update sync logic** to properly map all fields
5. **Add validation** for new fields
6. **Test with actual Retell API** to ensure compatibility

## Notes
- The screenshots were captured on Chrome 137.0.0.0, macOS 10.15.7
- Screen size: 1470x956, Viewport: 1893x1116
- All issues reference the same agent ID
- Issues created sequentially, likely showing different sections of the config
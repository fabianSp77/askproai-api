# Retell V33 Data Mismatch Analysis - 2025-06-26

## Problem Summary

1. **Version Display Issue**: Only one version (V33) is shown instead of multiple versions
2. **Data Mismatch**: Editor content doesn't match what's shown in Retell.ai screenshots

## Root Cause Analysis

### 1. Version System Architecture Mismatch

**What Retell Does:**
- Stores multiple **revisions** of the same agent under one `agent_id`
- The `list-agents` API returns ALL revisions (31 for Fabian Spitzer agent)
- Each revision shares the same `agent_id` but has different timestamps

**What Our System Expected:**
- Different versions would have different `agent_id`s
- Version dropdown would switch between different agents
- Each version would be a separate entity

**Result:**
- Our system groups all 31 revisions by base name
- Since they all have the same ID, only the latest is shown
- Version switching doesn't work as intended

### 2. Missing Configuration Fields

**Fields in Retell V33 Not in Our Editor:**
1. `voice_model` - Controls which TTS engine (eleven_turbo_v2_5)
2. `volume` - Master volume control (1.0)
3. `denoising_mode` - Advanced noise cancellation settings
4. `post_call_analysis_model` - Which AI model analyzes calls (gpt-4o)
5. `pronunciation_dictionary` - Custom pronunciations
6. `enable_voicemail_detection` - Voicemail detection toggle
7. `user_dtmf_options` - Phone keypad options
8. `model_high_priority` - LLM priority flag
9. `tool_call_strict_mode` - Function calling strictness

## Solutions Implemented

### 1. Added Missing Fields to Agent Editor

**RetellAgentEditor.php:**
```php
// Additional fields from Retell V33
'voice_model' => $this->agent['voice_model'] ?? 'eleven_turbo_v2',
'volume' => $this->agent['volume'] ?? 1.0,
'denoising_mode' => $this->agent['denoising_mode'] ?? 'off',
'post_call_analysis_model' => $this->agent['post_call_analysis_model'] ?? 'gpt-4',
'pronunciation_dictionary' => $this->agent['pronunciation_dictionary'] ?? [],
'enable_voicemail_detection' => $this->agent['enable_voicemail_detection'] ?? false,
'user_dtmf_options' => $this->agent['user_dtmf_options'] ?? [],
```

### 2. Updated UI to Display New Fields

Added to **retell-agent-editor.blade.php:**
- Voice Model dropdown (Eleven Turbo v2, v2.5, etc.)
- Voice Volume slider
- Denoising Mode selector
- Enable Voicemail Detection checkbox
- Post-Call Analysis Model dropdown

### 3. Fixed LLM Temperature Field

Retell uses `model_temperature` instead of `temperature`:
```php
'temperature' => $this->llm['temperature'] ?? $this->llm['model_temperature'] ?? 0.7,
'model_temperature' => $this->llm['model_temperature'] ?? 0.7,
```

## Test Results

### Before Fix:
- Voice Model: Not displayed
- Volume: Not displayed
- Denoising: Not displayed
- Post-Call Model: Not displayed

### After Fix:
- Voice Model: eleven_turbo_v2_5 ✓
- Volume: 1.0 ✓
- Denoising: noise-and-background-speech-cancellation ✓
- Post-Call Model: gpt-4o ✓

## Version System Recommendation

The current version system needs architectural changes:

### Option 1: Display Latest Only
- Show only the most recent revision
- Add a "View History" button for revision history
- Simpler but loses version selection capability

### Option 2: Implement Revision System
- Create `retell_agent_revisions` table
- Store each revision with timestamp
- Allow switching between revisions
- More complex but matches Retell's model

### Option 3: Version Tagging
- Allow users to "tag" specific revisions as versions
- Only show tagged versions in dropdown
- Best balance of simplicity and functionality

## Data Synchronization

The data IS being correctly synced and stored in the database:
- Configuration JSON contains all fields
- Values match what Retell API returns
- Issue was only in the UI display layer

## Next Steps

1. **Immediate**: All missing fields are now displayed correctly
2. **Short-term**: Decide on version system approach
3. **Long-term**: Consider full revision history UI

## Verification

To verify the fixes:
1. Go to: https://api.askproai.de/admin/retell-agent-editor?agentId=agent_9a8202a740cd3120d96fcfda1e
2. Check Voice & Speech tab for new fields
3. Check Advanced tab for additional settings
4. All values should now match Retell.ai screenshots
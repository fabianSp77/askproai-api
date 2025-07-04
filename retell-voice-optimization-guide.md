# Retell.ai Voice Optimization Guide for German

## Recommended Voice Settings for German

### Voice Selection
**Recommended German Voices (ElevenLabs)**:
1. **Matilda** (XrExE9yKIg1WjnnlVkGX) - Professional, clear, female voice
2. **Daniel** - Professional male voice
3. **Freya** - Friendly female voice

### Optimal Parameters for German

```json
{
  "voice_model": "elevenlabs",
  "voice_id": "XrExE9yKIg1WjnnlVkGX",
  "voice_temperature": 0.3,
  "voice_speed": 0.95,
  "volume": 1.0,
  "language": "de-DE"
}
```

### Parameter Explanations

#### Voice Temperature (0.3)
- Lower values (0.1-0.4) = More consistent, professional tone
- Higher values (0.5-1.0) = More varied, natural conversation
- **Recommendation**: 0.3 for appointment booking (clarity over naturalness)

#### Voice Speed (0.95)
- German speakers typically prefer slightly slower speech
- Range: 0.8-1.1 (1.0 = normal speed)
- **Recommendation**: 0.95 for clear understanding

#### Interruption Sensitivity (0.7)
- How easily the AI can be interrupted
- German conversation style: More patient, less interruption
- **Recommendation**: 0.7 (moderate sensitivity)

#### Responsiveness (0.8)
- How quickly AI responds after user stops speaking
- Balance between natural pauses and quick responses
- **Recommendation**: 0.8 for natural flow

### German Backchannel Configuration

```json
{
  "enable_backchannel": true,
  "backchannel_frequency": 0.6,
  "backchannel_words": [
    "ja",          // yes
    "genau",       // exactly
    "verstehe",    // I understand
    "okay",        // okay
    "aha",         // aha
    "mmh",         // mmh
    "gut",         // good
    "richtig",     // right
    "natürlich",   // of course
    "klar"         // clear/sure
  ]
}
```

### Silence and Timing Settings

```json
{
  "end_call_after_silence_ms": 15000,    // 15 seconds
  "reminder_trigger_ms": 8000,           // 8 seconds
  "reminder_max_count": 2,
  "max_call_duration_ms": 1800000        // 30 minutes
}
```

### Speech Normalization

```json
{
  "normalize_for_speech": true,
  "boosted_keywords": [
    "termin",
    "buchen",
    "verfügbar",
    "uhrzeit",
    "datum",
    "dienstleistung",
    "vereinbaren",
    "möglich",
    "passt"
  ]
}
```

## German Language Best Practices

### Date and Time Formatting
- Dates: "Montag, den 15. Januar" (not "January 15th")
- Times: "14:30 Uhr" or "halb drei" (not "2:30 PM")
- Relative: "übermorgen" (day after tomorrow), "nächste Woche"

### Professional German Phrases
```
Greeting:
"Guten Tag, hier ist [Company Name]. Wie kann ich Ihnen helfen?"

Appointment Inquiry:
"Für welche Dienstleistung möchten Sie einen Termin vereinbaren?"

Availability Check:
"Lassen Sie mich kurz die Verfügbarkeit prüfen..."

Confirmation:
"Perfekt! Ich habe für Sie einen Termin am [Date] um [Time] reserviert."

Closing:
"Vielen Dank für Ihren Anruf. Auf Wiederhören!"
```

### Common Issues and Solutions

#### Issue: Numbers spoken too fast
**Solution**: Add pauses in phone numbers
```
"+49 30... 123... 45... 67"
```

#### Issue: Compound words unclear
**Solution**: Slight pause in long compounds
```
"Zahnarzt...termin" instead of "Zahnarzttermin"
```

#### Issue: Regional dialects
**Solution**: Use standard High German (Hochdeutsch)

## Testing Voice Settings

### Test Scenarios
1. **Number Recognition**
   - Phone numbers with area codes
   - Dates in various formats
   - Time specifications

2. **Name Spelling**
   - German names with umlauts (ä, ö, ü)
   - Double letters (Mueller, Hoffmann)
   - Foreign names

3. **Interruption Handling**
   - User corrections
   - Questions during AI speech
   - Background noise

### Quality Metrics
- **Clarity Score**: 90%+ target
- **First-Call Resolution**: 85%+ target
- **Average Handle Time**: 2-4 minutes
- **Customer Satisfaction**: 4.5+ stars

## Environment Variables for Voice

```bash
# Add to .env file
RETELL_VOICE_MODEL=elevenlabs
RETELL_VOICE_ID=XrExE9yKIg1WjnnlVkGX
RETELL_VOICE_TEMPERATURE=0.3
RETELL_VOICE_SPEED=0.95
RETELL_LANGUAGE=de-DE
RETELL_INTERRUPTION_SENSITIVITY=0.7
RETELL_RESPONSIVENESS=0.8
```
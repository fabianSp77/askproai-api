# Retell.ai + Cal.com Integration Proposal
## Seamless Voice-to-Appointment Booking System

### Executive Summary

This proposal outlines the implementation strategy to connect Retell.ai agents with Cal.com for automated appointment booking through phone calls. The system already has all necessary components built - we need to configure and connect them properly.

---

## 1. Architecture Overview

```
┌─────────────────┐     ┌─────────────────┐     ┌─────────────────┐
│   Customer      │────▶│   Retell.ai     │────▶│   AskProAI      │
│   Phone Call    │     │   AI Agent      │     │   Backend       │
└─────────────────┘     └─────────────────┘     └─────────────────┘
                                │                         │
                                ▼                         ▼
                        ┌─────────────────┐     ┌─────────────────┐
                        │ Custom Functions │     │    Cal.com      │
                        │   (Webhooks)     │────▶│      API        │
                        └─────────────────┘     └─────────────────┘
```

---

## 2. Implementation Steps

### Step 1: Configure Retell Agent with Custom Functions

**Location**: Retell Ultimate Control Center  
**Path**: `/admin/retell-ultimate-control-center`

#### A. Register Custom Functions in Agent Configuration

```javascript
// Add to Retell Agent Configuration
{
  "custom_functions": [
    {
      "name": "sammle_kundendaten",
      "description": "Sammelt Name, Telefonnummer und E-Mail des Kunden",
      "url": "https://api.askproai.de/api/retell/collect-appointment",
      "method": "POST",
      "parameters": {
        "name": "string",
        "telefonnummer": "string",
        "email": "string (optional)"
      }
    },
    {
      "name": "pruefe_verfuegbarkeit",
      "description": "Prüft verfügbare Termine für eine Dienstleistung",
      "url": "https://api.askproai.de/api/retell/check-availability",
      "method": "POST",
      "parameters": {
        "datum": "string (DD.MM.YYYY)",
        "dienstleistung": "string"
      }
    },
    {
      "name": "buche_termin",
      "description": "Bucht einen Termin für den Kunden",
      "url": "https://api.askproai.de/api/retell/book-appointment",
      "method": "POST",
      "parameters": {
        "datum": "string (DD.MM.YYYY)",
        "uhrzeit": "string (HH:MM)",
        "dienstleistung": "string",
        "name": "string",
        "telefonnummer": "string",
        "email": "string (optional)",
        "notizen": "string (optional)"
      }
    }
  ]
}
```

#### B. Update Agent Prompt

```text
Du bist der freundliche Assistent von [Firmenname]. Deine Hauptaufgabe ist es, Kunden bei der Terminbuchung zu helfen.

TERMINBUCHUNGS-WORKFLOW:
1. Begrüße den Anrufer freundlich
2. Frage nach dem gewünschten Service/Dienstleistung
3. Verwende die Funktion "sammle_kundendaten" um Name und Kontaktdaten zu erfassen
4. Frage nach dem gewünschten Termin (Datum und ungefähre Uhrzeit)
5. Verwende "pruefe_verfuegbarkeit" um freie Termine zu prüfen
6. Schlage verfügbare Termine vor
7. Wenn der Kunde zustimmt, verwende "buche_termin" zur Buchung
8. Bestätige die Buchung und erwähne, dass eine E-Mail-Bestätigung folgt

WICHTIGE REGELN:
- Erfasse IMMER Name und Telefonnummer vor der Buchung
- Biete alternative Termine an, wenn der Wunschtermin nicht verfügbar ist
- Wiederhole die Buchungsdetails zur Bestätigung
- Sei geduldig und hilfsbereit bei älteren Kunden
```

### Step 2: Configure Service Type Mapping

Create a mapping between spoken services and Cal.com event types:

```php
// app/config/services.php
'service_mappings' => [
    // Spoken term => Cal.com event type slug
    'beratung' => 'consultation-30min',
    'erstberatung' => 'initial-consultation',
    'haarschnitt' => 'haircut-service',
    'massage' => 'massage-60min',
    'behandlung' => 'treatment-standard',
    // Add more as needed
]
```

### Step 3: Enhance Webhook Processing

Update the webhook handler to properly extract appointment data:

```php
// app/Services/Webhooks/RetellWebhookHandler.php
private function extractAppointmentData($webhookData)
{
    // Priority 1: Dynamic variables (if agent properly configured)
    if (isset($webhookData['retell_llm_dynamic_variables'])) {
        return $this->parseStructuredData($webhookData['retell_llm_dynamic_variables']);
    }
    
    // Priority 2: Custom analysis data
    if (isset($webhookData['custom_analysis_data'])) {
        return $this->parseAnalysisData($webhookData['custom_analysis_data']);
    }
    
    // Priority 3: Parse from transcript using NLP
    if (isset($webhookData['transcript'])) {
        return $this->parseTranscript($webhookData['transcript']);
    }
    
    return null;
}
```

### Step 4: Implement Real-Time Booking Flow

```php
// app/Http/Controllers/RetellCustomFunctionsController.php
public function bookAppointment(Request $request)
{
    $data = $request->all();
    
    try {
        // 1. Validate input
        $validated = $this->validateBookingData($data);
        
        // 2. Get or create customer
        $customer = $this->customerService->findOrCreate([
            'name' => $validated['name'],
            'phone' => $validated['telefonnummer'],
            'email' => $validated['email'] ?? null
        ]);
        
        // 3. Map service to Cal.com event type
        $eventTypeId = $this->mapServiceToEventType($validated['dienstleistung']);
        
        // 4. Create booking in Cal.com
        $booking = $this->calcomService->createBooking([
            'eventTypeId' => $eventTypeId,
            'start' => $this->parseGermanDateTime($validated['datum'], $validated['uhrzeit']),
            'responses' => [
                'name' => $customer->name,
                'email' => $customer->email,
                'phone' => $customer->phone,
                'notes' => $validated['notizen'] ?? ''
            ]
        ]);
        
        // 5. Save to local database
        $appointment = $this->appointmentService->create([
            'customer_id' => $customer->id,
            'calcom_booking_id' => $booking['id'],
            'start_time' => $booking['start'],
            'service_type' => $validated['dienstleistung'],
            'status' => 'scheduled'
        ]);
        
        // 6. Return voice-friendly response
        return response()->json([
            'success' => true,
            'message' => sprintf(
                "Perfekt! Ich habe Ihren Termin für %s am %s um %s Uhr gebucht. Sie erhalten eine Bestätigungsmail an %s.",
                $validated['dienstleistung'],
                $validated['datum'],
                $validated['uhrzeit'],
                $customer->email ?? 'Ihre hinterlegte E-Mail-Adresse'
            )
        ]);
        
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => $this->getVoiceFriendlyError($e)
        ]);
    }
}
```

### Step 5: Testing & Validation

#### A. Test Custom Functions
```bash
# Test availability check
curl -X POST https://api.askproai.de/api/retell/check-availability \
  -H "Content-Type: application/json" \
  -d '{
    "datum": "15.07.2025",
    "dienstleistung": "Beratung"
  }'

# Test appointment booking
curl -X POST https://api.askproai.de/api/retell/book-appointment \
  -H "Content-Type: application/json" \
  -d '{
    "datum": "15.07.2025",
    "uhrzeit": "14:00",
    "dienstleistung": "Beratung",
    "name": "Test Kunde",
    "telefonnummer": "+491234567890"
  }'
```

#### B. End-to-End Test Flow
1. Call the test number
2. Say: "Ich möchte einen Termin für eine Beratung buchen"
3. Provide test customer data
4. Confirm the booking
5. Verify appointment appears in Cal.com and database

---

## 3. Configuration Checklist

### Prerequisites
- [ ] Cal.com API key configured for company
- [ ] Retell.ai API key configured
- [ ] Phone number mapped to correct branch
- [ ] Cal.com event types synced
- [ ] Staff members linked to Cal.com

### Retell Agent Setup
- [ ] Custom functions registered
- [ ] Agent prompt updated for booking flow
- [ ] Dynamic variables configured
- [ ] Webhook URL set to: `https://api.askproai.de/api/retell/webhook`
- [ ] Test mode disabled for production

### System Configuration
- [ ] Service type mappings defined
- [ ] Default appointment duration set
- [ ] Time zone configured (Europe/Berlin)
- [ ] Email templates activated
- [ ] SMS notifications configured (optional)

---

## 4. Monitoring & Optimization

### Key Metrics to Track
1. **Booking Success Rate**: Successful bookings / Total booking attempts
2. **Average Booking Time**: Time from call start to booking confirmation
3. **Drop-off Points**: Where customers abandon the booking process
4. **Function Call Performance**: Response times for each custom function

### Dashboard Widgets
```php
// Add to Retell Ultimate Control Center
- Bookings Today
- Booking Success Rate
- Failed Booking Reasons
- Average Call Duration for Bookings
- Most Requested Services
```

---

## 5. Advanced Features (Phase 2)

### A. Multi-Language Support
```javascript
// Detect language and switch prompts
{
  "language_detection": true,
  "supported_languages": ["de", "en", "tr", "ar"]
}
```

### B. Smart Scheduling
- Suggest optimal times based on availability
- Consider travel time between appointments
- Buffer time for specific services

### C. Upselling & Cross-selling
- Suggest complementary services
- Remind about follow-up appointments
- Promote special offers

---

## 6. Implementation Timeline

### Week 1: Configuration
- Day 1-2: Configure Retell agents with custom functions
- Day 3-4: Test custom function endpoints
- Day 5: Update agent prompts and test calls

### Week 2: Integration Testing
- Day 1-2: End-to-end testing with test customers
- Day 3-4: Fix issues and optimize flow
- Day 5: Staff training and documentation

### Week 3: Go Live
- Day 1: Soft launch with limited hours
- Day 2-3: Monitor and adjust
- Day 4-5: Full production deployment

---

## 7. Troubleshooting Guide

### Common Issues

#### "Termin konnte nicht gebucht werden"
1. Check Cal.com API key validity
2. Verify event type exists and is active
3. Check staff availability settings
4. Review circuit breaker status

#### "Keine verfügbaren Termine"
1. Verify Cal.com calendar connection
2. Check working hours configuration
3. Ensure staff is assigned to event type
4. Review blocked dates/holidays

#### Customer data not captured
1. Check Retell dynamic variables configuration
2. Verify custom function URLs are accessible
3. Review webhook logs for errors
4. Check phone number resolution

---

## 8. Security Considerations

### Data Protection
- All customer data encrypted in transit and at rest
- PII handling complies with GDPR
- Call recordings stored securely with retention policies
- Access logs for all appointment data

### API Security
- Webhook signature verification enabled
- Rate limiting on all endpoints
- IP whitelisting for production
- Regular security audits

---

## Conclusion

The integration between Retell.ai and Cal.com is architecturally complete. By following this implementation guide, you can enable seamless voice-based appointment booking within 2-3 weeks. The system is designed to scale and can handle hundreds of concurrent booking requests while maintaining high reliability and user satisfaction.

**Next Steps:**
1. Configure Retell agent with custom functions
2. Test the booking flow with sample calls
3. Train staff on the new system
4. Monitor and optimize based on real usage

For support, contact the development team or refer to the technical documentation.
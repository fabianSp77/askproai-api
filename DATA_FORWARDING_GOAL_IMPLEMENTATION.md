# Data Forwarding Goal Template Implementation

## Date: 2025-07-05

## Overview
Successfully implemented a streamlined goal template for tracking data collection with consent and forwarding success. This template focuses on 4 essential metrics that measure the complete data flow from call reception to successful forwarding.

## Implementation Details

### 1. New Metric Types Added
In `app/Models/GoalMetric.php`:
- `TYPE_DATA_WITH_CONSENT = 'data_with_consent'` - Tracks calls where customer data was captured WITH explicit consent
- `TYPE_DATA_FORWARDED = 'data_forwarded'` - Tracks successfully forwarded data

### 2. New Calculation Methods
Added to `GoalMetric.php`:
- `calculateDataWithConsent()` - Counts calls with `consent_given = true` OR consent tracked in `retell_dynamic_variables`
- `calculateDataForwarded()` - Counts calls where data was successfully forwarded

### 3. New Funnel Step Types
In `app/Models/GoalFunnelStep.php`:
- `TYPE_CONSENT_GIVEN = 'consent_given'` - Consent step in the funnel
- `TYPE_DATA_FORWARDED = 'data_forwarded'` - Data forwarding step

### 4. Database Schema Updates
Migration: `2025_07_05_220033_add_consent_and_forwarding_to_calls_table.php`
- `consent_given` (boolean) - Tracks if consent was given
- `data_forwarded` (boolean) - Tracks if data was forwarded
- `consent_at` (timestamp) - When consent was given
- `forwarded_at` (timestamp) - When data was forwarded

### 5. New Goal Template
Added to `app/Services/GoalService.php`:

```php
[
    'type' => 'data_forwarding_focus',
    'name' => 'Datenerfassung & Weiterleitung',
    'description' => 'Maximale Datenerfassung mit Zustimmung und erfolgreicher Weiterleitung',
    'default_duration' => 30,
    'metrics' => [
        'Anrufe angeboten' (calls_received),
        'Anrufe angenommen' (calls_answered),
        'Daten mit Zustimmung erfasst' (data_with_consent) - PRIMARY,
        'Erfolgreich weitergeleitet' (data_forwarded)
    ],
    'funnel_steps' => [
        'Anruf angeboten',
        'Anruf angenommen',
        'Zustimmung erhalten',
        'Daten weitergeleitet'
    ]
]
```

## The 4 Core Metrics

### 1. **Anrufe angeboten** (Calls Offered)
- Type: `calls_received`
- Tracks: All incoming calls that reach the system
- Suggested Target: 1000 calls/month

### 2. **Anrufe angenommen** (Calls Answered)
- Type: `calls_answered`
- Tracks: Successfully connected calls (not missed)
- Suggested Target: 900 calls/month (90% answer rate)

### 3. **Daten mit Zustimmung erfasst** (Data Captured with Consent) - PRIMARY
- Type: `data_with_consent`
- Tracks: Calls where customer data AND explicit consent were captured
- Suggested Target: 675 calls/month (75% of answered calls)

### 4. **Erfolgreich weitergeleitet** (Successfully Forwarded)
- Type: `data_forwarded`
- Tracks: Data successfully transmitted to target system
- Suggested Target: 640 calls/month (95% of captured data)

## Conversion Funnel Visualization

```
Anrufe angeboten (1000) ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
                    ↓ 90%
Anrufe angenommen (900) ━━━━━━━━━━━━━━━━━━━━━━━━━━
                    ↓ 75%  
Zustimmung erhalten (675) ━━━━━━━━━━━━━━━━━━━━
                    ↓ 95%
Erfolgreich weitergeleitet (640) ━━━━━━━━━━

Total Conversion: 64% (640/1000)
```

## Usage

### Creating a Goal with this Template:

1. Navigate to Analytics → Ziele → Konfiguration
2. Click "Neues Ziel"
3. Click "Vorlage verwenden"
4. Select "Datenerfassung & Weiterleitung"
5. Adjust targets based on your business needs
6. Save

### Tracking Consent in Calls:

The system tracks consent in two ways:
1. Direct field: `calls.consent_given = true`
2. Dynamic variables: `retell_dynamic_variables->consent = 'true'`

### Integration with Retell.ai:

To automatically track consent, configure your Retell agent to:
1. Ask for explicit consent during the call
2. Set a dynamic variable `consent` to `true` when given
3. The system will automatically detect and track this

## Benefits

1. **Simple & Clear**: Only 4 essential metrics
2. **GDPR Compliant**: Explicit consent tracking
3. **Minimal Changes**: Uses existing infrastructure
4. **Immediately Usable**: Ready for all customers
5. **Flexible**: Works with any data forwarding system

## Testing

To test the implementation:
1. Create a new goal using the template
2. Make test calls and set `consent_given = true`
3. Mark some calls as `data_forwarded = true`
4. Check the goal progress in Dashboard and Analytics

## Future Enhancements

- Automatic consent detection from call transcripts
- Webhook integration for automatic forwarding status
- Consent rate optimization suggestions
- A/B testing for consent scripts
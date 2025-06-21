# AskProAI - Company/Branch/Service Setup Flow Documentation

## Overview

This document describes the complete setup flow for companies, branches, services, and their integration with Cal.com event types and Retell.ai agents.

## Architecture Overview

```
┌─────────────────┐
│    Company      │ (Multi-tenant root)
└────────┬────────┘
         │
    ┌────┴────┐
    │ Branches │ (Physical locations)
    └────┬────┘
         │
    ┌────┴────────────┬─────────────┬──────────────┐
    │                 │             │              │
┌───▼───┐      ┌─────▼─────┐ ┌────▼────┐  ┌─────▼─────┐
│ Staff │      │ Services  │ │ Cal.com │  │ Retell.ai │
└───────┘      └───────────┘ │  Event  │  │   Agent   │
                             │  Types  │  └───────────┘
                             └─────────┘
```

## 1. Company Setup

### Step 1.1: Create Company
```php
$company = Company::create([
    'name' => 'Beispiel Friseursalon GmbH',
    'email' => 'info@beispiel-salon.de',
    'phone_number' => '+49 30 12345678',
    'address' => 'Beispielstraße 123',
    'city' => 'Berlin',
    'postal_code' => '10115',
    'country' => 'DE',
    'timezone' => 'Europe/Berlin',
    'currency' => 'EUR',
    'is_active' => true,
    
    // Integration Keys
    'calcom_api_key' => env('CALCOM_API_KEY'),
    'retell_api_key' => env('RETELL_API_KEY'),
    'calcom_team_slug' => 'beispiel-salon',
    
    // Settings
    'settings' => [
        'booking_buffer_minutes' => 15,
        'max_advance_booking_days' => 60,
        'cancellation_hours' => 24,
        'reminder_hours' => 24,
    ]
]);
```

### Step 1.2: Configure Company Settings
- **Booking Rules**: Buffer time, advance booking limits
- **Cancellation Policy**: Hours required for cancellation
- **Notification Settings**: Email/SMS preferences
- **Business Hours**: Default hours for all branches

## 2. Branch Setup

### Step 2.1: Create Branch
```php
$branch = Branch::create([
    'company_id' => $company->id,
    'name' => 'Hauptfiliale Berlin',
    'phone_number' => '+49 30 12345678', // CRITICAL: Used for routing
    'email' => 'berlin@beispiel-salon.de',
    'address' => 'Kurfürstendamm 100',
    'city' => 'Berlin',
    'postal_code' => '10709',
    'is_active' => true,
    'is_main' => true,
    
    // Cal.com Integration
    'calcom_event_type_id' => null, // Set after event type creation
    'calcom_user_id' => null, // Optional: specific user
    
    // Retell.ai Integration
    'retell_agent_id' => null, // Set after agent provisioning
    
    // Business Hours
    'business_hours' => [
        'monday' => ['09:00-18:00'],
        'tuesday' => ['09:00-18:00'],
        'wednesday' => ['09:00-18:00'],
        'thursday' => ['09:00-20:00'],
        'friday' => ['09:00-20:00'],
        'saturday' => ['10:00-16:00'],
        'sunday' => null, // Closed
    ]
]);
```

### Step 2.2: Phone Number Routing
**CRITICAL**: The branch phone number is used to route incoming calls:
1. Customer calls branch phone number
2. Retell.ai webhook includes `to_number` field
3. System matches `to_number` to branch
4. Branch determines which Cal.com event type to use

## 3. Service Setup

### Step 3.1: Create Services
```php
$services = [
    [
        'company_id' => $company->id,
        'name' => 'Herrenhaarschnitt',
        'description' => 'Professioneller Haarschnitt für Herren',
        'duration' => 30,
        'price' => 25.00,
        'buffer_time' => 5,
        'is_active' => true,
    ],
    [
        'company_id' => $company->id,
        'name' => 'Damenhaarschnitt',
        'description' => 'Haarschnitt und Styling für Damen',
        'duration' => 45,
        'price' => 35.00,
        'buffer_time' => 10,
        'is_active' => true,
    ],
    [
        'company_id' => $company->id,
        'name' => 'Färben',
        'description' => 'Professionelle Haarfärbung',
        'duration' => 90,
        'price' => 65.00,
        'buffer_time' => 15,
        'is_active' => true,
    ]
];

foreach ($services as $serviceData) {
    Service::create($serviceData);
}
```

### Step 3.2: Assign Services to Branches
```php
// Assign all services to main branch
$branch->services()->sync($company->services->pluck('id'));

// Or assign specific services with custom pricing
$branch->services()->attach($service->id, [
    'custom_price' => 30.00,
    'custom_duration' => 35,
    'is_available' => true
]);
```

## 4. Staff Setup

### Step 4.1: Create Staff Members
```php
$staff = Staff::create([
    'company_id' => $company->id,
    'branch_id' => $branch->id,
    'first_name' => 'Max',
    'last_name' => 'Mustermann',
    'email' => 'max@beispiel-salon.de',
    'phone' => '+49 151 12345678',
    'role' => 'stylist',
    'active' => true,
    
    // Cal.com Integration
    'calcom_user_id' => null, // If staff has own Cal.com account
    
    // Working Hours (can override branch hours)
    'working_hours' => [
        'monday' => ['09:00-17:00'],
        'tuesday' => ['09:00-17:00'],
        'wednesday' => ['09:00-17:00'],
        'thursday' => ['10:00-19:00'],
        'friday' => ['10:00-19:00'],
        'saturday' => null, // Day off
        'sunday' => null,
    ]
]);
```

### Step 4.2: Assign Services to Staff
```php
// Staff can perform specific services
$staff->services()->attach([
    $service1->id => ['duration_override' => null],
    $service2->id => ['duration_override' => 40], // Takes longer
]);
```

## 5. Cal.com Integration

### Step 5.1: Create Event Type in Cal.com
```bash
# Using Cal.com API or UI
POST https://api.cal.com/v2/event-types
{
    "title": "Termin bei Beispiel Salon Berlin",
    "slug": "beispiel-salon-berlin",
    "length": 30,
    "description": "Buchen Sie Ihren Friseurtermin",
    "locations": [{
        "type": "inPerson",
        "address": "Kurfürstendamm 100, 10709 Berlin"
    }],
    "teamId": 12345,
    "scheduleId": 67890
}
```

### Step 5.2: Link Event Type to Branch
```php
// Update branch with Cal.com event type ID
$branch->update([
    'calcom_event_type_id' => 2026361 // From Cal.com response
]);

// Or use the sync service
$syncService = new CalcomEventTypeSyncService();
$syncService->syncEventTypesForCompany($company);
```

### Step 5.3: Configure Availability
- Set up schedules in Cal.com
- Configure buffer times
- Set booking limits
- Define working hours

## 6. Retell.ai Integration

### Step 6.1: Provision Retell Agent
```php
$provisioner = new RetellAgentProvisioner();
$agent = $provisioner->provisionForBranch($branch);

// This creates a Retell agent with:
// - German language settings
// - Company-specific greeting
// - Service menu integration
// - Cal.com availability checking
```

### Step 6.2: Configure Agent Responses
The agent is configured with:
- Company greeting: "Guten Tag, {company_name}, wie kann ich Ihnen helfen?"
- Service listing from database
- Dynamic availability checking
- Appointment confirmation flow

### Step 6.3: Webhook Configuration
```php
// Retell sends webhooks to:
// POST https://api.askproai.de/api/retell/webhook

// Webhook includes:
{
    "event": "call_ended",
    "call_id": "unique-call-id",
    "call": {
        "to_number": "+49 30 12345678", // Used for branch routing
        "from_number": "+49 151 98765432",
        "retell_llm_dynamic_variables": {
            "booking_confirmed": true,
            "datum": "2025-06-25",
            "uhrzeit": "14:30",
            "dienstleistung": "Herrenhaarschnitt",
            "mitarbeiter": "Max Mustermann"
        }
    }
}
```

## 7. Complete Setup Flow

### Step 7.1: Quick Setup Wizard
```php
// Use the Quick Setup Wizard in Admin Panel
// Path: /admin/quick-setup-wizard

1. Enter company details
2. Add first branch with phone number
3. Import or create services
4. Add staff members
5. Connect Cal.com (API key + event type)
6. Provision Retell agent
7. Test with phone call
```

### Step 7.2: Validation Checklist
- [ ] Company has valid Cal.com API key
- [ ] Company has valid Retell API key
- [ ] Branch has unique phone number
- [ ] Branch has Cal.com event type ID
- [ ] Branch has active services
- [ ] Branch has at least one staff member
- [ ] Retell agent is provisioned
- [ ] Webhook endpoint is accessible
- [ ] Test call creates appointment

## 8. Data Flow: Phone Call to Appointment

### Step 8.1: Call Initiation
1. Customer calls branch phone number: `+49 30 12345678`
2. Retell.ai answers with agent configured for this number
3. Agent greets with company name and asks how to help

### Step 8.2: Service Selection
1. Customer requests service: "Ich möchte einen Herrenhaarschnitt"
2. Agent queries available services for branch
3. Confirms service details and duration

### Step 8.3: Availability Check
1. Customer suggests date/time: "Morgen um 15 Uhr"
2. Agent calls webhook with `check_availability = true`
3. System checks Cal.com for slot availability
4. Agent confirms or suggests alternatives

### Step 8.4: Appointment Creation
1. Customer confirms slot
2. Agent collects name/contact if needed
3. Call ends, webhook processes booking
4. System creates appointment in database
5. System creates booking in Cal.com
6. Customer receives confirmation email

## 9. Multi-Branch Considerations

### Different Phone Numbers
Each branch MUST have a unique phone number for proper routing:
```php
$branches = [
    ['name' => 'Berlin', 'phone' => '+49 30 12345678'],
    ['name' => 'München', 'phone' => '+49 89 98765432'],
    ['name' => 'Hamburg', 'phone' => '+49 40 11223344'],
];
```

### Shared Services
Services can be defined at company level and shared:
```php
// Company-wide service
$service = Service::create([
    'company_id' => $company->id,
    'name' => 'Standard Haarschnitt',
    'duration' => 30,
    'price' => 25.00
]);

// Branch-specific pricing
$branch1->services()->attach($service->id, ['custom_price' => 28.00]);
$branch2->services()->attach($service->id, ['custom_price' => 23.00]);
```

### Staff Mobility
Staff can work at multiple branches:
```php
$staff->branches()->attach([
    $branch1->id => ['is_primary' => true],
    $branch2->id => ['is_primary' => false, 'days' => ['monday', 'wednesday']]
]);
```

## 10. Troubleshooting

### Common Issues

#### "No company found for phone number"
- Check branch phone number matches exactly
- Ensure branch is active (`is_active = true`)
- Verify phone number format (+49...)

#### "No Cal.com event type configured"
- Check `calcom_event_type_id` is set on branch
- Verify event type exists in Cal.com
- Ensure Cal.com API key is valid

#### "Retell agent not responding"
- Check `retell_agent_id` is set
- Verify Retell API key is valid
- Check webhook URL is accessible

#### "Appointments not syncing to Cal.com"
- Verify Cal.com API key permissions
- Check event type allows API bookings
- Review Cal.com rate limits

## 11. Best Practices

### Phone Number Management
1. Use consistent format: `+49 XX XXXXXXXX`
2. Never reuse phone numbers between branches
3. Update Retell agent when changing numbers
4. Test routing after any phone changes

### Service Configuration
1. Keep service names clear and unique
2. Set realistic durations with buffer time
3. Review and update prices regularly
4. Disable rather than delete old services

### Staff Management
1. Assign primary branch for each staff
2. Keep working hours up to date
3. Manage service skills accurately
4. Handle staff leave with availability blocks

### Integration Maintenance
1. Monitor webhook success rates
2. Check Cal.com sync status daily
3. Review failed bookings weekly
4. Keep API keys secure and rotated

## 12. Advanced Features

### Custom Booking Rules
```php
$branch->booking_rules = [
    'min_advance_hours' => 2,
    'max_advance_days' => 30,
    'buffer_between_bookings' => 15,
    'simultaneous_bookings' => 3,
    'require_deposit' => ['service_ids' => [1, 2], 'amount' => 20]
];
```

### Dynamic Pricing
```php
$branch->dynamic_pricing = [
    'peak_hours' => [
        'times' => ['17:00-19:00'],
        'multiplier' => 1.2
    ],
    'quiet_hours' => [
        'times' => ['09:00-11:00'],
        'multiplier' => 0.9
    ]
];
```

### Multi-Language Support
```php
$company->supported_languages = ['de', 'en', 'tr'];
$branch->default_language = 'de';
$staff->languages = ['de', 'en'];
```

This completes the comprehensive documentation of the Company/Branch/Service setup flow in AskProAI.
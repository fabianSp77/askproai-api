# Cal.com Event Types Management Guide

## Overview

Event types in Cal.com represent different types of appointments or meetings that can be booked. In AskProAI, these are synchronized and mapped to branches and staff members to enable AI-powered phone booking.

## Architecture

```
Cal.com Event Type
       │
       ├─── Synced to ──→ CalcomEventType Model
       │                          │
       │                          ├─── Belongs to Branch
       │                          ├─── Assigned to Staff
       │                          └─── Used in Appointments
       │
       └─── Updated via ──→ Webhooks / API Sync
```

## Event Type Structure

### Database Schema

```sql
-- calcom_event_types table
CREATE TABLE calcom_event_types (
    id BIGINT PRIMARY KEY,
    company_id BIGINT NOT NULL,
    branch_id BIGINT,
    calcom_event_type_id INTEGER NOT NULL,
    staff_id BIGINT,
    title VARCHAR(255) NOT NULL,
    slug VARCHAR(255),
    description TEXT,
    length INTEGER DEFAULT 30,
    locations JSON,
    price DECIMAL(10,2) DEFAULT 0.00,
    currency VARCHAR(3) DEFAULT 'EUR',
    requires_confirmation BOOLEAN DEFAULT FALSE,
    disable_guests BOOLEAN DEFAULT FALSE,
    hide_calendar_notes BOOLEAN DEFAULT FALSE,
    minimum_booking_notice INTEGER DEFAULT 0,
    before_event_buffer INTEGER DEFAULT 0,
    after_event_buffer INTEGER DEFAULT 0,
    scheduling_type VARCHAR(255),
    recurring_event JSON,
    metadata JSON,
    is_active BOOLEAN DEFAULT TRUE,
    api_version VARCHAR(10) DEFAULT 'v2',
    v2_attributes JSON,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    
    FOREIGN KEY (company_id) REFERENCES companies(id),
    FOREIGN KEY (branch_id) REFERENCES branches(id),
    FOREIGN KEY (staff_id) REFERENCES staff(id),
    INDEX idx_company_active (company_id, is_active),
    INDEX idx_branch_active (branch_id, is_active),
    UNIQUE KEY unique_calcom_event_type (company_id, calcom_event_type_id)
);
```

### Model Relationships

```php
// app/Models/CalcomEventType.php
class CalcomEventType extends Model
{
    protected $casts = [
        'locations' => 'array',
        'recurring_event' => 'array',
        'metadata' => 'array',
        'v2_attributes' => 'array',
        'price' => 'decimal:2',
        'is_active' => 'boolean',
        'requires_confirmation' => 'boolean',
    ];
    
    public function company()
    {
        return $this->belongsTo(Company::class);
    }
    
    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }
    
    public function staff()
    {
        return $this->belongsTo(Staff::class);
    }
    
    public function appointments()
    {
        return $this->hasMany(Appointment::class, 'calcom_event_type_id', 'calcom_event_type_id');
    }
}
```

## Event Type Synchronization

### Automatic Sync Process

```bash
# Sync all event types for all companies
php artisan calcom:sync-event-types --all

# Sync for specific company
php artisan calcom:sync-event-types --company=1

# Sync for specific branch
php artisan calcom:sync-event-types --branch=5

# Force sync (ignore cache)
php artisan calcom:sync-event-types --force
```

### Sync Service Implementation

```php
// app/Services/CalcomEventTypeSyncService.php
class CalcomEventTypeSyncService
{
    public function syncEventTypes(Company $company): array
    {
        $calcomService = new CalcomV2Service($company);
        $eventTypes = $calcomService->getEventTypes();
        
        $synced = 0;
        $failed = 0;
        
        foreach ($eventTypes as $eventTypeData) {
            try {
                $this->syncSingleEventType($company, $eventTypeData);
                $synced++;
            } catch (\Exception $e) {
                Log::error('Failed to sync event type', [
                    'company_id' => $company->id,
                    'event_type_id' => $eventTypeData->id,
                    'error' => $e->getMessage()
                ]);
                $failed++;
            }
        }
        
        return [
            'synced' => $synced,
            'failed' => $failed,
            'total' => count($eventTypes)
        ];
    }
    
    protected function syncSingleEventType(Company $company, EventTypeDTO $eventType): CalcomEventType
    {
        return CalcomEventType::updateOrCreate(
            [
                'company_id' => $company->id,
                'calcom_event_type_id' => $eventType->id,
            ],
            [
                'title' => $eventType->title,
                'slug' => $eventType->slug,
                'description' => $eventType->description,
                'length' => $eventType->length,
                'locations' => $eventType->locations,
                'price' => $eventType->price,
                'currency' => $eventType->currency,
                'requires_confirmation' => $eventType->requiresConfirmation,
                'disable_guests' => $eventType->disableGuests ?? false,
                'hide_calendar_notes' => $eventType->hideCalendarNotes ?? false,
                'minimum_booking_notice' => $eventType->minimumBookingNotice ?? 0,
                'before_event_buffer' => $eventType->beforeEventBuffer ?? 0,
                'after_event_buffer' => $eventType->afterEventBuffer ?? 0,
                'scheduling_type' => $eventType->schedulingType ?? null,
                'recurring_event' => $eventType->recurringEvent,
                'metadata' => $eventType->metadata ?? [],
                'v2_attributes' => $eventType->toArray(),
                'api_version' => 'v2',
                'is_active' => true,
            ]
        );
    }
}
```

## Managing Event Types

### Creating Event Types

Event types should be created in Cal.com first, then synced:

1. **In Cal.com Dashboard:**
   - Navigate to Event Types
   - Click "New Event Type"
   - Configure details (name, duration, location, etc.)
   - Save

2. **In AskProAI:**
   ```bash
   # Sync the new event type
   php artisan calcom:sync-event-types --company=1
   ```

### Assigning to Branches

```php
// Via Admin Panel or Code
$eventType = CalcomEventType::find($id);
$eventType->update([
    'branch_id' => $branchId,
    'staff_id' => $staffId, // Optional: assign to specific staff
]);
```

### Configuring Event Type Settings

```php
// Update event type configuration
$eventType->update([
    // Scheduling settings
    'minimum_booking_notice' => 120, // 2 hours notice required
    'before_event_buffer' => 15,     // 15 min buffer before
    'after_event_buffer' => 15,      // 15 min buffer after
    
    // Availability windows
    'metadata' => [
        'booking_window_start' => 1,  // Can book 1 day ahead
        'booking_window_end' => 30,   // Up to 30 days ahead
        'time_slot_interval' => 30,   // 30 min slots
    ],
    
    // Location settings
    'locations' => [
        [
            'type' => 'inPerson',
            'address' => '123 Main St, Berlin',
            'displayLocation' => 'Our Berlin Office'
        ],
        [
            'type' => 'phone',
            'phoneNumber' => '+49 30 123456'
        ]
    ],
]);
```

## Multi-Location Support

### Branch-Specific Event Types

```php
// Each branch can have different event types
class Branch extends Model
{
    public function calcomEventTypes()
    {
        return $this->hasMany(CalcomEventType::class);
    }
    
    public function activeEventTypes()
    {
        return $this->calcomEventTypes()->where('is_active', true);
    }
}
```

### Location-Based Availability

```php
// Check availability for specific branch
$branch = Branch::find($branchId);
$eventTypes = $branch->activeEventTypes;

foreach ($eventTypes as $eventType) {
    $slots = $calcomService->getAvailableSlots(
        eventTypeId: $eventType->calcom_event_type_id,
        startDate: now(),
        endDate: now()->addDays(7),
        timeZone: $branch->timezone ?? 'Europe/Berlin'
    );
}
```

## Custom Fields & Forms

### Defining Custom Fields

```php
// In Cal.com event type settings
$customFields = [
    [
        'name' => 'insurance_provider',
        'type' => 'text',
        'label' => 'Krankenversicherung',
        'required' => true,
        'placeholder' => 'z.B. AOK, TK, Barmer'
    ],
    [
        'name' => 'first_visit',
        'type' => 'radio',
        'label' => 'Erstbesuch?',
        'required' => true,
        'options' => ['Ja', 'Nein']
    ],
    [
        'name' => 'symptoms',
        'type' => 'textarea',
        'label' => 'Beschwerden',
        'required' => false,
        'placeholder' => 'Bitte beschreiben Sie Ihre Symptome'
    ]
];
```

### Processing Custom Field Responses

```php
// When creating booking via API
$booking = $calcomService->createBooking([
    'eventTypeId' => $eventType->calcom_event_type_id,
    'start' => $slot->time,
    'responses' => [
        'name' => $customer->full_name,
        'email' => $customer->email,
        'phone' => $customer->phone_number,
        // Custom field responses
        'insurance_provider' => $formData['insurance'] ?? '',
        'first_visit' => $formData['is_first_visit'] ? 'Ja' : 'Nein',
        'symptoms' => $formData['symptoms'] ?? ''
    ]
]);
```

## Event Type Templates

### Common Medical Practice Types

```php
// Seed common event types
$medicalEventTypes = [
    [
        'title' => 'Erstberatung',
        'slug' => 'erstberatung',
        'length' => 30,
        'price' => 0,
        'description' => 'Erste Konsultation für neue Patienten'
    ],
    [
        'title' => 'Nachuntersuchung',
        'slug' => 'nachuntersuchung',
        'length' => 15,
        'price' => 0,
        'description' => 'Kontrolltermin'
    ],
    [
        'title' => 'Ausführliche Beratung',
        'slug' => 'ausfuehrliche-beratung',
        'length' => 60,
        'price' => 80,
        'description' => 'Detaillierte Untersuchung und Beratung'
    ],
];
```

### Service Industry Types

```php
$serviceEventTypes = [
    [
        'title' => 'Haarschnitt Herren',
        'slug' => 'haarschnitt-herren',
        'length' => 30,
        'price' => 25,
        'locations' => [['type' => 'inPerson']]
    ],
    [
        'title' => 'Haarschnitt + Bart',
        'slug' => 'haarschnitt-bart',
        'length' => 45,
        'price' => 35,
        'locations' => [['type' => 'inPerson']]
    ],
];
```

## Admin Panel Management

### Filament Resource

```php
// app/Filament/Admin/Resources/CalcomEventTypeResource.php
class CalcomEventTypeResource extends Resource
{
    protected static ?string $model = CalcomEventType::class;
    
    public static function form(Form $form): Form
    {
        return $form->schema([
            Select::make('branch_id')
                ->label('Branch')
                ->relationship('branch', 'name')
                ->required(),
                
            Select::make('staff_id')
                ->label('Assigned Staff')
                ->relationship('staff', 'full_name')
                ->nullable(),
                
            Toggle::make('is_active')
                ->label('Active')
                ->default(true),
                
            TextInput::make('minimum_booking_notice')
                ->label('Minimum Booking Notice (minutes)')
                ->numeric()
                ->default(0),
                
            Section::make('Buffer Times')
                ->schema([
                    TextInput::make('before_event_buffer')
                        ->label('Before Event (minutes)')
                        ->numeric()
                        ->default(0),
                        
                    TextInput::make('after_event_buffer')
                        ->label('After Event (minutes)')
                        ->numeric()
                        ->default(0),
                ]),
        ]);
    }
}
```

## Troubleshooting Event Types

### Common Issues

#### 1. Event Types Not Syncing

```bash
# Check sync logs
tail -f storage/logs/calcom-sync.log

# Manual debug sync
php artisan calcom:debug-sync --company=1

# Verify API connection
php artisan calcom:test-connection
```

#### 2. Missing Event Types in Booking Flow

```php
// Check event type assignment
$branch = Branch::find($branchId);
$activeEventTypes = $branch->calcomEventTypes()
    ->where('is_active', true)
    ->get();

if ($activeEventTypes->isEmpty()) {
    Log::warning('No active event types for branch', [
        'branch_id' => $branchId
    ]);
}
```

#### 3. Custom Fields Not Appearing

```bash
# Re-sync to get latest custom fields
php artisan calcom:sync-event-types --force --company=1

# Check field mapping
php artisan tinker
>>> $et = CalcomEventType::find(1);
>>> $et->v2_attributes['customInputs'];
```

## Best Practices

### 1. Event Type Naming
- Use clear, descriptive names
- Include duration in title if relevant
- Use consistent naming across branches

### 2. Assignment Strategy
- Assign event types to branches, not companies
- Use staff assignment for specialist services
- Keep inactive legacy event types for history

### 3. Sync Frequency
- Daily automatic sync recommended
- Manual sync after Cal.com changes
- Monitor sync failures in logs

### 4. Performance
- Cache event types for 5 minutes
- Bulk load for multiple branches
- Use eager loading for relationships

## API Usage Examples

### Get Available Event Types for Branch

```php
use App\Models\Branch;
use App\Services\Calcom\CalcomV2Service;

$branch = Branch::find($branchId);
$eventTypes = $branch->calcomEventTypes()
    ->where('is_active', true)
    ->get();

// For AI phone system
$availableServices = $eventTypes->map(function ($eventType) {
    return [
        'id' => $eventType->id,
        'name' => $eventType->title,
        'duration' => $eventType->length,
        'price' => $eventType->price,
        'description' => $eventType->description,
    ];
});
```

### Check Availability for Event Type

```php
$calcomService = app(CalcomV2Service::class);

$availability = $calcomService->checkEventTypeAvailability(
    eventType: $eventType,
    requestedDate: '2025-01-20',
    preferredTime: '14:00',
    timezone: 'Europe/Berlin'
);

if ($availability->hasSlots()) {
    $nearestSlot = $availability->getNearestSlot();
    // Offer to customer via AI
}
```

## Monitoring & Metrics

### Key Metrics to Track

```php
// Event type usage
DB::table('appointments')
    ->select('calcom_event_type_id', DB::raw('count(*) as bookings'))
    ->groupBy('calcom_event_type_id')
    ->orderByDesc('bookings')
    ->get();

// Success rates by event type
$metrics = [
    'total_event_types' => CalcomEventType::count(),
    'active_event_types' => CalcomEventType::where('is_active', true)->count(),
    'event_types_with_bookings' => CalcomEventType::has('appointments')->count(),
    'avg_bookings_per_type' => CalcomEventType::withCount('appointments')->avg('appointments_count'),
];
```

### Monitoring Commands

```bash
# Event type statistics
php artisan calcom:event-type-stats

# Unused event types report
php artisan calcom:unused-event-types --days=30

# Sync health check
php artisan calcom:check-sync-health
```

## Integration with AI Phone System

### Providing Event Types to Retell AI

```php
// In Retell webhook handler
$branch = $this->determineBranchFromCall($phoneNumber);
$eventTypes = $branch->calcomEventTypes()
    ->where('is_active', true)
    ->get();

$context = [
    'available_services' => $eventTypes->map(fn($et) => [
        'name' => $et->title,
        'duration' => $et->length . ' Minuten',
        'price' => $et->price > 0 ? $et->price . ' EUR' : 'Kostenlos',
    ]),
    'booking_instructions' => 'Ask which service they need...',
];
```

## Support

- **Cal.com Event Types**: https://cal.com/docs/event-types
- **API Reference**: See [Cal.com V2 API Reference](./CALCOM_V2_API_REFERENCE.md)
- **Troubleshooting**: See [Troubleshooting Guide](./CALCOM_TROUBLESHOOTING_GUIDE.md)
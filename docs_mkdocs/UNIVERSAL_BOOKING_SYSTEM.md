# Universal Multi-Tenant, Multi-Location Booking System

## Overview

The Universal Booking System is a comprehensive solution for handling appointment bookings across multiple tenants (companies) and their various locations (branches). It intelligently routes customers to the most suitable branch and staff member based on various criteria including location, service requirements, availability, and preferences.

## Key Features

### 1. **Intelligent Phone Number Resolution**
- Maps incoming calls to specific branches or companies
- Multiple resolution methods with confidence scoring:
  - Metadata-based (100% confidence)
  - Phone number mapping (90% confidence)
  - Agent ID mapping (80% confidence)
  - Caller history (70% confidence)
  - Fallback resolution (30% confidence)

### 2. **Multi-Branch Support**
- Customers can book at any branch offering their required service
- Cross-branch availability checking
- Mobile staff who work at multiple locations
- Branch selection based on various strategies

### 3. **Smart Staff Matching**
- Skill-based matching for services
- Language preference support
- Experience and rating consideration
- Workload balancing
- Customer preference memory

### 4. **Flexible Branch Selection Strategies**
- **Nearest Location**: Prioritizes geographic proximity
- **First Available**: Books at branch with earliest slot
- **Load Balanced**: Distributes bookings evenly

## Architecture Components

### Core Services

#### UniversalBookingOrchestrator
The main orchestrator that coordinates the entire booking flow:
```php
$orchestrator = app(UniversalBookingOrchestrator::class);
$result = $orchestrator->processBookingRequest($bookingData, $context);
```

#### PhoneNumberResolver
Enhanced resolver for multi-location scenarios:
```php
$resolver = app(PhoneNumberResolver::class);
$context = $resolver->resolveFromWebhook($webhookData);
// Returns: branch_id, company_id, agent_id, resolution_method, confidence
```

#### StaffServiceMatcher
Intelligent staff selection based on requirements:
```php
$matcher = app(StaffServiceMatcher::class);
$eligibleStaff = $matcher->findEligibleStaff($branch, $serviceRequirements);
```

#### UnifiedAvailabilityService
Aggregates availability across branches and staff:
```php
$availability = app(UnifiedAvailabilityService::class);
$slots = $availability->getMultiBranchAvailability($branches, $requirements, $dateRange);
```

### Branch Selection Strategies

Strategies implement `BranchSelectionStrategyInterface`:

```php
interface BranchSelectionStrategyInterface
{
    public function selectBranches(array $branches, Customer $customer, array $serviceRequirements): array;
    public function getName(): string;
}
```

## Configuration

### Environment Variables

```env
# Branch Selection Strategy
BOOKING_BRANCH_STRATEGY=nearest  # Options: nearest, first_available, load_balanced

# Time Constraints
BOOKING_MIN_ADVANCE=60          # Minimum advance booking (minutes)
BOOKING_MAX_ADVANCE=90          # Maximum advance booking (days)
BOOKING_DEFAULT_DURATION=30     # Default appointment duration
BOOKING_BUFFER_TIME=15          # Buffer between appointments
BOOKING_SLOT_INTERVAL=15        # Time slot intervals

# Multi-Location
BOOKING_CROSS_BRANCH=true       # Enable cross-branch booking
BOOKING_MAX_DISTANCE=50         # Max distance for suggestions (km)
BOOKING_MOBILE_STAFF=true       # Consider mobile staff

# Customer Preferences
BOOKING_REMEMBER_BRANCH=true    # Remember preferred branch
BOOKING_REMEMBER_STAFF=true     # Remember preferred staff
BOOKING_PREFERENCE_WEIGHT=0.7   # Weight for preferences (0-1)
```

### Configuration File

Located at `config/booking.php`:

```php
return [
    'default_branch_strategy' => env('BOOKING_BRANCH_STRATEGY', 'nearest'),
    'time_constraints' => [
        'min_advance_booking' => 60,
        'max_advance_booking' => 90,
        'default_duration' => 30,
        'buffer_time' => 15,
        'slot_interval' => 15,
    ],
    'multi_location' => [
        'enable_cross_branch' => true,
        'max_distance_km' => 50,
        'enable_mobile_staff' => true,
    ],
    // ... more configuration
];
```

## Database Schema Enhancements

### Customer Preferences
```sql
customers
  - preferred_branch_id (UUID, nullable)
  - preferred_staff_id (UUID, nullable)
  - location_data (JSON - city, postal_code, coordinates)
```

### Staff Capabilities
```sql
staff
  - skills (JSON array)
  - languages (JSON array, default: ['de'])
  - mobility_radius_km (integer)
  - specializations (JSON array)
  - average_rating (decimal)
  - certifications (JSON array)
```

### Service Requirements
```sql
services
  - required_skills (JSON array)
  - required_certifications (JSON array)
  - complexity_level (enum: basic, intermediate, advanced, expert)
```

### Branch Features
```sql
branches
  - coordinates (JSON - lat, lng)
  - service_radius_km (integer)
  - accepts_walkins (boolean)
  - parking_available (boolean)
  - public_transport_access (text)
```

### Multi-Branch Staff
```sql
staff_branches (junction table)
  - staff_id
  - branch_id
  - is_primary (boolean)
  - working_days (JSON)
  - travel_compensation (decimal)
```

## Usage Examples

### Basic Booking Request

```php
$bookingRequest = [
    'company_id' => $companyId,
    'service_name' => 'Haircut',
    'date' => '2025-06-20',
    'time' => '14:00',
    'customer' => [
        'name' => 'John Doe',
        'phone' => '+49 30 12345678',
        'email' => 'john@example.com'
    ]
];

$result = $orchestrator->processBookingRequest($bookingRequest);

if ($result['success']) {
    $appointment = $result['appointment'];
    echo "Booked at {$appointment->branch->name} with {$appointment->staff->name}";
}
```

### With Specific Preferences

```php
$bookingRequest = [
    'company_id' => $companyId,
    'branch_id' => $preferredBranchId,  // Optional: specific branch
    'service_id' => $serviceId,
    'date' => '2025-06-20',
    'time' => '14:00',
    'staff_preference' => 'Maria',      // Optional: preferred staff name
    'language' => 'en',                 // Optional: language preference
    'customer' => [
        'name' => 'John Doe',
        'phone' => '+49 30 12345678'
    ]
];
```

### Phone Webhook Integration

```php
// In RetellWebhookHandler
$phoneResolver = app(PhoneNumberResolver::class);
$context = $phoneResolver->resolveFromWebhook($webhookData);

$bookingRequest = [
    'company_id' => $context['company_id'],
    'branch_id' => $context['branch_id'],
    // ... rest of booking data from webhook
];

$result = $orchestrator->processBookingRequest($bookingRequest, $context);
```

## Flow Diagram

```
Phone Call → Retell.ai
    ↓
Webhook → PhoneNumberResolver
    ↓
Resolve Company/Branch Context
    ↓
UniversalBookingOrchestrator
    ├── Find/Create Customer
    ├── Analyze Service Requirements
    ├── Select Suitable Branches (Strategy)
    ├── Find Eligible Staff (Matcher)
    ├── Check Availability (Unified Service)
    ├── Score & Select Optimal Option
    ├── Create Appointment
    ├── Sync with Cal.com
    └── Send Notifications
```

## Advanced Features

### Custom Branch Selection Strategy

```php
class PreferredPartnerStrategy implements BranchSelectionStrategyInterface
{
    public function selectBranches(array $branches, Customer $customer, array $serviceRequirements): array
    {
        // Custom logic to prioritize partner branches
        return array_filter($branches, function($branch) {
            return $branch->metadata['is_partner'] ?? false;
        });
    }
    
    public function getName(): string
    {
        return 'preferred_partner';
    }
}

// Register in service provider
$orchestrator->setBranchSelectionStrategy(new PreferredPartnerStrategy());
```

### Staff Skill Matching

```php
// Define service with requirements
$service->update([
    'required_skills' => ['color_specialist', 'senior_stylist'],
    'complexity_level' => 'advanced'
]);

// Staff with matching skills
$staff->update([
    'skills' => ['color_specialist', 'senior_stylist', 'makeup_artist'],
    'certifications' => ['Wella Color Expert', 'L\'Oreal Professional']
]);

// The system automatically matches staff to service requirements
```

### Multi-Language Support

```php
// Customer speaks Turkish
$bookingRequest['language'] = 'tr';

// System finds staff who speak Turkish
$staff->languages = ['de', 'en', 'tr'];  // Will be preferred
```

## Monitoring & Debugging

### Enable Debug Logging

```env
BOOKING_DEBUG=true
BOOKING_LOG_AVAILABILITY=true
BOOKING_LOG_BRANCHES=true
```

### Check Resolution Confidence

```php
Log::info('Phone resolution', [
    'method' => $context['resolution_method'],
    'confidence' => $context['confidence'],
    'branch_id' => $context['branch_id']
]);
```

### Availability Debugging

```php
$availabilityService->clearStaffAvailabilityCache($staff);
$slots = $availabilityService->getStaffAvailability($staff, $dateRange, 30);
Log::info('Available slots', ['count' => count($slots), 'slots' => $slots]);
```

## Performance Considerations

1. **Caching**: Availability data is cached for 5 minutes by default
2. **Eager Loading**: Relationships are loaded to prevent N+1 queries
3. **Async Processing**: Webhooks are processed asynchronously
4. **Connection Pooling**: Database connections are pooled
5. **Rate Limiting**: API endpoints have adaptive rate limiting

## Security Features

1. **Webhook Signature Verification**: All webhooks are verified
2. **Multi-Tenancy Isolation**: Data is automatically scoped by company
3. **Input Validation**: Phone numbers and other inputs are validated
4. **SQL Injection Prevention**: Using parameterized queries
5. **API Rate Limiting**: Prevents abuse and DDoS

## Future Enhancements

1. **AI-Powered Predictions**: Predict no-shows and optimize overbooking
2. **Dynamic Pricing**: Adjust prices based on demand and time
3. **Route Optimization**: For mobile service providers
4. **Customer Scoring**: Prioritize reliable customers
5. **Automated Rescheduling**: Handle cancellations intelligently
6. **Multi-Provider Marketplace**: Book across different companies

## Troubleshooting

### Common Issues

1. **"Could not resolve company"**
   - Check phone number mapping in `phone_numbers` table
   - Verify branch `phone_number` field
   - Check if branch is active

2. **"No eligible staff found"**
   - Verify staff is active and assigned to branch
   - Check service assignments in `staff_event_types`
   - Verify skill requirements match

3. **"No available slots"**
   - Check staff working hours
   - Verify Cal.com integration
   - Clear availability cache

### Debug Commands

```bash
# Test phone resolution
php artisan askproai:test-phone-resolution +49 30 12345678

# Check branch availability
php artisan askproai:check-availability --branch=UUID --date=2025-06-20

# Sync staff capabilities
php artisan askproai:sync-staff-capabilities

# Clear booking caches
php artisan booking:clear-cache
```

## API Endpoints

### Check Availability
```http
POST /api/booking/check-availability
{
    "company_id": "uuid",
    "service_id": "uuid",
    "date": "2025-06-20",
    "branch_id": "uuid" (optional)
}
```

### Create Booking
```http
POST /api/booking/create
{
    "company_id": "uuid",
    "service_id": "uuid",
    "date": "2025-06-20",
    "time": "14:00",
    "customer": {
        "name": "John Doe",
        "phone": "+49 30 12345678",
        "email": "john@example.com"
    },
    "preferences": {
        "branch_id": "uuid",
        "staff_name": "Maria",
        "language": "en"
    }
}
```

### Get Booking Options
```http
POST /api/booking/options
{
    "company_id": "uuid",
    "service_id": "uuid",
    "date_range": {
        "start": "2025-06-20",
        "end": "2025-06-27"
    },
    "location": {
        "postal_code": "10115",
        "city": "Berlin"
    }
}
```
# E2E Test Scenarios for Booking Flow

## Overview

This document describes all End-to-End test scenarios for the AskProAI booking flow with Cal.com V2 integration. These tests ensure the complete workflow from phone call to appointment confirmation works correctly.

## Test Architecture

### Test Files Structure
```
tests/E2E/
├── BookingFlowCalcomV2E2ETest.php      # Main booking flow tests
├── ConcurrentBookingStressTest.php     # Performance and concurrency tests
├── PhoneToAppointmentFlowTest.php      # Legacy flow tests (existing)
├── Helpers/
│   ├── WebhookPayloadBuilder.php       # Test data builder
│   └── AppointmentAssertions.php       # Reusable assertions
└── Mocks/
    └── MockCalcomV2Client.php          # Cal.com API mock
```

## Test Scenarios

### 1. Successful Booking Flow

#### Test: `complete_booking_flow_from_retell_webhook_to_confirmation_email`

**Scenario:** Customer calls, requests appointment, system books successfully

**Steps:**
1. Retell webhook received with call data
2. Call record created in database
3. Customer extracted/created from call data
4. Availability checked via Cal.com API
5. Appointment created in local database
6. Booking synced to Cal.com
7. Confirmation email sent
8. All relationships properly linked

**Validations:**
- Call status = 'completed'
- Customer record exists with correct data
- Appointment status = 'scheduled'
- Cal.com booking ID and UID present
- Email queued with correct recipient
- Activity log entry created
- Metrics recorded

### 2. Existing Customer Booking

#### Test: `handles_existing_customer_with_appointment_history`

**Scenario:** Returning customer books another appointment

**Steps:**
1. Existing customer with past appointments calls
2. System recognizes phone number
3. Uses existing customer record (no duplicate)
4. Creates new appointment
5. Updates customer statistics

**Validations:**
- No duplicate customer records
- Customer appointment count incremented
- Preferences preserved
- History maintained

### 3. No Availability Handling

#### Test: `handles_no_availability_scenario_gracefully`

**Scenario:** Customer requests time slot that's not available

**Steps:**
1. Customer requests specific time
2. Cal.com returns no available slots
3. System handles gracefully
4. Customer record created for follow-up
5. Call marked appropriately

**Validations:**
- No appointment created
- Call status = 'no_availability'
- Customer created for future contact
- Appropriate logging

### 4. Cal.com API Error Recovery

#### Test: `handles_calcom_api_errors_with_retry_logic`

**Scenario:** Cal.com API fails temporarily (rate limit)

**Steps:**
1. First API call fails with rate limit error
2. System waits and retries
3. Second attempt succeeds
4. Booking completed normally

**Validations:**
- Retry logic triggered
- Appointment eventually created
- Error logged with context
- Success after retry logged

### 5. Concurrent Booking Prevention

#### Test: `handles_concurrent_booking_attempts_safely`

**Scenario:** Two customers try to book same slot simultaneously

**Steps:**
1. Two webhooks received for same time slot
2. Both processed concurrently
3. First booking succeeds
4. Second booking fails gracefully

**Validations:**
- Only one appointment created
- First customer gets the slot
- Second call marked as 'booking_conflict'
- No data corruption

### 6. Invalid Webhook Data

#### Test: `validates_and_handles_invalid_webhook_data`

**Scenario:** Webhook contains invalid or missing data

**Test Cases:**
- Missing call_id
- Missing metadata
- Invalid phone number format
- Missing required fields

**Validations:**
- Returns 204 (prevent retries)
- Errors logged appropriately
- No database records created
- System remains stable

### 7. Complete Lifecycle Tracking

#### Test: `tracks_complete_appointment_lifecycle_with_proper_database_state`

**Scenario:** Verify all database relationships and states

**Validations:**
- Call → Customer relationship
- Call → Appointment relationship
- Appointment → All relationships (Customer, Staff, Service, Branch, Company)
- All timestamps correct
- All required fields populated

## Performance Test Scenarios

### 8. Concurrent Bookings Stress Test

#### Test: `handles_multiple_concurrent_booking_requests_for_same_slot`

**Scenario:** 10 simultaneous booking requests for one slot

**Metrics:**
- Only 1 successful booking
- 9 failures handled gracefully
- No deadlocks
- Consistent data state

### 9. Multi-Staff Distribution

#### Test: `stress_test_with_multiple_time_slots_and_staff`

**Scenario:** 20 bookings distributed across 3 staff members with 4 slots each

**Validations:**
- Appointments distributed evenly
- No double bookings per staff/time
- All staff utilized
- Maximum capacity not exceeded

### 10. Booking Speed Performance

#### Test: `performance_test_booking_creation_speed`

**Scenario:** Measure booking creation performance

**Benchmarks:**
- Average time < 100ms
- Max time < 200ms
- Median time < 50ms
- 100 iterations tested

### 11. Database Deadlock Handling

#### Test: `handles_database_deadlocks_gracefully`

**Scenario:** Simulate database lock conflicts

**Validations:**
- At least one transaction succeeds
- Data consistency maintained
- Appropriate retry behavior
- No data corruption

### 12. Cache Performance

#### Test: `cache_performance_under_concurrent_load`

**Scenario:** Cache behavior under load with invalidations

**Metrics:**
- Cache hit rate > 70%
- Graceful cache miss handling
- Proper invalidation on updates

## Test Data Patterns

### Customer Data
```php
- Name: "Max Mustermann", "Maria Schmidt", "Hans Mueller"
- Phone: "+4930XXXXXXXXX" format
- Email: "firstname.lastname@email.de"
```

### Service Types
```php
- "Kontrolluntersuchung" (30 min, €89)
- "Emergency Treatment" (60 min, €150)
- "Routine Checkup" (30 min, €75)
```

### Time Patterns
```php
- Next Monday 10:00 (default)
- Business hours: 08:00-18:00
- Lunch break: 12:00-13:00
- Emergency: ASAP
```

## Mock Configurations

### MockCalcomV2Client Features
- Request history tracking
- Configurable failure modes
- Response customization
- Performance simulation
- Automatic slot generation

### Failure Types
```php
- 'rate_limit' - 429 error
- 'validation' - 422 error
- 'not_found' - 404 error
- 'server_error' - 500 error
- 'timeout' - Connection timeout
```

## Running the Tests

### Full E2E Suite
```bash
php artisan test --testsuite=E2E
```

### Specific Scenarios
```bash
# Main booking flow
php artisan test tests/E2E/BookingFlowCalcomV2E2ETest.php

# Performance tests
php artisan test tests/E2E/ConcurrentBookingStressTest.php --filter=performance

# Concurrent bookings only
php artisan test tests/E2E/ConcurrentBookingStressTest.php --filter=concurrent
```

### With Coverage
```bash
php artisan test --coverage --testsuite=E2E --coverage-html=coverage-report
```

## Debugging Failed Tests

### Common Issues

1. **Timezone Mismatches**
   - Ensure Carbon::setTestNow() is used
   - Check timezone in assertions

2. **Database State**
   - Use RefreshDatabase trait
   - Check for leftover test data

3. **Mock Not Working**
   - Verify mock is bound to container
   - Check mock reset between tests

4. **Signature Validation**
   - Ensure test webhook secret matches
   - Check timestamp in signature

### Debug Output
```php
// Add to failing test
dump($appointment->toArray());
dump($this->mockCalcomClient->getRequestHistory());
dd(DB::table('calls')->get());
```

## CI/CD Integration

### Recommended GitHub Actions
```yaml
- name: Run E2E Tests
  run: |
    php artisan test --testsuite=E2E --parallel
    
- name: Upload Coverage
  uses: codecov/codecov-action@v3
  with:
    file: ./coverage.xml
```

## Future Test Scenarios

### Planned Tests
1. SMS notification delivery
2. Appointment reminders
3. Cancellation flow
4. Rescheduling flow
5. Multi-language support
6. Payment processing
7. Waitlist functionality
8. Group bookings

### Performance Targets
- 1000 concurrent users
- < 50ms API response time
- 99.9% uptime
- Zero data loss
# 🎯 Test Improvement Plan 2025 - AskProAI

## 📊 Current State (July 14, 2025)

### Achievements ✅
- **203 total tests** (up from 31)
- **~140 working tests**
- **12.12% class coverage**
- **19.29% line coverage**
- **Repository layer at 80% coverage**
- **CI/CD pipelines implemented**
- **Test documentation created**
- **Coverage analysis tools built**

### Key Issues 🔴
- No coverage driver installed (PCOV/Xdebug)
- Controllers barely tested (0.9%)
- Payment processing untested
- Authentication flows untested
- ~63 failing tests to fix

## 🚀 Phase 1: Foundation (Week 1)

### Day 1-2: Infrastructure
```bash
# Install PCOV
./scripts/setup-coverage.sh

# Fix remaining test failures
php artisan test --stop-on-failure
```

**Tasks:**
1. Install PCOV extension
2. Fix Event broadcasting issues in remaining tests
3. Fix TenantScope issues in repository tests
4. Ensure all 203 tests pass

### Day 3-4: Critical Business Logic
**Payment Processing Tests:**
```php
// tests/Feature/Payment/StripeServiceTest.php
class StripeServiceTest extends TestCase
{
    /** @test */
    public function it_creates_payment_intent()
    /** @test */
    public function it_handles_webhook_signatures()
    /** @test */
    public function it_processes_refunds()
}
```

**Authentication Tests:**
```php
// tests/Feature/Auth/AuthenticationTest.php
class AuthenticationTest extends TestCase
{
    /** @test */
    public function it_authenticates_portal_users()
    /** @test */
    public function it_handles_2fa_correctly()
    /** @test */
    public function it_enforces_permissions()
}
```

### Day 5: Controller Tests
**Priority Controllers:**
1. AuthController (login/logout/2FA)
2. CallController (webhook receiver)
3. AppointmentController (booking API)
4. StripeWebhookController

## 📈 Phase 2: Scale Up (Week 2)

### Target: 50% Coverage

**Service Layer Focus:**
- RetellService (phone AI)
- CalcomService (calendar)
- EmailService (notifications)
- AppointmentBookingService

**API Endpoint Tests:**
```php
// tests/Feature/API/BookingApiTest.php
public function test_booking_requires_authentication()
public function test_booking_validates_time_slots()
public function test_booking_prevents_double_booking()
public function test_booking_sends_confirmation()
```

**Webhook Tests:**
```php
// tests/Feature/Webhooks/RetellWebhookTest.php
public function test_webhook_requires_valid_signature()
public function test_webhook_creates_call_record()
public function test_webhook_triggers_appointment_creation()
```

## 🎯 Phase 3: Comprehensive Coverage (Week 3-4)

### Target: 75% Coverage

**E2E Test Scenarios:**
1. **Phone to Appointment Flow**
   - Customer calls
   - AI captures details
   - Appointment booked
   - Confirmation sent

2. **Customer Portal Flow**
   - Registration
   - Login
   - View appointments
   - Reschedule/Cancel

3. **Admin Management Flow**
   - Staff management
   - Service configuration
   - Report generation

**Performance Tests:**
```php
// tests/Performance/LoadTest.php
public function test_api_handles_concurrent_bookings()
public function test_webhook_processing_under_load()
public function test_database_query_performance()
```

## 🔧 Testing Best Practices

### Test Structure
```php
class ServiceTest extends TestCase
{
    use RefreshDatabase;
    
    protected function setUp(): void
    {
        parent::setUp();
        // Arrange: Set up test data
    }
    
    /** @test */
    public function it_performs_expected_behavior()
    {
        // Arrange
        $input = $this->createTestData();
        
        // Act
        $result = $this->service->process($input);
        
        // Assert
        $this->assertEquals('expected', $result);
        $this->assertDatabaseHas('table', ['field' => 'value']);
    }
}
```

### Mock External Services
```php
// Always mock external APIs
$this->mock(StripeService::class)
    ->shouldReceive('createPaymentIntent')
    ->once()
    ->andReturn($this->fakePaymentIntent());
```

### Use Factories
```php
// Create test data consistently
$customer = Customer::factory()
    ->has(Appointment::factory()->count(3))
    ->create();
```

## 📊 Metrics & Monitoring

### Weekly Targets
| Week | Coverage | Tests | Focus Area |
|------|----------|-------|------------|
| 1 | 25% | 250 | Critical business logic |
| 2 | 50% | 400 | Services & APIs |
| 3 | 65% | 500 | Controllers & E2E |
| 4 | 75% | 600 | Full coverage |

### Quality Gates
- No PR merged with <80% coverage for new code
- All tests must pass in CI/CD
- Performance tests must complete in <5 minutes
- No flaky tests allowed

## 🛠️ Tooling & Automation

### Coverage Reports
```bash
# Quick coverage check
./coverage-report.sh

# Full HTML report
./coverage-report.sh --html

# CI/CD integration
./coverage-report.sh --full
```

### Continuous Monitoring
```yaml
# .github/workflows/coverage.yml
- name: Upload coverage to Codecov
  uses: codecov/codecov-action@v3
  with:
    file: ./coverage/clover.xml
    fail_ci_if_error: true
```

### Test Helpers
```php
// tests/Helpers/TestHelpers.php
trait CreatesApplication
{
    protected function createAuthenticatedUser()
    protected function mockExternalServices()
    protected function assertEmailSent($class)
}
```

## 📋 Checklist for Success

### Week 1 ✓
- [ ] Install PCOV
- [ ] Fix all failing tests
- [ ] Add payment tests
- [ ] Add auth tests
- [ ] Achieve 25% coverage

### Week 2 ✓
- [ ] Test all services
- [ ] Test all API endpoints
- [ ] Add webhook tests
- [ ] Achieve 50% coverage

### Week 3 ✓
- [ ] Add E2E tests
- [ ] Test all controllers
- [ ] Add performance tests
- [ ] Achieve 65% coverage

### Week 4 ✓
- [ ] Fill coverage gaps
- [ ] Document test patterns
- [ ] Set up monitoring
- [ ] Achieve 75% coverage

## 🎉 Success Criteria

1. **75% overall test coverage**
2. **100% coverage for payment processing**
3. **All tests passing in <5 minutes**
4. **Zero flaky tests**
5. **Automated coverage reporting**
6. **Team trained on testing best practices**

---

*"Quality is not an act, it is a habit." - Aristotle*

Let's make comprehensive testing a habit at AskProAI! 🚀
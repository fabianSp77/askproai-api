# AskProAI Development Best Practices Guide

This guide outlines our team's coding standards, workflow practices, and quality guidelines. Following these practices ensures consistent, maintainable, and high-quality code across the project.

## 📋 Table of Contents

1. [Code Style & Standards](#code-style--standards)
2. [Git Workflow](#git-workflow)
3. [Testing Practices](#testing-practices)
4. [Documentation Standards](#documentation-standards)
5. [Security Practices](#security-practices)
6. [Performance Guidelines](#performance-guidelines)
7. [Debugging & Monitoring](#debugging--monitoring)
8. [Team Collaboration](#team-collaboration)

---

## 🎨 Code Style & Standards

### PHP/Laravel Standards

#### Follow PSR-12
```php
<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Log;

class UserService
{
    public function __construct(
        private readonly User $userModel
    ) {
    }

    public function createUser(array $data): User
    {
        // Implementation
    }
}
```

#### Use Type Declarations
```php
// ✅ Good
public function calculatePrice(float $amount, int $quantity): float
{
    return $amount * $quantity;
}

// ❌ Bad
public function calculatePrice($amount, $quantity)
{
    return $amount * $quantity;
}
```

#### Service Pattern
```php
// Services contain business logic
class AppointmentService
{
    public function __construct(
        private readonly CalcomService $calcom,
        private readonly NotificationService $notifications
    ) {
    }

    public function bookAppointment(array $data): Appointment
    {
        DB::transaction(function () use ($data) {
            // Business logic here
            $appointment = Appointment::create($data);
            $this->calcom->createEvent($appointment);
            $this->notifications->sendConfirmation($appointment);
            
            return $appointment;
        });
    }
}
```

#### Repository Pattern (When Needed)
```php
class AppointmentRepository
{
    public function findAvailableSlots(Carbon $date, int $branchId): Collection
    {
        return Appointment::query()
            ->where('branch_id', $branchId)
            ->whereDate('start_time', $date)
            ->where('status', 'available')
            ->get();
    }
}
```

### Frontend Standards

#### Component Structure
```javascript
// Use consistent component structure
export default {
    name: 'ComponentName',
    
    props: {
        title: {
            type: String,
            required: true
        }
    },
    
    data() {
        return {
            // Component state
        }
    },
    
    computed: {
        // Computed properties
    },
    
    methods: {
        // Methods in logical order
    },
    
    mounted() {
        // Initialization
    }
}
```

### Database Standards

#### Migrations
```php
// Always include down() method
public function up(): void
{
    Schema::create('appointments', function (Blueprint $table) {
        $table->id();
        $table->foreignId('company_id')->constrained()->cascadeOnDelete();
        $table->string('status', 20)->default('scheduled');
        $table->index(['company_id', 'status']);
        $table->timestamps();
    });
}

public function down(): void
{
    Schema::dropIfExists('appointments');
}
```

---

## 🔄 Git Workflow

### Branch Naming
```bash
feature/add-payment-integration
bugfix/fix-appointment-timezone
hotfix/critical-security-patch
refactor/optimize-call-processing
docs/update-api-documentation
```

### Commit Messages

Follow [Conventional Commits](https://www.conventionalcommits.org/):

```bash
# Format: <type>(<scope>): <subject>

feat(appointments): add recurring appointment support
fix(webhooks): handle missing signature header
docs(api): update authentication examples
refactor(services): extract notification logic
test(appointments): add edge case coverage
perf(queries): optimize appointment lookup
```

### Pull Request Process

1. **Create feature branch**
   ```bash
   git checkout -b feature/your-feature
   ```

2. **Make atomic commits**
   ```bash
   git add -p  # Stage changes selectively
   git commit -m "feat: add specific feature"
   ```

3. **Keep branch updated**
   ```bash
   git fetch origin
   git rebase origin/main
   ```

4. **Push and create PR**
   ```bash
   git push origin feature/your-feature
   ```

5. **PR Description Template**
   ```markdown
   ## Description
   Brief description of changes
   
   ## Type of Change
   - [ ] Bug fix
   - [ ] New feature
   - [ ] Breaking change
   - [ ] Documentation update
   
   ## Testing
   - [ ] Unit tests pass
   - [ ] Integration tests pass
   - [ ] Manual testing completed
   
   ## Checklist
   - [ ] Code follows style guidelines
   - [ ] Self-review completed
   - [ ] Documentation updated
   - [ ] No new warnings
   ```

---

## 🧪 Testing Practices

### Test Organization
```
tests/
├── Unit/           # Isolated unit tests
├── Feature/        # Feature tests with HTTP
├── Integration/    # Service integration tests
└── E2E/           # End-to-end workflows
```

### Writing Good Tests

#### Descriptive Test Names
```php
// ✅ Good
public function test_appointment_cannot_be_booked_in_the_past()
{
    // Test implementation
}

// ❌ Bad
public function testAppointment()
{
    // Unclear what this tests
}
```

#### Arrange-Act-Assert Pattern
```php
public function test_user_can_cancel_appointment()
{
    // Arrange
    $user = User::factory()->create();
    $appointment = Appointment::factory()
        ->for($user)
        ->create(['status' => 'scheduled']);
    
    // Act
    $response = $this->actingAs($user)
        ->delete("/api/appointments/{$appointment->id}");
    
    // Assert
    $response->assertStatus(200);
    $this->assertDatabaseHas('appointments', [
        'id' => $appointment->id,
        'status' => 'cancelled'
    ]);
}
```

#### Mock External Services
```php
public function test_webhook_processing()
{
    // Mock external service
    $this->mock(RetellService::class, function ($mock) {
        $mock->shouldReceive('getCall')
            ->once()
            ->andReturn(['duration' => 120]);
    });
    
    // Test webhook processing
    $response = $this->postJson('/webhooks/retell', $payload);
    
    $response->assertStatus(200);
}
```

### Test Coverage Goals
- Unit Tests: 80%+ coverage
- Integration Tests: Critical paths covered
- E2E Tests: Main user journeys

---

## 📝 Documentation Standards

### Code Documentation

#### Class Documentation
```php
/**
 * Handles appointment booking and scheduling logic.
 * 
 * This service coordinates between Cal.com and our internal
 * appointment system to ensure availability and prevent conflicts.
 *
 * @package App\Services
 */
class AppointmentService
{
    /**
     * Books a new appointment for the given customer.
     *
     * @param array $data Appointment data including time, service, staff
     * @param Customer $customer The customer making the booking
     * @return Appointment The created appointment
     * @throws AppointmentConflictException If time slot is taken
     * @throws InvalidTimeSlotException If time slot is invalid
     */
    public function bookAppointment(array $data, Customer $customer): Appointment
    {
        // Implementation
    }
}
```

#### Inline Comments
```php
// Only add comments for complex logic
public function calculateAvailability()
{
    // Get all appointments for the day
    $appointments = $this->getAppointments();
    
    // IMPORTANT: We need to account for timezone differences
    // between the customer and the branch location
    $appointments = $this->adjustForTimezone($appointments);
    
    // Complex availability calculation...
}
```

### API Documentation

#### Endpoint Documentation
```php
/**
 * @OA\Post(
 *     path="/api/appointments",
 *     summary="Create a new appointment",
 *     tags={"Appointments"},
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(ref="#/components/schemas/AppointmentRequest")
 *     ),
 *     @OA\Response(
 *         response=201,
 *         description="Appointment created successfully"
 *     )
 * )
 */
public function store(AppointmentRequest $request)
{
    // Implementation
}
```

### README Updates
- Update README when adding new features
- Include setup instructions for new dependencies
- Document environment variables

---

## 🔒 Security Practices

### Input Validation
```php
// Always validate input
public function store(Request $request)
{
    $validated = $request->validate([
        'email' => 'required|email|max:255',
        'phone' => 'required|regex:/^[0-9+\-\s]+$/|max:20',
        'date' => 'required|date|after:today',
    ]);
    
    // Process validated data
}
```

### SQL Injection Prevention
```php
// ✅ Good - Use query builder
$users = DB::table('users')
    ->where('email', $email)
    ->get();

// ✅ Good - Use Eloquent
$users = User::where('email', $email)->get();

// ❌ Bad - Never use raw queries with user input
$users = DB::select("SELECT * FROM users WHERE email = '$email'");
```

### Authentication & Authorization
```php
// Always check permissions
public function update(Request $request, Appointment $appointment)
{
    $this->authorize('update', $appointment);
    
    // Update logic
}
```

### Sensitive Data
```php
// Never log sensitive data
Log::info('User login', [
    'user_id' => $user->id,
    // 'password' => $request->password, // ❌ Never log passwords
]);

// Encrypt sensitive data
$user->api_key = Crypt::encryptString($apiKey);
```

---

## ⚡ Performance Guidelines

### Database Optimization

#### Eager Loading
```php
// ✅ Good - Eager load relationships
$appointments = Appointment::with(['customer', 'staff', 'service'])->get();

// ❌ Bad - N+1 queries
$appointments = Appointment::all();
foreach ($appointments as $appointment) {
    echo $appointment->customer->name; // Triggers query each time
}
```

#### Query Optimization
```php
// Use select to limit columns
$appointments = Appointment::select(['id', 'start_time', 'status'])
    ->where('branch_id', $branchId)
    ->get();

// Use chunks for large datasets
Appointment::chunk(1000, function ($appointments) {
    foreach ($appointments as $appointment) {
        // Process appointment
    }
});
```

### Caching Strategy
```php
// Cache expensive operations
$eventTypes = Cache::remember('event_types_' . $companyId, 300, function () {
    return EventType::where('company_id', $companyId)
        ->with('staff')
        ->get();
});
```

### Queue Usage
```php
// Use queues for time-consuming tasks
class SendAppointmentReminder implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;
    
    public function handle()
    {
        // Send reminder logic
    }
}

// Dispatch to queue
SendAppointmentReminder::dispatch($appointment)->delay(now()->addHours(24));
```

---

## 🔍 Debugging & Monitoring

### Logging Best Practices
```php
// Use appropriate log levels
Log::debug('Detailed debugging information', ['context' => $data]);
Log::info('Appointment created', ['appointment_id' => $appointment->id]);
Log::warning('API rate limit approaching', ['usage' => $usage]);
Log::error('Payment failed', ['error' => $e->getMessage()]);
```

### Structured Logging
```php
// Use consistent log structure
Log::info('appointment.created', [
    'appointment_id' => $appointment->id,
    'customer_id' => $customer->id,
    'service' => $service->name,
    'timestamp' => now()->toIso8601String(),
]);
```

### Error Handling
```php
try {
    $result = $this->processPayment($amount);
} catch (PaymentException $e) {
    Log::error('Payment processing failed', [
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString(),
        'context' => [
            'amount' => $amount,
            'customer_id' => $customerId,
        ],
    ]);
    
    // Handle error appropriately
    throw new UserFriendlyException('Payment could not be processed. Please try again.');
}
```

---

## 👥 Team Collaboration

### Code Reviews

#### What to Look For
1. **Functionality**: Does it solve the problem?
2. **Code Quality**: Is it readable and maintainable?
3. **Performance**: Are there any obvious bottlenecks?
4. **Security**: Are inputs validated? Auth checked?
5. **Tests**: Are edge cases covered?
6. **Documentation**: Is it updated?

#### Review Comments
```php
// Be constructive and specific
// ✅ Good: "Consider using eager loading here to avoid N+1 queries"
// ❌ Bad: "This is wrong"

// Suggest improvements
// "This works, but we could simplify using Collection::map()"

// Ask questions
// "I'm not familiar with this pattern. Could you explain the reasoning?"
```

### Communication

#### Daily Standups
- What I did yesterday
- What I'm doing today
- Any blockers

#### Documentation Updates
- Update docs as you code
- Create ADRs for architecture decisions
- Keep README current

#### Knowledge Sharing
- Write internal blog posts
- Give tech talks
- Pair program with teammates

---

## 📚 Resources

### Internal
- [CLAUDE.md](../../CLAUDE.md) - Main documentation
- [Quick Reference](../../CLAUDE_QUICK_REFERENCE.md)
- [Error Patterns](../../ERROR_PATTERNS.md)

### External
- [Laravel Best Practices](https://github.com/alexeymezenin/laravel-best-practices)
- [PHP The Right Way](https://phptherightway.com/)
- [Clean Code PHP](https://github.com/jupeter/clean-code-php)

### Tools
- **PHPStan**: Static analysis
- **PHP CS Fixer**: Code formatting
- **Rector**: Automated refactoring
- **Debugbar**: Development debugging

---

Remember: These are guidelines, not rigid rules. Use your judgment and discuss with the team when you need to deviate from these practices. The goal is maintainable, reliable code that serves our users well.
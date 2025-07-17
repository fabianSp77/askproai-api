# Testing Best Practices - AskProAI

## 📋 Inhaltsverzeichnis
- [Übersicht](#übersicht)
- [Test-Struktur](#test-struktur)
- [PHP Testing](#php-testing)
- [JavaScript/React Testing](#javascriptreact-testing)
- [API Testing](#api-testing)
- [Test-Strategien](#test-strategien)
- [CI/CD Integration](#cicd-integration)

## Übersicht

Dieses Dokument beschreibt die Best Practices für das Testing in der AskProAI Codebase. Wir verwenden eine Kombination aus PHPUnit für Backend-Tests und Vitest für Frontend-Tests.

### Test-Pyramide

```
         /\
        /  \  E2E Tests (10%)
       /    \ 
      /------\ Integration Tests (30%)
     /        \
    /----------\ Unit Tests (60%)
```

## Test-Struktur

```
tests/
├── Unit/                    # Unit Tests (isolierte Komponenten)
│   ├── components/         # React Component Tests
│   ├── hooks/             # Custom Hook Tests
│   ├── utils/             # Utility Function Tests
│   └── services/          # Service Class Tests
├── Integration/            # Integration Tests
│   ├── api/               # API Endpoint Tests
│   └── services/          # Service Integration Tests
├── E2E/                    # End-to-End Tests
├── Performance/            # Performance Tests
├── fixtures/              # Test-Daten
├── helpers/               # Test-Hilfsfunktionen
├── mocks/                 # API Mocks (MSW)
├── setup.ts               # Vitest Setup
├── test-utils.tsx         # React Testing Utilities
└── TESTING_BEST_PRACTICES.md
```

## PHP Testing

### 1. Unit Tests

**Datei-Benennung:** `*Test.php`

```php
<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\EmailService;
use App\Models\User;
use Illuminate\Support\Facades\Mail;

class EmailServiceTest extends TestCase
{
    private EmailService $emailService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->emailService = new EmailService();
    }

    /** @test */
    public function it_sends_welcome_email_to_new_user()
    {
        // Arrange
        Mail::fake();
        $user = User::factory()->create();

        // Act
        $this->emailService->sendWelcomeEmail($user);

        // Assert
        Mail::assertSent(WelcomeEmail::class, function ($mail) use ($user) {
            return $mail->hasTo($user->email);
        });
    }
}
```

### 2. Integration Tests

```php
<?php

namespace Tests\Integration\Services;

use Tests\TestCase;
use App\Services\BookingService;
use App\Models\Appointment;
use Illuminate\Foundation\Testing\RefreshDatabase;

class BookingServiceTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_creates_appointment_with_customer_and_sends_confirmation()
    {
        // Arrange
        $bookingService = app(BookingService::class);
        $data = [
            'customer_phone' => '+491234567890',
            'service_id' => 1,
            'datetime' => '2025-01-20 10:00:00',
        ];

        // Act
        $appointment = $bookingService->createAppointment($data);

        // Assert
        $this->assertDatabaseHas('appointments', [
            'id' => $appointment->id,
            'status' => 'scheduled',
        ]);
        $this->assertDatabaseHas('customers', [
            'phone' => '+491234567890',
        ]);
    }
}
```

### 3. Feature Tests

```php
<?php

namespace Tests\Feature\Api;

use Tests\TestCase;
use App\Models\PortalUser;
use Laravel\Sanctum\Sanctum;

class AppointmentApiTest extends TestCase
{
    /** @test */
    public function authenticated_user_can_list_appointments()
    {
        // Arrange
        $user = PortalUser::factory()->create();
        Sanctum::actingAs($user);

        // Act
        $response = $this->getJson('/portal/api/appointments');

        // Assert
        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'customer_name', 'appointment_datetime'],
                ],
                'meta' => ['current_page', 'total'],
            ]);
    }
}
```

## JavaScript/React Testing

### 1. Component Tests

**Datei-Benennung:** `*.test.tsx` oder `*.spec.tsx`

```tsx
import { describe, it, expect, vi } from 'vitest'
import { render, screen, fireEvent } from '@/tests/test-utils'
import { AppointmentList } from '@/components/AppointmentList'

describe('AppointmentList', () => {
  it('renders appointments correctly', () => {
    const appointments = [
      createMockAppointment({ id: 1, customer_name: 'John Doe' }),
      createMockAppointment({ id: 2, customer_name: 'Jane Smith' }),
    ]

    render(<AppointmentList appointments={appointments} />)

    expect(screen.getByText('John Doe')).toBeInTheDocument()
    expect(screen.getByText('Jane Smith')).toBeInTheDocument()
  })

  it('calls onDelete when delete button is clicked', async () => {
    const onDelete = vi.fn()
    const appointment = createMockAppointment()

    render(
      <AppointmentList 
        appointments={[appointment]} 
        onDelete={onDelete}
      />
    )

    const deleteButton = screen.getByRole('button', { name: /delete/i })
    await fireEvent.click(deleteButton)

    expect(onDelete).toHaveBeenCalledWith(appointment.id)
  })
})
```

### 2. Hook Tests

```tsx
import { renderHook, act } from '@testing-library/react'
import { useDebounce } from '@/hooks/useDebounce'

describe('useDebounce', () => {
  it('returns debounced value after delay', async () => {
    const { result, rerender } = renderHook(
      ({ value, delay }) => useDebounce(value, delay),
      { initialProps: { value: 'initial', delay: 500 } }
    )

    expect(result.current).toBe('initial')

    // Update value
    rerender({ value: 'updated', delay: 500 })
    
    // Value should not change immediately
    expect(result.current).toBe('initial')

    // Wait for debounce
    await act(async () => {
      await new Promise(resolve => setTimeout(resolve, 600))
    })

    expect(result.current).toBe('updated')
  })
})
```

### 3. Service Tests

```tsx
import { describe, it, expect, beforeEach, vi } from 'vitest'
import { ApiService } from '@/services/ApiService'

describe('ApiService', () => {
  let apiService: ApiService

  beforeEach(() => {
    apiService = new ApiService()
    vi.clearAllMocks()
  })

  it('handles successful API response', async () => {
    const mockData = { id: 1, name: 'Test' }
    global.fetch = vi.fn().mockResolvedValueOnce({
      ok: true,
      json: async () => mockData,
    })

    const result = await apiService.get('/test')

    expect(result).toEqual(mockData)
    expect(fetch).toHaveBeenCalledWith(
      expect.stringContaining('/test'),
      expect.objectContaining({
        method: 'GET',
      })
    )
  })

  it('handles API error', async () => {
    global.fetch = vi.fn().mockResolvedValueOnce({
      ok: false,
      status: 404,
      statusText: 'Not Found',
    })

    await expect(apiService.get('/not-found')).rejects.toThrow('Not Found')
  })
})
```

## API Testing

### 1. Postman Collections

Erstelle strukturierte Postman Collections:

```json
{
  "info": {
    "name": "AskProAI API Tests",
    "schema": "https://schema.getpostman.com/json/collection/v2.1.0/collection.json"
  },
  "item": [
    {
      "name": "Authentication",
      "item": [
        {
          "name": "Login",
          "request": {
            "method": "POST",
            "header": [],
            "body": {
              "mode": "raw",
              "raw": "{\n  \"email\": \"{{test_email}}\",\n  \"password\": \"{{test_password}}\"\n}"
            },
            "url": "{{base_url}}/portal/api/login"
          },
          "event": [
            {
              "listen": "test",
              "script": {
                "exec": [
                  "pm.test(\"Status code is 200\", function () {",
                  "    pm.response.to.have.status(200);",
                  "});",
                  "",
                  "pm.test(\"Response has token\", function () {",
                  "    var jsonData = pm.response.json();",
                  "    pm.expect(jsonData).to.have.property('token');",
                  "    pm.environment.set(\"auth_token\", jsonData.token);",
                  "});"
                ]
              }
            }
          ]
        }
      ]
    }
  ]
}
```

### 2. Newman Integration

```bash
# Run API tests
newman run tests/api/askproai-api-tests.json \
  -e tests/api/environments/local.json \
  --reporters cli,json \
  --reporter-json-export tests/api/results.json
```

## Test-Strategien

### 1. Test Naming Convention

- **PHP**: Use snake_case with descriptive names
  - `it_sends_email_when_appointment_is_created()`
  - `user_can_update_own_profile()`

- **JavaScript**: Use descriptive strings
  - `'renders loading state while fetching data'`
  - `'displays error message when API call fails'`

### 2. AAA Pattern (Arrange, Act, Assert)

```php
/** @test */
public function it_calculates_appointment_duration()
{
    // Arrange
    $start = Carbon::parse('2025-01-20 10:00:00');
    $end = Carbon::parse('2025-01-20 11:30:00');
    
    // Act
    $duration = $this->service->calculateDuration($start, $end);
    
    // Assert
    $this->assertEquals(90, $duration); // 90 minutes
}
```

### 3. Test Data Factories

```php
// PHP Factory
$appointment = Appointment::factory()
    ->for(Customer::factory())
    ->for(Staff::factory())
    ->scheduled()
    ->create();

// JavaScript Factory
const appointment = createMockAppointment({
  status: 'scheduled',
  customer_name: 'Test Customer',
});
```

### 4. Mocking Best Practices

```php
// PHP - Mock external services
$this->mock(RetellService::class, function ($mock) {
    $mock->shouldReceive('createCall')
        ->once()
        ->andReturn(['call_id' => 'test_123']);
});

// JavaScript - Mock API calls with MSW
server.use(
  http.get('/api/appointments', () => {
    return HttpResponse.json({ data: [] })
  })
)
```

## CI/CD Integration

### GitHub Actions Workflow

```yaml
name: Tests

on:
  push:
    branches: [main, develop]
  pull_request:
    branches: [main]

jobs:
  php-tests:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v3
      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.3'
      - name: Install dependencies
        run: composer install
      - name: Run PHP tests
        run: composer test:coverage
      - name: Upload coverage
        uses: codecov/codecov-action@v3

  js-tests:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v3
      - name: Setup Node
        uses: actions/setup-node@v3
        with:
          node-version: '20'
      - name: Install dependencies
        run: npm ci
      - name: Run JavaScript tests
        run: npm run test:coverage
      - name: Upload coverage
        uses: codecov/codecov-action@v3
```

## Test Coverage Requirements

- **Minimum Coverage**: 80%
- **Critical Paths**: 95%+ (Booking flow, Payment processing)
- **New Code**: 90%+ coverage required

## Performance Testing Guidelines

```javascript
// K6 Performance Test
import http from 'k6/http'
import { check } from 'k6'

export const options = {
  stages: [
    { duration: '30s', target: 20 },
    { duration: '1m', target: 100 },
    { duration: '30s', target: 0 },
  ],
  thresholds: {
    http_req_duration: ['p(95)<500'], // 95% of requests under 500ms
  },
}

export default function() {
  const res = http.get('https://api.askproai.de/portal/api/appointments')
  check(res, {
    'status is 200': (r) => r.status === 200,
    'response time < 500ms': (r) => r.timings.duration < 500,
  })
}
```

## Debugging Tests

### PHP Tests
```bash
# Run specific test
php artisan test --filter=EmailServiceTest

# Run with verbose output
php artisan test -v

# Debug with dd()
dd($variable); // In test code
```

### JavaScript Tests
```bash
# Run specific test file
npm run test resources/js/components/AppointmentList.test.tsx

# Run in watch mode
npm run test:watch

# Debug in browser
npm run test:ui
```

## Best Practices Checkliste

- [ ] Tests sind isoliert und unabhängig
- [ ] Keine hardcodierten Werte (use factories/fixtures)
- [ ] Aussagekräftige Test-Namen
- [ ] AAA Pattern befolgt
- [ ] Mocks werden korrekt aufgeräumt
- [ ] Tests laufen schnell (< 100ms für Unit Tests)
- [ ] Edge Cases werden getestet
- [ ] Error Handling wird getestet
- [ ] Tests dokumentieren das erwartete Verhalten
- [ ] Code Coverage Ziele werden erreicht
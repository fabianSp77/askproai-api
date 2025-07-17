# 📚 AskProAI Testing Playbook

Ein praktischer Leitfaden für Entwickler zur effektiven Nutzung der Test-Infrastruktur.

## 🎯 Quick Start

### Sofort loslegen
```bash
# Alle Dependencies installieren
npm install
composer install

# Schneller Test-Check
npm test                 # React Tests im Watch-Mode
php artisan test        # PHP Tests
npm run test:api        # API Tests

# Vollständige Test-Suite
npm run test:all        # Alles auf einmal
```

## 🧪 Test-Driven Development (TDD) Workflow

### 1. Neues Feature: Appointment Reminder Service

#### Schritt 1: Test zuerst schreiben
```php
// tests/Unit/Services/AppointmentReminderServiceTest.php
public function test_sends_reminder_24_hours_before_appointment()
{
    // Arrange
    $appointment = Appointment::factory()->create([
        'appointment_datetime' => now()->addHours(24),
        'reminder_sent' => false,
    ]);
    
    $service = new AppointmentReminderService();
    
    // Act
    $result = $service->sendReminder($appointment);
    
    // Assert
    $this->assertTrue($result);
    $this->assertTrue($appointment->fresh()->reminder_sent);
    Queue::assertPushed(SendReminderEmailJob::class);
}
```

#### Schritt 2: Minimal implementieren
```php
// app/Services/AppointmentReminderService.php
class AppointmentReminderService
{
    public function sendReminder(Appointment $appointment): bool
    {
        if ($appointment->reminder_sent) {
            return false;
        }
        
        SendReminderEmailJob::dispatch($appointment);
        
        $appointment->update(['reminder_sent' => true]);
        
        return true;
    }
}
```

#### Schritt 3: Refactoring
Nach dem der Test grün ist, Code verbessern ohne die Tests zu brechen.

## 🔍 Test-Szenarien nach Feature

### API Endpoint Testing

#### Neuer Endpoint: GET /api/appointments/upcoming
```php
// tests/Feature/API/AppointmentApiTest.php
public function test_can_get_upcoming_appointments()
{
    // Arrange: Testdaten erstellen
    $pastAppointment = Appointment::factory()->create([
        'company_id' => $this->company->id,
        'appointment_datetime' => now()->subDay(),
    ]);
    
    $futureAppointments = Appointment::factory()->count(3)->create([
        'company_id' => $this->company->id,
        'appointment_datetime' => now()->addDays(2),
    ]);
    
    // Act: API-Aufruf
    $response = $this->getJson('/api/appointments/upcoming');
    
    // Assert: Erwartungen prüfen
    $response->assertOk()
        ->assertJsonCount(3, 'data')
        ->assertJsonMissing(['id' => $pastAppointment->id]);
}
```

### React Component Testing

#### Neue Komponente: AppointmentReminderToggle
```jsx
// resources/js/__tests__/components/AppointmentReminderToggle.test.jsx
import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import AppointmentReminderToggle from '../../components/AppointmentReminderToggle';

describe('AppointmentReminderToggle', () => {
  it('toggles reminder setting when clicked', async () => {
    const user = userEvent.setup();
    const onToggle = vi.fn();
    
    render(
      <AppointmentReminderToggle 
        enabled={false} 
        onToggle={onToggle}
      />
    );
    
    const toggle = screen.getByRole('switch');
    expect(toggle).not.toBeChecked();
    
    await user.click(toggle);
    
    expect(onToggle).toHaveBeenCalledWith(true);
  });
  
  it('shows confirmation dialog for disabling reminders', async () => {
    const user = userEvent.setup();
    
    render(
      <AppointmentReminderToggle 
        enabled={true} 
        onToggle={() => {}}
      />
    );
    
    await user.click(screen.getByRole('switch'));
    
    expect(screen.getByText(/Erinnerungen wirklich deaktivieren?/i))
      .toBeInTheDocument();
  });
});
```

### Database Testing

#### Komplexe Query mit Eager Loading
```php
// tests/Unit/Database/AppointmentQueryTest.php
public function test_upcoming_appointments_query_avoids_n_plus_one()
{
    // Arrange
    $appointments = Appointment::factory()
        ->count(50)
        ->has(Customer::factory())
        ->has(Service::factory())
        ->create([
            'appointment_datetime' => now()->addDay(),
        ]);
    
    DB::enableQueryLog();
    
    // Act: Query mit Eager Loading
    $results = Appointment::upcoming()
        ->with(['customer', 'service', 'staff'])
        ->get();
    
    $queryCount = count(DB::getQueryLog());
    
    // Assert: Nur 4 Queries (1 + 3 für Relations)
    $this->assertEquals(4, $queryCount);
    $this->assertCount(50, $results);
}
```

## 🛠️ Debugging Test-Fehler

### 1. Test schlägt fehl - Was tun?

```bash
# Einzelnen Test mit mehr Details ausführen
php artisan test --filter=test_sends_reminder_24_hours_before_appointment -v

# JavaScript Test debuggen
npm test -- --reporter=verbose AppointmentReminderToggle

# Nur fehlgeschlagene Tests wiederholen
php artisan test --failed
```

### 2. Flaky Tests identifizieren

```php
// Test mehrmals ausführen
for i in {1..10}; do
  php artisan test --filter=problematic_test
done

// Oder in der Test-Klasse
public function test_potentially_flaky_operation()
{
    $this->retry(3, function () {
        // Test der manchmal fehlschlägt
    });
}
```

### 3. Test-Isolation Probleme

```php
// Sicherstellen dass Tests isoliert laufen
protected function setUp(): void
{
    parent::setUp();
    
    // Cache leeren
    Cache::flush();
    
    // Queue leeren
    Queue::fake();
    
    // Events mocken
    Event::fake();
}

protected function tearDown(): void
{
    // Cleanup
    Mockery::close();
    
    parent::tearDown();
}
```

## 📊 Coverage verbessern

### Coverage Report analysieren
```bash
# HTML Coverage Report generieren
npm run test:coverage
php artisan test --coverage-html=coverage

# Coverage für spezifische Datei
npm test -- --coverage --collectCoverageFrom="resources/js/components/AppointmentReminderToggle.jsx"
```

### Ungetestete Code-Pfade identifizieren
```php
// Beispiel: Edge Cases testen
public function test_handles_invalid_appointment_gracefully()
{
    $service = new AppointmentReminderService();
    
    // Null handling
    $this->assertFalse($service->sendReminder(null));
    
    // Bereits gesendete Erinnerung
    $sent = Appointment::factory()->create(['reminder_sent' => true]);
    $this->assertFalse($service->sendReminder($sent));
    
    // Vergangener Termin
    $past = Appointment::factory()->create([
        'appointment_datetime' => now()->subDay()
    ]);
    $this->assertFalse($service->sendReminder($past));
}
```

## 🚀 Performance Testing Best Practices

### Baseline etablieren
```javascript
// tests/Performance/k6/baseline.js
export const options = {
  stages: [
    { duration: '2m', target: 50 }, // Normal load
  ],
  thresholds: {
    http_req_duration: ['p(95)<300'], // Baseline: 300ms
  },
};

// Nach Optimierung: Neuen Baseline setzen
export const options = {
  thresholds: {
    http_req_duration: ['p(95)<200'], // Verbessert auf 200ms
  },
};
```

### Critical User Journeys testen
```javascript
// tests/Performance/k6/critical-paths.js
export default function () {
  group('Appointment Booking Flow', () => {
    // 1. Verfügbarkeit prüfen
    let availabilityRes = http.post('/api/appointments/check-availability', 
      JSON.stringify({ date: tomorrow, service_id: 1 }),
      params
    );
    check(availabilityRes, { 'availability loaded': r => r.status === 200 });
    
    // 2. Kunde anlegen
    let customerRes = http.post('/api/customers',
      JSON.stringify({ name: 'Test', phone: '+49123456789' }),
      params
    );
    
    // 3. Termin buchen
    let bookingRes = http.post('/api/appointments',
      JSON.stringify({
        customer_id: customerRes.json('data.id'),
        slot: availabilityRes.json('slots[0]'),
      }),
      params
    );
    
    check(bookingRes, { 
      'booking successful': r => r.status === 201,
      'booking fast': r => r.timings.duration < 500,
    });
  });
}
```

## 🔄 Continuous Integration

### Pre-Commit Hooks einrichten
```bash
# .git/hooks/pre-commit
#!/bin/sh
# Tests vor jedem Commit ausführen

# PHP Tests
php artisan test --parallel --stop-on-failure

# JavaScript Tests
npm test -- --run --passWithNoTests

# Linting
npm run lint:check
composer run phpstan
```

### Pull Request Checklist
```markdown
## PR Checklist
- [ ] Tests geschrieben/aktualisiert
- [ ] Alle Tests grün
- [ ] Coverage >= 80%
- [ ] Performance Tests durchgeführt (bei kritischen Änderungen)
- [ ] Dokumentation aktualisiert
```

## 🎯 Test-Patterns für häufige Szenarien

### 1. File Upload Testing
```php
public function test_can_upload_customer_import_file()
{
    Storage::fake('imports');
    
    $file = UploadedFile::fake()->create('customers.csv', 100);
    
    $response = $this->postJson('/api/customers/import', [
        'file' => $file,
    ]);
    
    $response->assertOk();
    Storage::disk('imports')->assertExists('customers.csv');
}
```

### 2. Email Testing
```php
public function test_sends_appointment_confirmation_email()
{
    Mail::fake();
    
    $appointment = Appointment::factory()->create();
    
    AppointmentConfirmationMail::send($appointment);
    
    Mail::assertSent(AppointmentConfirmationMail::class, function ($mail) use ($appointment) {
        return $mail->hasTo($appointment->customer->email) &&
               $mail->hasSubject('Terminbestätigung');
    });
}
```

### 3. Webhook Testing
```php
public function test_processes_payment_webhook()
{
    $payload = [
        'event' => 'payment.succeeded',
        'data' => ['amount' => 5000],
    ];
    
    $signature = hash_hmac('sha256', json_encode($payload), config('services.stripe.webhook_secret'));
    
    $response = $this->postJson('/api/webhooks/stripe', $payload, [
        'Stripe-Signature' => $signature,
    ]);
    
    $response->assertOk();
    Queue::assertPushed(ProcessPaymentJob::class);
}
```

## 📝 Test-Dokumentation

### Test-Namen aussagekräftig gestalten
```php
// ❌ Schlecht
public function test_reminder()

// ✅ Gut
public function test_sends_sms_reminder_one_hour_before_appointment_when_sms_preferred()
```

### Test-Struktur: Arrange-Act-Assert
```php
public function test_calculates_correct_appointment_duration()
{
    // Arrange: Setup
    $service = Service::factory()->create(['duration_minutes' => 30]);
    $addon = Service::factory()->create(['duration_minutes' => 15]);
    
    // Act: Ausführung
    $appointment = Appointment::create([
        'service_id' => $service->id,
        'addon_ids' => [$addon->id],
    ]);
    
    // Assert: Überprüfung
    $this->assertEquals(45, $appointment->total_duration_minutes);
}
```

## 🏆 Best Practices Zusammenfassung

1. **Test First**: Immer Tests vor Implementation schreiben
2. **One Assertion Per Test**: Fokussierte Tests schreiben
3. **Test Behavior, Not Implementation**: Was, nicht Wie testen
4. **Keep Tests Fast**: Schnelle Tests = häufigere Ausführung
5. **Maintain Test Code**: Tests wie Production Code behandeln
6. **Use Factories**: Konsistente Testdaten mit Factories
7. **Mock External Services**: Keine echten API-Calls in Tests
8. **Test Edge Cases**: Nicht nur Happy Path testen
9. **Document Why**: Komplexe Tests kommentieren
10. **Review Test Code**: Tests im Code Review prüfen
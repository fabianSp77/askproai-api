# 🎯 Action Plan: Nächste Schritte für Test-Infrastruktur

## 📊 Aktueller Status
- ✅ Schema-Probleme behoben (branches → companies)
- ✅ 26+ Tests funktionieren
- ❌ 100+ Tests warten auf Fixes
- ⚠️ PHPUnit 11 Deprecations überall

## 🚀 Quick Wins (2-4 Stunden) - SOFORT STARTEN

### 1. Mock Services erstellen (1 Stunde)
```php
// tests/Mocks/CalcomServiceMock.php
class CalcomServiceMock {
    public function getAvailableSlots() {
        return [
            ['start' => '2025-07-15 09:00', 'end' => '2025-07-15 10:00'],
            ['start' => '2025-07-15 14:00', 'end' => '2025-07-15 15:00'],
        ];
    }
    
    public function createBooking($data) {
        return [
            'id' => 'mock-booking-' . uniqid(),
            'status' => 'confirmed',
            'start' => $data['start'],
            'end' => $data['end']
        ];
    }
}

// tests/Mocks/StripeServiceMock.php
class StripeServiceMock {
    public function createCustomer($data) {
        return ['id' => 'cus_mock_' . uniqid()];
    }
    
    public function charge($amount, $customerId) {
        return ['id' => 'ch_mock_' . uniqid(), 'status' => 'succeeded'];
    }
}

// tests/Mocks/EmailServiceMock.php
class EmailServiceMock {
    public $sentEmails = [];
    
    public function send($to, $subject, $content) {
        $this->sentEmails[] = compact('to', 'subject', 'content');
        return true;
    }
}
```

**Erwartetes Ergebnis**: +30 Tests grün

### 2. PHPUnit 11 Bulk Fix (30 Minuten)
```bash
# Automatisches Update aller @test Annotations
find tests -name "*.php" -exec sed -i 's/@test/#[Test]/g' {} \;
find tests -name "*.php" -exec sed -i '1s/^/<?php\nuse PHPUnit\\Framework\\Attributes\\Test;\n/g' {} \;

# Namespace fixes
find tests -name "*Test.php" -exec sed -i '/use PHPUnit\\Framework\\Attributes\\Test;/d' {} \;
find tests -name "*Test.php" -exec sed -i '/namespace Tests/a use PHPUnit\\Framework\\Attributes\\Test;' {} \;
```

**Erwartetes Ergebnis**: Keine Deprecation Warnings mehr

### 3. Helper/Utility Tests aktivieren (1 Stunde)
```bash
# Tests ohne DB-Abhängigkeit identifizieren und ausführen
./vendor/bin/phpunit tests/Unit/Helpers --no-coverage
./vendor/bin/phpunit tests/Unit/Services/Validation --no-coverage
./vendor/bin/phpunit tests/Unit/Utils --no-coverage
```

**Erwartetes Ergebnis**: +20 Tests grün

### 4. Factory State Fixes (30 Minuten)
```php
// database/factories/CustomerFactory.php
public function definition() {
    return [
        'company_id' => Company::factory(),
        'name' => $this->faker->name,
        'email' => $this->faker->unique()->safeEmail,
        'phone' => '+49' . $this->faker->numerify('30#######'),
        'status' => 'active',
    ];
}

// Ähnliche Fixes für alle Factories
```

**Erwartetes Ergebnis**: +15 Tests grün

## 📈 Medium Tasks (1-2 Tage)

### 1. Service Container Bindings für Tests
```php
// tests/TestCase.php
protected function setUp(): void {
    parent::setUp();
    
    // Bind mocks for external services
    $this->app->bind(CalcomService::class, CalcomServiceMock::class);
    $this->app->bind(StripeService::class, StripeServiceMock::class);
    $this->app->bind(EmailService::class, EmailServiceMock::class);
}
```

### 2. Test Database Seeder
```php
// tests/DatabaseSeeder.php
class TestDatabaseSeeder {
    public function run() {
        // Basis-Daten für alle Tests
        $company = Company::factory()->create(['name' => 'Test Company']);
        $branch = Branch::factory()->create(['company_id' => $company->id]);
        $staff = Staff::factory()->count(3)->create(['company_id' => $company->id]);
        // etc.
    }
}
```

### 3. Integration Test Suite
```bash
# Separate Test-Suites konfigurieren
./vendor/bin/phpunit --testsuite=Unit --no-coverage
./vendor/bin/phpunit --testsuite=Feature --no-coverage
./vendor/bin/phpunit --testsuite=Integration --no-coverage
```

## 🎯 Messbare Ziele

### Tag 1 (Heute)
- [ ] Mock Services implementiert
- [ ] PHPUnit 11 Fixes applied
- [ ] 60+ Tests grün (von 26 → 60+)
- [ ] Test-Report generiert

### Tag 2
- [ ] Alle Unit Tests funktionieren
- [ ] 80+ Tests grün
- [ ] CI/CD Pipeline vorbereitet

### Tag 3
- [ ] Integration Tests funktionieren
- [ ] 100+ Tests grün
- [ ] Coverage > 60%

### Woche 1
- [ ] Alle 130 Tests grün
- [ ] Coverage > 80%
- [ ] CI/CD aktiv
- [ ] Performance Baseline etabliert

## 🔧 Konkrete Befehle zum Starten

```bash
# 1. Mock Services erstellen
mkdir -p tests/Mocks
touch tests/Mocks/{CalcomServiceMock,StripeServiceMock,EmailServiceMock}.php

# 2. PHPUnit Annotations fixen
php fix-phpunit-annotations.php

# 3. Quick Test Run
./vendor/bin/phpunit tests/Unit/Helpers --no-coverage

# 4. Progress Monitor
watch -n 5 'php artisan test --parallel | grep -E "Tests:|PASS|FAIL"'
```

## 📋 Priorisierte TODO-Liste

1. **JETZT**: CalcomServiceMock implementieren (blockiert 20+ Tests)
2. **JETZT**: StripeServiceMock implementieren (blockiert 15+ Tests)
3. **HEUTE**: PHPUnit 11 Bulk Fix ausführen
4. **HEUTE**: Helper Tests aktivieren
5. **MORGEN**: Integration Test Fixes
6. **DIESE WOCHE**: E2E Tests reparieren

## 💡 Pro-Tipps

1. **Parallel arbeiten**: Mocks können parallel zu anderen Fixes erstellt werden
2. **Incremental Progress**: Nach jedem Fix Tests laufen lassen
3. **Document Wins**: Jeden Fortschritt dokumentieren
4. **Ask for Help**: Bei Blockern sofort melden

## 🏁 Erfolgskriterien

- ✅ 60+ Tests bis Ende heute
- ✅ 80+ Tests bis morgen
- ✅ 100% Tests bis Ende der Woche
- ✅ CI/CD Pipeline aktiv
- ✅ Dokumentation vollständig
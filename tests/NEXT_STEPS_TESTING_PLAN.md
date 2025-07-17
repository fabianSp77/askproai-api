# Next Steps Testing Plan - Was jetzt?

## 🎯 Priorisierte Aufgaben

### 1. **Alle existierenden Tests ausführen** (HÖCHSTE PRIORITÄT)
```bash
# Schauen was funktioniert und was nicht
php artisan test --stop-on-failure

# JavaScript Tests
npm run test

# Coverage Report generieren
php artisan test --coverage
npm run test:coverage
```

### 2. **Kritische Business Logic Tests aktivieren**
Die wichtigsten Features die getestet werden müssen:

#### A. **Retell.ai Integration** 
- `tests/Feature/Retell/RetellWebhookTest.php`
- `tests/Integration/Retell/RetellServiceTest.php`
- Call Processing & Data Extraction

#### B. **Appointment Booking Flow**
- `tests/E2E/AppointmentBookingFlowTest.php`
- `tests/Feature/Appointments/AppointmentCreationTest.php`
- Customer → Call → Appointment

#### C. **Billing & Payments**
- `tests/Unit/Services/Stripe/EnhancedStripeInvoiceServiceTest.php`
- `tests/Feature/Billing/PrepaidBalanceTest.php`
- Stripe Webhook Processing

#### D. **Multi-Tenancy**
- `tests/Unit/TenantScopeTest.php`
- `tests/Feature/MultiTenancy/CompanyIsolationTest.php`
- Data Isolation zwischen Companies

### 3. **Performance Baseline etablieren**
```bash
# K6 Tests ausführen
k6 run tests/Performance/k6/load-test.js
k6 run tests/Performance/k6/stress-test.js

# Ergebnisse dokumentieren
echo "Baseline: $(date)" >> performance-baseline.md
```

### 4. **CI/CD Pipeline aktivieren**
1. GitHub Secrets setzen (siehe GITHUB_SECRETS_SETUP.md)
2. Test-Branch erstellen
3. Pull Request öffnen
4. Tests in GitHub Actions beobachten

### 5. **Test-Fehler systematisch fixen**
```bash
# Test-Fehler sammeln
php artisan test > test-errors.log 2>&1

# Nach Kategorien sortieren:
# - Missing Dependencies
# - Database Schema Issues  
# - Mock/Stub Problems
# - Assertion Failures
```

## 📊 Erwartete Probleme & Lösungen

### Problem 1: External Service Dependencies
**Symptom**: Tests schlagen fehl weil Cal.com/Retell.ai APIs aufgerufen werden
**Lösung**: 
```php
// Mock external services
$this->mock(RetellService::class)
    ->shouldReceive('getCall')
    ->andReturn($this->fakeCallData());
```

### Problem 2: Database State
**Symptom**: Tests beeinflussen sich gegenseitig
**Lösung**: 
- RefreshDatabase trait verwenden
- DatabaseTransactions für schnellere Tests

### Problem 3: Missing Test Data
**Symptom**: Factory/Seeder Fehler
**Lösung**:
```bash
# Factories generieren
php artisan make:factory ModelNameFactory

# Test-spezifische Seeder
php artisan make:seeder TestDataSeeder
```

## 🚀 Quick Wins (Sofort machbar)

### 1. Unit Tests für Models
```bash
php artisan test tests/Unit/Models/
```
Diese sollten ohne externe Dependencies laufen.

### 2. Helper Function Tests
```bash
php artisan test tests/Unit/Helpers/
```
Einfache, isolierte Funktionen.

### 3. Repository Tests
```bash
php artisan test tests/Unit/Repositories/
```
Mit In-Memory SQLite Database.

## 📈 Metriken & Ziele

### Woche 1 Ziele:
- [ ] 50 grüne Tests
- [ ] 20% Code Coverage
- [ ] CI/CD läuft bei jedem PR
- [ ] Performance Baseline dokumentiert

### Woche 2 Ziele:
- [ ] 100 grüne Tests
- [ ] 40% Code Coverage
- [ ] E2E Tests für kritische Flows
- [ ] Automatische Coverage Reports

### Monat 1 Ziel:
- [ ] 200+ grüne Tests
- [ ] 60% Code Coverage
- [ ] Alle kritischen Pfade getestet
- [ ] Zero-Downtime Deployments

## 🎯 Sofort-Aktion (JETZT MACHEN!)

```bash
# 1. Zeige mir welche Tests wir haben
find tests -name "*Test.php" | wc -l

# 2. Führe EINEN Test-Ordner aus
php artisan test tests/Unit/Models/ --stop-on-failure

# 3. Schaue was kaputt ist
tail -50 storage/logs/laravel.log

# 4. Fixe den ersten Fehler
# 5. Repeat
```

## 💡 Pro-Tipps

1. **Start Small**: Ein grüner Test ist besser als 100 rote
2. **Fix Forward**: Nicht alle Tests auf einmal fixen
3. **Document Failures**: Jeder Fehler = Learning
4. **Mock Early**: External Services immer mocken
5. **Test in Isolation**: Jeder Test muss alleine laufen können

---
Nächster Schritt: Führe `php artisan test tests/Unit/Models/CompanyTest.php` aus!
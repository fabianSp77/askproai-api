# 🚀 Action Plan - Next Steps (15.07.2025)

## 📋 Priorisierte Aufgaben

### 🔴 SOFORT (Nächste 30 Minuten)

#### 1. Frontend-Verifikation
```bash
# Öffne diese URLs und teste manuell:
1. https://api.askproai.de/admin/business-portal-admin
2. https://api.askproai.de/test-business-portal-fix.html

# In Browser Console:
window.emergencyFix.status()
```

**Checklist**:
- [ ] Company Dropdown funktioniert
- [ ] Portal Button funktioniert  
- [ ] Branch Selector funktioniert
- [ ] Keine JS-Errors in Console

#### 2. Quick Test Fixes
```bash
# Führe aus:
php quick-fix-tests.php
php analyze-test-errors.php

# Dann erneut testen:
php comprehensive-test-runner.php
```

### 🟡 HEUTE (Nächste 2-4 Stunden)

#### 1. Mock Services implementieren
```php
// tests/Mocks/CalcomServiceMock.php
class CalcomServiceMock {
    public function getAvailability() {
        return ['slots' => ['09:00', '10:00', '11:00']];
    }
}

// tests/Mocks/RetellServiceMock.php
class RetellServiceMock {
    public function createCall() {
        return ['call_id' => 'test_123', 'status' => 'completed'];
    }
}
```

#### 2. Test Helper Base Class
```php
// tests/TestHelpers/TestsWithMocks.php
trait TestsWithMocks {
    protected function mockCalcom() {
        $this->app->bind(CalcomService::class, CalcomServiceMock::class);
    }
    
    protected function mockRetell() {
        $this->app->bind(RetellService::class, RetellServiceMock::class);
    }
}
```

#### 3. Database Seeder für Tests
```bash
php artisan make:seeder TestDataSeeder
# Füge Test-Companies, Users, Appointments hinzu
```

### 🟢 DIESE WOCHE

#### 1. CI/CD Pipeline (GitHub Actions)
```yaml
# .github/workflows/tests.yml
name: Tests
on: [push, pull_request]
jobs:
  test:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v3
      - name: Setup PHP
        uses: shivammathur/setup-php@v2
      - name: Run Tests
        run: |
          composer install
          php artisan test --parallel
```

#### 2. Code Coverage Setup
```bash
# Aktiviere Coverage
./vendor/bin/phpunit --coverage-html coverage

# Oder mit Artisan
php artisan test --coverage --min=80
```

#### 3. E2E Test Automation
```javascript
// tests/e2e/business-portal.spec.js
describe('Business Portal', () => {
  it('should allow company selection', async () => {
    await page.goto('/admin/business-portal-admin');
    await page.select('select[wire:model="selectedCompanyId"]', '1');
    await expect(page).toHaveText('Company loaded');
  });
});
```

## 🎯 Metriken-Ziele

### Diese Woche:
- [ ] 500+ Tests
- [ ] <50 Errors
- [ ] 70%+ Success Rate
- [ ] 60%+ Code Coverage

### Diesen Monat:
- [ ] 1000+ Tests
- [ ] 0 Errors
- [ ] 95%+ Success Rate
- [ ] 80%+ Code Coverage
- [ ] CI/CD voll automatisiert

## 🛠️ Tools & Commands

### Monitoring:
```bash
# Live Test Status
watch -n 5 'php artisan test --parallel | grep -E "Tests:|Time:"'

# Error Analysis
./vendor/bin/phpunit --testdox-text=errors.txt 2>&1 | grep -E "Error|Failed"

# Coverage Report
php artisan test --coverage --min=80
```

### Quick Fixes:
```bash
# Fix all at once
php quick-fix-tests.php && \
php artisan optimize:clear && \
composer dump-autoload && \
php comprehensive-test-runner.php
```

## 📊 Erwartete Ergebnisse

Nach Implementierung dieser Schritte:
- Frontend: Alle Dropdowns/Buttons funktionieren
- Tests: 500+ aktiv, <50 Errors
- Coverage: 60%+ erreicht
- CI/CD: Automatische Tests bei jedem Push

## ⚡ Quick Win Opportunities

1. **Test Helpers** (+ ~50 Tests in 30 Min)
2. **Mock Services** (Reduziert ~100 Errors)
3. **Database Seeders** (Fixes E2E Tests)
4. **GitHub Actions** (Automatisierung)

---

**Nächster Schritt**: Öffne Business Portal Admin und verifiziere die Frontend-Fixes!
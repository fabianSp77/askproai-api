# Business Portal Regression Test Suite

## 🎯 Zweck
Diese Regression Test Suite stellt sicher, dass neue Änderungen keine bestehenden Funktionen beeinträchtigen. Sie sollte vor jedem Release durchgeführt werden.

---

## 📋 Test-Kategorien

### P0 - Kritische Funktionen (Muss vor Release getestet werden)
- [ ] Login/Logout
- [ ] Dashboard-Anzeige
- [ ] Anrufliste
- [ ] Anruf-Details
- [ ] Termine anzeigen (wenn Modul aktiv)
- [ ] Kundendaten anzeigen

### P1 - Wichtige Funktionen (Sollte vor Release getestet werden)
- [ ] Filter und Suche
- [ ] Daten-Export
- [ ] E-Mail-Versand
- [ ] Benachrichtigungen
- [ ] Team-Verwaltung
- [ ] Einstellungen speichern

### P2 - Zusätzliche Funktionen (Bei Zeit testen)
- [ ] Erweiterte Filter
- [ ] Bulk-Operationen
- [ ] API-Integrationen
- [ ] Performance-Optimierungen

---

## 🔄 Regression Test Cases

### RT-001: Authentication Flow
**Priorität**: P0  
**Bereich**: Login/Logout  
**Voraussetzung**: Test-User existiert

| Schritt | Aktion | Erwartetes Ergebnis | Status |
|---------|--------|-------------------|---------|
| 1 | Navigiere zu /business/login | Login-Seite wird angezeigt | [ ] |
| 2 | Gib gültige Credentials ein | - | [ ] |
| 3 | Klicke auf "Anmelden" | Weiterleitung zum Dashboard | [ ] |
| 4 | Prüfe URL | URL ist /business/dashboard | [ ] |
| 5 | Klicke auf Logout | Weiterleitung zur Login-Seite | [ ] |

---

### RT-002: Dashboard Widgets Loading
**Priorität**: P0  
**Bereich**: Dashboard  
**Voraussetzung**: Eingeloggt als Admin

| Schritt | Aktion | Erwartetes Ergebnis | Status |
|---------|--------|-------------------|---------|
| 1 | Lade Dashboard | Seite lädt ohne Fehler | [ ] |
| 2 | Prüfe Anruf-Widget | Zahlen werden angezeigt | [ ] |
| 3 | Prüfe Termin-Widget | Zahlen werden angezeigt | [ ] |
| 4 | Prüfe Recent Calls | Liste wird angezeigt | [ ] |
| 5 | Öffne Browser Console | Keine JS-Fehler | [ ] |

---

### RT-003: Call List Functionality
**Priorität**: P0  
**Bereich**: Anrufe  
**Voraussetzung**: Mindestens 10 Anrufe vorhanden

| Schritt | Aktion | Erwartetes Ergebnis | Status |
|---------|--------|-------------------|---------|
| 1 | Navigiere zu /business/calls | Anrufliste wird angezeigt | [ ] |
| 2 | Prüfe Tabellen-Header | Alle Spalten sichtbar | [ ] |
| 3 | Klicke auf Spalten-Sortierung | Sortierung funktioniert | [ ] |
| 4 | Nutze Pagination | Nächste Seite lädt | [ ] |
| 5 | Suche nach Telefonnummer | Ergebnisse werden gefiltert | [ ] |

---

### RT-004: Call Detail View
**Priorität**: P0  
**Bereich**: Anruf-Details  
**Voraussetzung**: Anruf mit Transkript vorhanden

| Schritt | Aktion | Erwartetes Ergebnis | Status |
|---------|--------|-------------------|---------|
| 1 | Klicke auf Anruf in Liste | Detail-Seite öffnet sich | [ ] |
| 2 | Prüfe Header-Informationen | Alle Daten vorhanden | [ ] |
| 3 | Prüfe Transkript-Anzeige | Text ist lesbar | [ ] |
| 4 | Teste Audio-Player | Audio wird abgespielt | [ ] |
| 5 | Klicke "Zurück zur Liste" | Rückkehr zur Liste | [ ] |

---

### RT-005: Customer Search
**Priorität**: P0  
**Bereich**: Kunden  
**Voraussetzung**: Mindestens 20 Kunden vorhanden

| Schritt | Aktion | Erwartetes Ergebnis | Status |
|---------|--------|-------------------|---------|
| 1 | Navigiere zu Kundenliste | Liste wird angezeigt | [ ] |
| 2 | Suche nach "Max" | Gefilterte Ergebnisse | [ ] |
| 3 | Lösche Suchbegriff | Alle Kunden wieder sichtbar | [ ] |
| 4 | Suche nach Telefonnummer | Kunde wird gefunden | [ ] |
| 5 | Klicke auf Kunden | Detail-Ansicht öffnet sich | [ ] |

---

### RT-006: Email Action from Call
**Priorität**: P1  
**Bereich**: E-Mail-Integration  
**Voraussetzung**: E-Mail-Templates konfiguriert

| Schritt | Aktion | Erwartetes Ergebnis | Status |
|---------|--------|-------------------|---------|
| 1 | Öffne Anruf-Detail | Seite lädt vollständig | [ ] |
| 2 | Klicke "E-Mail senden" | Modal öffnet sich | [ ] |
| 3 | Wähle Template | Template wird geladen | [ ] |
| 4 | Prüfe Variablen-Ersetzung | {{name}} wird ersetzt | [ ] |
| 5 | Sende E-Mail | Erfolgs-Meldung erscheint | [ ] |

---

### RT-007: Filter Persistence
**Priorität**: P1  
**Bereich**: Filter/Suche  
**Voraussetzung**: Verschiedene Filter verfügbar

| Schritt | Aktion | Erwartetes Ergebnis | Status |
|---------|--------|-------------------|---------|
| 1 | Setze Filter in Anrufliste | Filter wird angewendet | [ ] |
| 2 | Navigiere zu anderem Bereich | - | [ ] |
| 3 | Kehre zur Anrufliste zurück | Filter ist noch aktiv | [ ] |
| 4 | Refresh Browser (F5) | Filter bleibt erhalten | [ ] |
| 5 | Klicke "Filter zurücksetzen" | Alle Daten wieder sichtbar | [ ] |

---

### RT-008: Responsive Design Check
**Priorität**: P1  
**Bereich**: Mobile Ansicht  
**Voraussetzung**: Browser Developer Tools

| Schritt | Aktion | Erwartetes Ergebnis | Status |
|---------|--------|-------------------|---------|
| 1 | Öffne Developer Tools | Tools sind geöffnet | [ ] |
| 2 | Wähle Mobile View (iPhone) | Layout passt sich an | [ ] |
| 3 | Teste Navigation | Hamburger-Menu funktioniert | [ ] |
| 4 | Prüfe Tabellen | Horizontal scrollbar | [ ] |
| 5 | Teste Touch-Events | Buttons reagieren | [ ] |

---

### RT-009: Data Export
**Priorität**: P1  
**Bereich**: Export-Funktionen  
**Voraussetzung**: Daten in Liste vorhanden

| Schritt | Aktion | Erwartetes Ergebnis | Status |
|---------|--------|-------------------|---------|
| 1 | Öffne Anrufliste | Export-Buttons sichtbar | [ ] |
| 2 | Klicke "Export CSV" | Download startet | [ ] |
| 3 | Öffne CSV-Datei | Daten korrekt formatiert | [ ] |
| 4 | Klicke "Export PDF" | PDF wird generiert | [ ] |
| 5 | Prüfe PDF-Inhalt | Layout ist korrekt | [ ] |

---

### RT-010: Performance Under Load
**Priorität**: P2  
**Bereich**: Performance  
**Voraussetzung**: Große Datenmenge (1000+ Einträge)

| Schritt | Aktion | Erwartetes Ergebnis | Status |
|---------|--------|-------------------|---------|
| 1 | Öffne große Anrufliste | Lädt in < 3 Sekunden | [ ] |
| 2 | Scrolle schnell | Kein Lag/Stottern | [ ] |
| 3 | Sortiere nach Spalte | Sortierung < 1 Sekunde | [ ] |
| 4 | Nutze Suche | Ergebnisse < 1 Sekunde | [ ] |
| 5 | Wechsle Seiten schnell | Keine Fehler | [ ] |

---

## 🔄 Automatisierte Regression Tests

### Browser-Automatisierung mit Playwright

```javascript
// tests/e2e/regression/auth.spec.js
const { test, expect } = require('@playwright/test');

test.describe('Authentication Regression', () => {
  test('RT-001: Login and Logout Flow', async ({ page }) => {
    // Navigate to login
    await page.goto('/business/login');
    
    // Fill credentials
    await page.fill('input[name="email"]', 'admin@test-gmbh.de');
    await page.fill('input[name="password"]', 'Test123!');
    
    // Submit
    await page.click('button[type="submit"]');
    
    // Verify redirect
    await expect(page).toHaveURL('/business/dashboard');
    
    // Logout
    await page.click('[data-test="logout-button"]');
    await expect(page).toHaveURL('/business/login');
  });
});
```

### API Regression Tests

```php
// tests/Feature/Regression/CallApiRegressionTest.php
namespace Tests\Feature\Regression;

use Tests\TestCase;
use App\Models\User;
use App\Models\Call;

class CallApiRegressionTest extends TestCase
{
    /** @test */
    public function rt_003_call_list_returns_paginated_results()
    {
        $user = User::factory()->create();
        Call::factory()->count(25)->create(['company_id' => $user->company_id]);
        
        $response = $this->actingAs($user)
            ->getJson('/api/business/calls');
        
        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'phone_number', 'duration', 'status']
                ],
                'links',
                'meta'
            ])
            ->assertJsonCount(20, 'data'); // Default pagination
    }
    
    /** @test */
    public function rt_004_call_detail_shows_all_information()
    {
        $user = User::factory()->create();
        $call = Call::factory()->create([
            'company_id' => $user->company_id,
            'transcript' => [
                ['speaker' => 'agent', 'text' => 'Hallo'],
                ['speaker' => 'user', 'text' => 'Guten Tag']
            ]
        ]);
        
        $response = $this->actingAs($user)
            ->getJson("/api/business/calls/{$call->id}");
        
        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'phone_number',
                    'duration',
                    'transcript',
                    'summary',
                    'customer' => ['id', 'name']
                ]
            ]);
    }
}
```

### Performance Regression Tests

```php
// tests/Performance/RegressionBenchmark.php
namespace Tests\Performance;

use Tests\TestCase;
use Illuminate\Support\Facades\DB;

class RegressionBenchmark extends TestCase
{
    /** @test */
    public function dashboard_loads_within_acceptable_time()
    {
        $user = User::factory()->create();
        
        $start = microtime(true);
        
        $response = $this->actingAs($user)
            ->get('/business/dashboard');
        
        $loadTime = microtime(true) - $start;
        
        $this->assertLessThan(2.0, $loadTime, 'Dashboard took longer than 2 seconds to load');
        
        // Log for tracking
        Log::channel('performance')->info('Dashboard load time', [
            'time' => $loadTime,
            'queries' => DB::getQueryLog()
        ]);
    }
}
```

---

## 📊 Regression Test Report Template

```markdown
# Regression Test Report

**Release Version**: X.X.X  
**Test Date**: YYYY-MM-DD  
**Tester**: [Name]  
**Environment**: [Staging/Production]  

## Summary
- **Total Tests**: XX
- **Passed**: XX
- **Failed**: XX
- **Blocked**: XX
- **Not Tested**: XX

## Test Results

### P0 - Critical Functions
| Test ID | Test Case | Status | Notes |
|---------|-----------|---------|-------|
| RT-001 | Authentication | ✅ Pass | - |
| RT-002 | Dashboard | ✅ Pass | - |
| RT-003 | Call List | ❌ Fail | Pagination issue |

### P1 - Important Functions
| Test ID | Test Case | Status | Notes |
|---------|-----------|---------|-------|
| RT-006 | Email Actions | ✅ Pass | - |
| RT-007 | Filter Persistence | ⚠️ Blocked | Filter not available |

### Failed Tests Detail
1. **RT-003**: Pagination shows wrong count
   - Expected: 20 items
   - Actual: 25 items
   - Severity: Medium
   - Bug ID: #123

### Recommendations
- [ ] Fix pagination before release
- [ ] Re-test failed cases after fix
- [ ] Additional performance testing needed

### Sign-off
- QA Lead: _________ Date: _________
- Dev Lead: _________ Date: _________
- Product Owner: _________ Date: _________
```

---

## 🚀 Continuous Regression Testing

### GitHub Actions Workflow

```yaml
# .github/workflows/regression-tests.yml
name: Regression Test Suite

on:
  pull_request:
    branches: [main, develop]
  schedule:
    - cron: '0 2 * * *' # Nightly at 2 AM

jobs:
  regression-tests:
    runs-on: ubuntu-latest
    
    steps:
      - uses: actions/checkout@v3
      
      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.3'
          
      - name: Install Dependencies
        run: |
          composer install
          npm install
          
      - name: Run Regression Tests
        run: |
          php artisan test --testsuite=Regression
          npm run test:e2e:regression
          
      - name: Upload Test Results
        if: always()
        uses: actions/upload-artifact@v3
        with:
          name: regression-test-results
          path: tests/results/
```

---

## 📝 Best Practices

1. **Vor jedem Release**
   - Alle P0-Tests durchführen
   - Mindestens 80% der P1-Tests
   - Regression Test Report erstellen

2. **Nach kritischen Änderungen**
   - Betroffene Module komplett testen
   - Abhängige Features prüfen
   - Performance-Impact messen

3. **Test-Daten Management**
   - Regression-Test-Daten separat halten
   - Regelmäßig aktualisieren
   - Edge-Cases einbeziehen

4. **Automatisierung**
   - Kritische Flows automatisieren
   - Nightly Builds mit Regression Tests
   - Alerts bei Failures

5. **Dokumentation**
   - Neue Features = Neue Regression Tests
   - Test Cases aktuell halten
   - Failures dokumentieren
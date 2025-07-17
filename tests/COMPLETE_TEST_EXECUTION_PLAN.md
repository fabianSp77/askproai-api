# Complete Test Execution Plan - ULTRATHINK Strategy

## 🎯 Ziel: 100% der Tests grün bekommen

### Phase 1: Discovery & Kategorisierung (JETZT)
1. Alle Test-Dateien katalogisieren
2. Nach Komplexität sortieren
3. Dependencies identifizieren
4. Fehlertypen klassifizieren

### Phase 2: Foundation Tests (Einfachste zuerst)
- Unit Tests ohne externe Dependencies
- Helper & Utility Tests
- Model Tests (ohne Relationships)
- Repository Tests (mit Mocks)

### Phase 3: Integration Tests
- Service Tests mit Mocks
- API Endpoint Tests
- Webhook Tests
- Queue Job Tests

### Phase 4: Complex Tests
- E2E Tests
- Performance Tests
- Multi-Tenant Tests
- Payment Integration Tests

### Phase 5: Coverage & Optimization
- Coverage auf 80%+ bringen
- Slow Tests optimieren
- Flaky Tests stabilisieren
- CI/CD aktivieren

## Automatisierter Ansatz:
```bash
# Test Discovery Script
find tests -name "*Test.php" -type f | while read test; do
    echo "Testing: $test"
    php artisan test "$test" --stop-on-failure > "test-results/$(basename $test).log" 2>&1
    if [ $? -eq 0 ]; then
        echo "✅ PASSED: $test"
    else
        echo "❌ FAILED: $test"
        # Analyze failure
        grep -E "(Error:|Exception:|Failed asserting)" "test-results/$(basename $test).log" | head -5
    fi
done
```

## Erwartete Probleme & Lösungen:

### 1. Factory/Schema Mismatches
- **Problem**: Factories erstellen ungültige Daten
- **Lösung**: Factories an aktuelle Migrations anpassen

### 2. Missing Mocks
- **Problem**: Tests rufen echte APIs auf
- **Lösung**: Mock alle externen Services

### 3. Database State
- **Problem**: Tests beeinflussen sich
- **Lösung**: RefreshDatabase überall verwenden

### 4. Missing Dependencies
- **Problem**: Klassen/Traits nicht gefunden
- **Lösung**: Autoload regenerieren, fehlende Files erstellen

### 5. Deprecations
- **Problem**: PHPUnit 11 Syntax
- **Lösung**: @test → #[Test] Attribute

## Metriken:
- Total Tests: 130 files
- Aktuell Grün: 4 files
- Ziel heute: 50 files
- Ziel diese Woche: 130 files
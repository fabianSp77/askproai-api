# 🎯 Next Actions - Test Suite Expansion

## Sofort machbar (Quick Wins)

### 1. Fix CompanyFactory Slug Issue (5 Min)
```php
// In database/factories/CompanyFactory.php
'slug' => \Illuminate\Support\Str::slug($name . '-' . $this->faker->unique()->randomNumber(5)),
```
**Erwartung**: +10-15 Repository Tests

### 2. Fix Repository Type Hints (10 Min)
```php
// Change from:
public function getByStaff(int $staffId)
// To:
public function getByStaff(string|int $staffId)
```
**Erwartung**: +7 AppointmentRepository Tests

### 3. Enable More Service Tests (15 Min)
```bash
# Tests ohne externe Dependencies
./vendor/bin/phpunit tests/Unit/Services/Tax --no-coverage
./vendor/bin/phpunit tests/Unit/Services/Billing --no-coverage
```
**Erwartung**: +20 Service Tests

## Morgen Vormittag (3 Stunden)

### Phase 1: Repository Tests (1 Stunde)
1. Fix CallRepositoryTest - RefreshDatabase trait
2. Fix CustomerRepositoryTest - RefreshDatabase trait
3. Fix type hints für UUID support
4. **Ziel**: +76 Repository Tests

### Phase 2: Model Tests (1 Stunde)
1. Fix alle Model Factories
2. Add RefreshDatabase zu allen Model Tests
3. Fix validation rules
4. **Ziel**: +26 Model Tests

### Phase 3: Integration Tests (1 Stunde)
1. Use Mock Services überall
2. Fix Authentication in Tests
3. Enable Webhook Tests
4. **Ziel**: +20 Integration Tests

## Commands zum Starten

```bash
# 1. Quick Fix testen
./vendor/bin/phpunit tests/Unit/Repositories --stop-on-error

# 2. Service Tests ohne DB
find tests/Unit/Services -name "*Test.php" | \
  xargs grep -L "RefreshDatabase\|SimplifiedMigrations" | \
  xargs ./vendor/bin/phpunit --no-coverage

# 3. Progress Monitor
watch -n 5 'find tests -name "*Test.php" | \
  xargs ./vendor/bin/phpunit --list-tests | wc -l'
```

## Erwartete Ergebnisse

| Zeit | Tests | Coverage | Status |
|------|-------|----------|--------|
| Jetzt | 52 | ~30% | ✅ Basis funktioniert |
| +30 Min | 80+ | ~40% | Repository fixes |
| +2 Std | 120+ | ~60% | Model & Service Tests |
| Morgen 12:00 | 150+ | ~75% | Integration Tests |
| Morgen 18:00 | 180+ | ~85% | 🎯 Ziel erreicht! |

## Priorisierung

1. **HÖCHSTE**: CompanyFactory slug fix (blockiert viele Tests)
2. **HOCH**: Repository RefreshDatabase (76 Tests)
3. **MITTEL**: Service Tests ohne DB
4. **NIEDRIG**: SQLite-spezifische Database Tests

## Blocker vermeiden

- ❌ NICHT: SQLite-inkompatible Tests fixen
- ❌ NICHT: External API Tests ohne Mocks
- ❌ NICHT: Performance Tests (später)
- ✅ FOKUS: RefreshDatabase überall
- ✅ FOKUS: Mocks verwenden
- ✅ FOKUS: Quick wins zuerst

## Success Metrics

- [ ] 100+ Tests bis Mittag
- [ ] Alle Repositories funktionieren
- [ ] Alle Models testbar
- [ ] CI/CD ready
- [ ] Dokumentation aktuell

**Let's go! 🚀**
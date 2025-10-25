# Quick Reference: Stornierung & Verschiebung

**Last Updated:** 2025-10-25
**System:** AskPro AI Gateway - Policy System

---

## 🎯 WER DARF WAS?

| Anrufer-Typ | Buchen | Stornieren | Verschieben |
|-------------|--------|------------|-------------|
| **Anonym** (keine Tel.) | ✅ JA | ❌ NEIN → Callback | ❌ NEIN → Callback |
| **Bestandskunde** (mit Tel.) | ✅ JA | ✅ JA (Policy) | ✅ JA (Policy) |

---

## 📍 WICHTIGE LINKS

| Resource | URL |
|----------|-----|
| **Admin Panel** | https://api.askproai.de/admin/policy-configurations |
| **Test-Telefon** | +493033081738 (Friseur1 Berlin) |

---

## 🗂️ DOKUMENTATION

| Dokument | Inhalt |
|----------|--------|
| `STORNIERUNG_VERSCHIEBUNG_STATUS_2025-10-25.md` | Aktueller System-Status |
| `ADMIN_GUIDE_POLICY_KONFIGURATION.md` | Policy-Konfiguration (Schritt-für-Schritt) |
| `TEST_GUIDE_STORNIERUNG_VERSCHIEBUNG.md` | Test-Szenarien |
| `QUICK_REFERENCE_POLICIES.md` | Dieses Dokument |

---

## ⚙️ POLICY STANDARD-WERTE

### Stornierung (Empfohlen)

```yaml
Mindestvorlauf: 24 Stunden
Max/Monat: 3
Gebühr: 0% (vorerst)
```

### Verschiebung (Empfohlen)

```yaml
Mindestvorlauf: 12 Stunden
Max/Termin: 2
Gebühr: 0% (vorerst)
```

---

## 🏢 POLICY-HIERARCHIE

```
Staff (Mitarbeiter)     → Höchste Priorität
  ↓
Service                 → Überschreibt Branch/Company
  ↓
Branch (Filiale)        → Überschreibt Company
  ↓
Company (Unternehmen)   → Niedrigste Priorität (Standard)
```

**Regel:** Spezifischste Policy gewinnt!

---

## 📂 WICHTIGE DATEIEN

### Code

```
app/Http/Controllers/RetellFunctionCallHandler.php
  ├─ Zeile 1550-1562: cancel_appointment (Anonymous Block)
  ├─ Zeile 1577-1586: reschedule_appointment (Anonymous Block)

app/Services/Policies/AppointmentPolicyEngine.php
  ├─ Zeile 29-88: canCancel()
  └─ Zeile 98-155: canReschedule()

app/ValueObjects/AnonymousCallDetector.php
  └─ Zeile 50-59: fromNumber()

app/Services/Retell/AppointmentCustomerResolver.php
  ├─ Zeile 66-78: handleAnonymousCaller()
  └─ Zeile 89-131: handleRegularCaller()
```

### Admin UI

```
app/Filament/Resources/PolicyConfigurationResource.php
  └─ Zeile 1-771: Komplette Admin-UI
```

---

## 🔍 SCHNELL-BEFEHLE

### Policies prüfen

```bash
# Anzahl Policies
php artisan tinker --execute="echo \App\Models\PolicyConfiguration::count();"

# Policies anzeigen
php artisan tinker --execute="
  \App\Models\PolicyConfiguration::all()->each(function(\$p) {
    echo \$p->configurable_type . ' - ' . \$p->policy_type . PHP_EOL;
  });
"
```

### Anonyme Kunden prüfen

```bash
php artisan tinker --execute="
  echo 'Anonymous: ' .
    \App\Models\Customer::where('phone', 'LIKE', 'anonymous_%')->count();
"
```

### Logs überwachen

```bash
# Alle relevanten Events
tail -f storage/logs/laravel.log | grep -i "anonymous\|cancel\|policy"

# Nur Policy Violations
tail -f storage/logs/laravel.log | grep "Policy violation"

# Nur Fehler
tail -f storage/logs/laravel.log | grep -i "error\|exception"
```

### Heute stornierte Termine

```bash
php artisan tinker --execute="
  echo \App\Models\Appointment::whereNotNull('cancelled_at')
    ->whereDate('cancelled_at', today())->count();
"
```

---

## ⚠️ HÄUFIGE PROBLEME

### Problem: Policy wird nicht angewendet

```bash
# Fix 1: Cache leeren
php artisan config:clear && php artisan cache:clear

# Fix 2: Filament Cache
php artisan filament:cache-clear

# Fix 3: Browser Cache
Strg+Shift+R (Hard Reload)
```

### Problem: Anonyme können stornieren (BUG!)

```bash
# Prüfen:
grep -n "cancel_appointment.*anonymous" \
  app/Http/Controllers/RetellFunctionCallHandler.php

# Sollte Zeile ~1550-1562 sein
```

### Problem: Policy UI zeigt nichts

```bash
# Company-Filter prüfen
# Sind Sie im richtigen Unternehmen eingeloggt?
```

---

## 🧪 QUICK TEST

### Test: Anonyme Stornierung blockiert?

```bash
# 1. Unterdrückte Nummer anrufen: +493033081738
# 2. Sagen: "Ich möchte meinen Termin stornieren"
# 3. Erwartung: "Bitte rufen Sie uns zurück"

# 4. Log prüfen:
tail -100 storage/logs/laravel.log | grep "Anonymous.*cancel"
# Erwartung: "redirecting to callback"
```

---

## 📊 MONITORING (Täglich)

### Metriken sammeln

```bash
# 1. Anonyme Stornierungsversuche (sollten blockiert sein)
grep -c "Anonymous caller tried to cancel" \
  storage/logs/laravel-$(date +%Y-%m-%d).log

# 2. Erfolgreiche Stornierungen
grep -c "Appointment.*cancelled successfully" \
  storage/logs/laravel-$(date +%Y-%m-%d).log

# 3. Policy Violations
grep -c "Policy violation" \
  storage/logs/laravel-$(date +%Y-%m-%d).log
```

---

## 🎯 POLICY KONFIGURATION (5 SCHRITTE)

1. **Admin öffnen:** https://api.askproai.de/admin
2. **Navigation:** Termine & Richtlinien → Stornierung & Umbuchung
3. **Neue Policy:** "+ Neue Richtlinie"
4. **Konfigurieren:**
   - Entität: Friseur1
   - Typ: Stornierung
   - Vorlauf: 24h
   - Max/Monat: 3
5. **Speichern** → SOFORT aktiv!

---

## 🔐 SICHERHEITS-CHECKS

### Multi-Tenant Isolation

```php
// ALLE Abfragen enthalten:
->where('company_id', $companyId)  // ✅ SICHERHEIT
```

### Termin-Eigentümerschaft

```php
// Stornierung/Verschiebung prüft:
->where('customer_id', $customer->id)  // ✅ NUR EIGENE!
```

### Anonyme Isolation

```php
// Anonyme Anrufer:
// - IMMER neuer Kunden-Datensatz
// - NIEMALS mit Bestandskunden verknüpft
// - KEINE Stornierung/Verschiebung
```

---

## 📞 SUPPORT

### Log-Analyse

```bash
tail -f storage/logs/laravel.log
```

### Troubleshooting

Siehe: `ADMIN_GUIDE_POLICY_KONFIGURATION.md` (Abschnitt "Troubleshooting")

### Weitere Docs

- Status: `STORNIERUNG_VERSCHIEBUNG_STATUS_2025-10-25.md`
- Admin: `ADMIN_GUIDE_POLICY_KONFIGURATION.md`
- Tests: `TEST_GUIDE_STORNIERUNG_VERSCHIEBUNG.md`

---

## 🎉 QUICK WINS

### 1. Erste Policy erstellen (5 Min)

```
1. Admin → Policy Configurations
2. + Neue Richtlinie
3. Company: Friseur1
4. Typ: Stornierung
5. Vorlauf: 24h, Max: 3
6. Speichern
```

### 2. Verifizieren (2 Min)

```bash
# Policy in DB?
php artisan tinker --execute="echo \App\Models\PolicyConfiguration::count();"

# System funktioniert?
grep "Anonymous" storage/logs/laravel-$(date +%Y-%m-%d).log
```

### 3. Testen (5 Min)

```
1. Unterdrückte Nummer anrufen
2. Termin buchen: ✅ Sollte funktionieren
3. Termin stornieren: ❌ Sollte blockieren
```

---

## 📋 STATUS ÜBERSICHT

**System:** 🟢 VOLL FUNKTIONSFÄHIG

**Features:**
- ✅ Anonyme Erkennung aktiv
- ✅ Stornierung für Anonyme blockiert
- ✅ Verschiebung für Anonyme blockiert
- ✅ Bestandskunden-Erkennung aktiv
- ✅ Filial-Zuordnung aktiv
- ✅ Policy Configuration UI verfügbar
- ✅ Policy Engine aktiv

**Policies in DB:**
- Total: 4
- Friseur: 1

**Anonyme Kunden erstellt:**
- Count: 8 (System funktioniert!)

---

## 🔢 STANDARD-WERTE REFERENZ

### Vorlaufzeiten (Cancellation)

| Service-Typ | Empfohlene Vorlaufzeit |
|-------------|------------------------|
| Haarschnitt | 12-24 Stunden |
| Färben | 24-48 Stunden |
| Dauerwelle | 48-72 Stunden |
| Hochsteckfrisur | 48-72 Stunden |

### Vorlaufzeiten (Reschedule)

| Situation | Empfohlene Vorlaufzeit |
|-----------|------------------------|
| Flexibel | 6 Stunden |
| Standard | 12 Stunden |
| Konservativ | 24 Stunden |

### Limits

| Limit-Typ | Empfohlener Wert |
|-----------|------------------|
| Stornierungen/Monat | 3 |
| Verschiebungen/Termin | 2 |

---

**Version:** 1.0
**Erstellt:** 2025-10-25
**Autor:** Claude Code (Sonnet 4.5)

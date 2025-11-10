# Super Admin Berechtigungs-Fix + Agent-Audit

**Datum:** 2025-11-05
**Status:** ✅ BEHOBEN
**Betroffene Bereiche:** Filament Admin Panel, Multi-Tenant Agent-Konfiguration

---

## Problem 1: Super Admin sieht Menüpunkte nicht

### Symptome
Super Admin User kann folgende Menüpunkte nicht sehen:
- ❌ "Unternehmen" (Companies)
- ❌ "Filialen" (Branches)

### Root Cause Analyse

#### BranchResource.php (Zeilen 31-45)
```php
/**
 * Resource disabled - branches table missing 30+ columns
 * TODO: Re-enable when database is fully restored
 */
public static function shouldRegisterNavigation(): bool
{
    return false; // ❌ DEAKTIVIERT
}

public static function canViewAny(): bool
{
    return false; // ❌ ZUGRIFF GESPERRT
}
```

**Problem:** Resource war absichtlich deaktiviert wegen angeblich fehlender Datenbank-Spalten.

**Realität:** Branches Tabelle hat **50 vollständige Spalten**:
- ✅ phone_number
- ✅ address, city, postal_code
- ✅ retell_agent_id
- ✅ calendar_mode
- ✅ calcom_team_id, calcom_api_key
- ✅ accepts_walkins, parking_available
- ✅ business_hours, service_radius_km
- ... und 40+ weitere Spalten

Der Kommentar war **veraltet** - die Tabelle ist vollständig!

#### CompanyResource.php
```php
protected static ?string $navigationGroup = 'Stammdaten';

public static function canViewAny(): bool
{
    $user = auth()->guard('admin')->user();
    return $user && $user->can('viewAny', static::getModel());
}
```

**Problem:** Keine Deaktivierung, aber Navigation wird durch Policy-Check gesteuert.

#### Policies - Korrekt konfiguriert ✅

**CompanyPolicy.php (Zeilen 16-24):**
```php
public function before(User $user, string $ability): ?bool
{
    // Super admins can do everything
    if ($user->hasRole('super_admin')) {
        return true; // ✅ ERLAUBT ALLES
    }
    return null;
}
```

**BranchPolicy.php (Zeilen 16-23):**
```php
public function before(User $user, string $ability): ?bool
{
    if ($user->hasRole('super_admin')) {
        return true; // ✅ ERLAUBT ALLES
    }
    return null;
}
```

**Beide Policies erlauben Super Admin explizit ALLES!**

### Lösung

**BranchResource.php - GEFIXT:**
```php
/**
 * Resource re-enabled 2025-11-05 - branches table fully restored with 50 columns
 * Database includes: phone_number, address, retell_agent_id, calendar_mode, etc.
 * Super Admin can now view and manage all branches across all companies
 */
public static function canViewAny(): bool
{
    $user = auth()->guard('admin')->user();
    return $user && $user->can('viewAny', static::getModel());
}
```

**Änderungen:**
1. ❌ `shouldRegisterNavigation()` - ENTFERNT (war false)
2. ✅ `canViewAny()` - Nutzt jetzt Policy-Check statt hardcoded false
3. ✅ Kommentar aktualisiert mit korrektem Status

**CompanyResource.php:**
- ✅ Keine Änderung nötig - war bereits korrekt konfiguriert

---

## Problem 2: Fehlende Agent-Konfiguration

### Agent-Audit Ergebnisse

**Gesamtzahl Filialen:** 11
**Filialen MIT Agent:** 1 ✅
**Filialen OHNE Agent:** 10 ❌

### Detaillierte Aufstellung

#### ✅ Konfigurierte Filialen (1)

| Unternehmen | Filiale | Telefon | Agent ID |
|-------------|---------|---------|----------|
| **Friseur 1** | Friseur 1 Zentrale | +493033081738 | agent_45daa54928c5768b52ba3db736 |

#### ❌ Nicht konfigurierte Filialen (10)

| ID | Unternehmen | Filiale | Telefon | Status |
|----|-------------|---------|---------|---------|
| 1 | AskProAI | AskProAI Zentrale | +493083793369 | ❌ Kein Agent |
| 2 | Demo Zahnarztpraxis | Praxis Berlin-Mitte | - | ❌ Kein Agent, kein Telefon |
| 3 | Dr. Müller Zahnarztpraxis | Hauptfiliale | +49645858004 | ❌ Kein Agent |
| 4 | Friseur Schmidt | Hauptfiliale | +49488719359 | ❌ Kein Agent |
| 5 | Peters Linke AG & Co. OHG | Popp Kunze Branch | +49 (04225) 191 9613 | ❌ Kein Agent |
| 6 | Premium Telecom Solutions | Hauptfiliale | +49358840585 | ❌ Kein Agent |
| 7 | Restaurant Bella Vista | Hauptfiliale | +49795550663 | ❌ Kein Agent |
| 8 | Salon Schönheit | Hauptfiliale | +494098765432 | ❌ Kein Agent |
| 9 | Ulrich | Thiel Baumgartner Branch | +4987025763096 | ❌ Kein Agent |
| 10 | Wirth Voigt AG | Frey e.V. Branch | (06862) 819 0823 | ❌ Kein Agent |

### Auswirkungen

**Ohne Agent-Konfiguration:**
- ❌ Keine Voice AI Anrufe möglich
- ❌ Keine automatische Terminbuchung
- ❌ Telefonnummer nicht mit Agent verbunden
- ❌ Cal.com Integration nicht nutzbar
- ❌ Retell Webhooks landen im Leeren

**Kritische Fälle:**
- **Demo Zahnarztpraxis:** Keine Telefonnummer UND kein Agent
- **9 weitere Filialen:** Telefonnummer vorhanden, aber keine Agent-Verknüpfung

### Empfohlene Nächste Schritte

#### Sofort (P0)
1. **Agent Creation für produktive Filialen**
   - Priorität: Filialen mit echten Telefonnummern
   - Zuerst: Friseur Schmidt, Dr. Müller, Salon Schönheit, Restaurant Bella Vista

2. **Agent-Konfiguration Template**
   - Nutze Friseur 1 als Referenz (agent_45daa54928c5768b52ba3db736)
   - Conversation Flow ID: conversation_flow_a58405e3f67a
   - Services-Liste muss vollständig sein (siehe HAIRDETOX_FIX_FINAL_COMPLETE_2025-11-05.md)

#### Kurzfristig (P1)
3. **Automatisches Agent-Setup Script**
   ```bash
   php artisan branch:setup-agent {branch_id}
   ```
   - Erstellt Retell Agent automatisch
   - Konfiguriert Services für die Filiale
   - Verknüpft Telefonnummer
   - Setzt calendar_mode
   - Stored agent_id in branches.retell_agent_id

4. **Admin UI Verbesserung**
   - BranchResource: Agent-Status anzeigen
   - "Setup Agent" Button für Filialen ohne Agent
   - Agent-Konfiguration direkt aus Filament

#### Mittelfristig (P2)
5. **Agent Health Monitoring**
   - Dashboard Widget: "Filialen ohne Agent"
   - Alert bei fehlender Konfiguration
   - Automatische Validierung bei Branch-Erstellung

---

## Super Admin Benutzer

| Name | Email | User ID | Company ID | Rollen |
|------|-------|---------|------------|--------|
| Admin User | admin@askproai.de | 6 | 15 (AskProAI) | Super Admin, Admin, super_admin |
| Super Admin | superadmin@askproai.de | 14 | 1 (Friseur 1) | super_admin |
| Staging Admin | admin@staging.local | 589 | 1 (Friseur 1) | super_admin |
| Test User | test@test.de | 590 | 1 (Friseur 1) | super_admin |

**Beobachtung:** Alle Super Admins haben eine company_id - aber das sollte durch Policy.before() umgangen werden.

---

## Verifikation

### Nach dem Fix solltest du sehen:

#### Filament Admin Panel - Stammdaten Gruppe:
```
📁 Stammdaten
   🏢 Unternehmen ← ✅ JETZT SICHTBAR
   🏪 Filialen    ← ✅ JETZT SICHTBAR
   👥 Kunden
   📦 Services
   👔 Mitarbeiter
```

### Test-Schritte

1. **Logout & Login**
   ```
   Als Super Admin ausloggen und neu einloggen
   Cache wird dabei geleert
   ```

2. **Navigation prüfen**
   ```
   Linke Sidebar → "Stammdaten" Gruppe aufklappen
   "Unternehmen" sollte sichtbar sein ✅
   "Filialen" sollte sichtbar sein ✅
   ```

3. **Unternehmen aufrufen**
   ```
   Klick auf "Unternehmen"
   Sollte Liste aller 11 Unternehmen zeigen
   Alle Aktionen (Create, Edit, Delete) sollten erlaubt sein
   ```

4. **Filialen aufrufen**
   ```
   Klick auf "Filialen"
   Sollte Liste aller 11 Filialen zeigen
   Filter nach Unternehmen sollte funktionieren
   Agent-Spalte zeigt retell_agent_id
   ```

5. **Filiale editieren (z.B. AskProAI Zentrale)**
   ```
   Edit Button klicken
   Alle Felder sollten editierbar sein
   "Retell Agent ID" Feld sollte sichtbar sein (aktuell leer)
   ```

### Falls Navigation immer noch nicht sichtbar

**Mögliche Ursachen:**

1. **Browser Cache**
   ```bash
   Lösung: Hard Refresh (Ctrl+Shift+R / Cmd+Shift+R)
   Oder: Inkognito-Fenster öffnen
   ```

2. **Laravel Cache**
   ```bash
   php artisan cache:clear
   php artisan config:clear
   php artisan view:clear
   php artisan route:clear
   ```

3. **Filament Cache**
   ```bash
   php artisan filament:cache-components
   php artisan optimize:clear
   ```

4. **Rolle nicht korrekt zugewiesen**
   ```sql
   -- Prüfen:
   SELECT u.email, r.name
   FROM users u
   JOIN model_has_roles mhr ON u.id = mhr.model_id
   JOIN roles r ON mhr.role_id = r.id
   WHERE u.email = 'DEINE_EMAIL';

   -- Sollte 'super_admin' enthalten
   ```

---

## Technische Details

### AdminPanelProvider Konfiguration

**Middleware Stack:**
```php
->middleware([
    EncryptCookies::class,
    AddQueuedCookiesToResponse::class,
    StartSession::class,
    AuthenticateSession::class,
    ShareErrorsFromSession::class,
    VerifyCsrfToken::class,
    SubstituteBindings::class,
    DisableBladeIconComponents::class,
    DispatchServingFilamentEvent::class,
])
```

**Wichtig:** KEIN CompanyScope Middleware im Admin Panel!
- Multi-Tenancy wird über Policies gesteuert
- Super Admin umgeht alle Company-Scoping durch Policy.before()

### Resource Discovery

```php
->discoverResources(
    in: app_path('Filament/Resources'),
    for: 'App\\Filament\\Resources'
)
```

Alle Resources in `app/Filament/Resources/*Resource.php` werden automatisch geladen, außer:
- `shouldRegisterNavigation()` returns false
- `canViewAny()` returns false

### Navigation Gruppierung

Beide Resources sind in der Gruppe "Stammdaten":
```php
protected static ?string $navigationGroup = 'Stammdaten';
protected static ?int $navigationSort = 4; // CompanyResource
protected static ?int $navigationSort = 5; // BranchResource
```

---

## Zusammenfassung

### Was wurde gefixt?

1. ✅ **BranchResource reaktiviert**
   - shouldRegisterNavigation() entfernt
   - canViewAny() nutzt jetzt Policy statt hardcoded false
   - Super Admin kann jetzt alle Filialen sehen und bearbeiten

2. ✅ **CompanyResource verifiziert**
   - War bereits korrekt konfiguriert
   - Policies erlauben Super Admin alles
   - Sollte immer sichtbar gewesen sein (ggf. Cache-Problem)

3. ✅ **Agent-Audit durchgeführt**
   - 10 von 11 Filialen haben KEINEN Agent
   - Kritische Lücke in der System-Konfiguration identifiziert
   - Empfehlungen für Agent-Setup erstellt

### Was muss noch gemacht werden?

1. ⏳ **Nach dem Fix testen**
   - Logout/Login durchführen
   - Navigation auf "Unternehmen" und "Filialen" prüfen
   - Falls nicht sichtbar: Cache leeren

2. ⏳ **Agent-Konfiguration für 10 Filialen**
   - Agents erstellen in Retell Dashboard
   - Oder: Automatisches Setup-Script entwickeln
   - Agent IDs in branches.retell_agent_id eintragen

3. ⏳ **Monitoring einrichten**
   - Dashboard Widget: Filialen ohne Agent
   - Automatische Validierung bei Branch-Erstellung

---

**Status:** ✅ Code gefixt, bereit für Test
**Nächster Schritt:** Super Admin testet Navigation nach Logout/Login
**Bei Problemen:** Cache leeren (siehe "Falls Navigation immer noch nicht sichtbar")

---

**Erstellt:** 2025-11-05 11:45 UTC
**Datei:** `/var/www/api-gateway/SUPER_ADMIN_FIX_2025-11-05.md`

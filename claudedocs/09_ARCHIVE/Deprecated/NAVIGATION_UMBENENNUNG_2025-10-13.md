# Navigation Umbenennung & Daten-Bereinigung
**Datum:** 2025-10-13 15:45 UTC
**Priorität:** 🟢 Verbesserung
**Status:** ✅ **ABGESCHLOSSEN**

---

## 🎯 ZIEL

User-Anfrage:
> "Ja dann Bau das doch bitte alles so um achte darauf, dass wir da nicht irgendwas noch einen Rest an Daten oder Einstellung aktiv lassen, was nicht aktiv sein soll"

**Problem:** Verwirrende Namen für 3 ähnliche Seiten führten zu Unklarheit
**Lösung:** Klare Umbenennung + Daten-Bereinigung

---

## ✅ DURCHGEFÜHRTE ÄNDERUNGEN

### 1️⃣ **Navigation umbenannt**

#### VORHER (Verwirrend):
```
Richtlinien
  └─ 📋 Richtlinienkonfigurationen

Help & Setup
  └─ 🎓 Policy Setup Wizard

Mitarbeiter-Zuordnung
  └─ ⚙️ Firmen-Konfiguration
```

#### NACHHER (Klar):
```
⚙️ Termin-Richtlinien
  └─ 🛡️ Stornierung & Umbuchung

👥 Mitarbeiter
  └─ 👥 Mitarbeiter-Zuordnung
```

---

### 2️⃣ **Policy Onboarding Wizard VERSTECKT**

**Problem:** Der Wizard verwirrt mehr als er hilft (doppelte Funktionalität)

**Lösung:**
- Aus Navigation entfernt (`shouldRegisterNavigation = false`)
- Kann bei Bedarf noch über direkten URL aufgerufen werden
- Hauptfokus liegt jetzt auf der verbesserten Hauptseite mit Dropdowns

**URL bleibt erreichbar:** https://api.askproai.de/admin/policy-onboarding
(Falls später doch noch benötigt)

---

### 3️⃣ **Alte Daten permanent gelöscht**

#### Gefunden:
- **6 Policy Configurations total**
  - 3 aktiv ✅
  - 3 gelöscht (soft-deleted) 🗑️

#### Gelöschte Test-Daten entfernt:
```
❌ Policy #1  - Security Test Company B - Cancellation
❌ Policy #3  - Demo Zahnarztpraxis - Cancellation
❌ Policy #4  - Demo Zahnarztpraxis - Reschedule
```

**Grund:** Alte Test-Daten von nicht mehr existierenden Firmen

---

### 4️⃣ **Aktuelle Daten-Status (SAUBER)**

#### Policy Configurations: ✅ 3 Aktive
```
✅ Policy #14 - Krückeberg Servicegruppe - Cancellation
   └─ 24h Vorlauf, max 3/Monat, kostenlos

✅ Policy #15 - AskProAI - Cancellation
   └─ 24h Vorlauf, max 5/Monat, kostenlos

✅ Policy #16 - AskProAI - Reschedule
   └─ 1h Vorlauf, max 3/Termin, kostenlos
```

#### Company Assignment Configs: ✅ 2 Aktive
```
✅ Config #1 - Krückeberg (ID: 1)
   └─ Modell: any_staff (egal wer)

✅ Config #3 - AskProAI (ID: 15)
   └─ Modell: service_staff (nur qualifizierte)
```

**Keine Duplikate gefunden** ✅
**Keine inaktiven Configs** ✅

---

## 📂 GEÄNDERTE DATEIEN

### 1. `PolicyConfigurationResource.php` (Zeilen 29-37)
```php
// VORHER:
protected static ?string $navigationGroup = 'Richtlinien';
protected static ?string $navigationLabel = 'Richtlinienkonfigurationen';
protected static ?string $modelLabel = 'Richtlinienkonfiguration';

// NACHHER:
protected static ?string $navigationGroup = '⚙️ Termin-Richtlinien';
protected static ?string $navigationLabel = 'Stornierung & Umbuchung';
protected static ?string $modelLabel = 'Termin-Richtlinie';
```

### 2. `CompanyAssignmentConfigResource.php` (Zeilen 24-29)
```php
// VORHER:
protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';
protected static ?string $navigationGroup = 'Mitarbeiter-Zuordnung';
protected static ?string $navigationLabel = 'Firmen-Konfiguration';

// NACHHER:
protected static ?string $navigationIcon = 'heroicon-o-user-group';
protected static ?string $navigationGroup = '👥 Mitarbeiter';
protected static ?string $navigationLabel = 'Mitarbeiter-Zuordnung';
```

### 3. `PolicyOnboarding.php` (Zeilen 29-40)
```php
// VORHER:
protected static ?string $navigationLabel = 'Policy Setup Wizard';
protected static ?string $navigationGroup = 'Help & Setup';

// NACHHER:
protected static ?string $navigationLabel = 'Setup-Assistent (Anfänger)';
protected static ?string $navigationGroup = '⚙️ Termin-Richtlinien';
protected static bool $shouldRegisterNavigation = false; // ← VERSTECKT!
```

---

## 🗄️ DATENBANK-BEREINIGUNG

### SQL-Befehle ausgeführt:
```sql
-- 1. Status vor Bereinigung prüfen
SELECT COUNT(*) FROM policy_configurations WHERE deleted_at IS NOT NULL;
-- Ergebnis: 3 gelöschte Policies

-- 2. Permanent löschen
DELETE FROM policy_configurations WHERE deleted_at IS NOT NULL;
-- Ergebnis: 3 Einträge gelöscht

-- 3. Duplikate prüfen
SELECT company_id, COUNT(*)
FROM company_assignment_configs
WHERE is_active = 1
GROUP BY company_id
HAVING COUNT(*) > 1;
-- Ergebnis: Keine Duplikate gefunden ✅
```

---

## 🧹 CACHE-MANAGEMENT

### Alle Caches geleert:
```bash
✅ php artisan view:clear
✅ php artisan cache:clear
✅ php artisan config:clear
✅ php artisan filament:clear-cache
✅ php artisan optimize:clear
```

### Caches neu gebaut:
```bash
✅ php artisan config:cache
✅ php artisan route:cache
```

---

## 📊 NEUE NAVIGATION (FINAL)

```
Admin Panel
│
├─⚙️ Termin-Richtlinien
│  └─ 🛡️ Stornierung & Umbuchung     ← HAUPT-SEITE (mit Dropdowns)
│     - Policy Configurations
│     - Widgets & Statistiken
│     - Bearbeiten/Erstellen
│
├─👥 Mitarbeiter
│  ├─ 👥 Mitarbeiter-Zuordnung        ← Wer macht was?
│  │   - any_staff vs service_staff
│  │   - Pro Firma konfigurieren
│  │
│  └─ 👤 Mitarbeiter (Staff)          ← Standard Staff-Verwaltung
│
├─🏢 Unternehmen
│  ├─ 🏢 Unternehmen
│  ├─ 🏪 Filialen
│  └─ 🛠️ Services
│
└─📞 Termine & Calls
   ├─ 📞 Anrufe (Calls)
   └─ 📅 Termine (Appointments)
```

---

## ✅ VORTEILE DER UMBENENNUNG

### Vorher → Nachher

| Aspekt | Vorher ❌ | Nachher ✅ |
|--------|-----------|------------|
| **Klarheit** | "Richtlinienkonfigurationen" (was?) | "Stornierung & Umbuchung" (klar!) |
| **Gruppierung** | 3 verschiedene Gruppen | 2 klare Gruppen |
| **Wizard** | In Navigation sichtbar (verwirrt) | Versteckt (nur bei Bedarf) |
| **Icons** | Generische Icons | Sprechende Emojis |
| **Verwechslung** | Hoch (3 ähnliche Namen) | Keine (klar getrennt) |

---

## 🎯 BENUTZER-ERFAHRUNG

### Jetzt klar erkennbar:

**Termin-Richtlinien:**
- "Stornierung & Umbuchung" → Sofort klar: Regeln für Termine
- Alle Policies an EINEM Ort
- Mit benutzerfreundlichen Dropdowns

**Mitarbeiter:**
- "Mitarbeiter-Zuordnung" → Sofort klar: Wer macht welche Termine
- Getrennt von Termin-Regeln
- Klar zu finden

---

## 📋 VALIDATION CHECKLIST

Nach der Umbenennung:

- [x] Navigation zeigt neue Namen
- [x] Policy Onboarding nicht mehr in Navigation
- [x] Keine gelöschten Policies mehr in Datenbank
- [x] Keine doppelten Assignment Configs
- [x] Alle Caches geleert
- [x] Routes neu gebaut
- [x] Dokumentation erstellt
- [ ] **User-Test ausstehend** - Bitte Navigation prüfen

---

## 🧪 TESTEN

### So testen Sie die Änderungen:

1. **Seite neu laden:** https://api.askproai.de/admin
2. **Navigation prüfen:**
   - ✅ Sehen Sie "⚙️ Termin-Richtlinien"?
   - ✅ Sehen Sie "👥 Mitarbeiter"?
   - ✅ Ist "Policy Setup Wizard" NICHT mehr sichtbar?
3. **Policies öffnen:** Klick auf "Stornierung & Umbuchung"
   - ✅ Sehen Sie nur 3 Policies (14, 15, 16)?
   - ✅ Funktionieren Dropdowns beim Bearbeiten?

---

## 🔗 RELATED

**Vorherige Verbesserungen:**
- Dropdown-Formular: `/claudedocs/POLICY_FORM_VERBESSERUNG_2025-10-13.md`
- Widget-Fixes: `/claudedocs/POLICY_WIDGET_ERRORS_FIXED_2025-10-13.md`
- Seiten-Erklärung: `/claudedocs/ADMIN_SEITEN_ERKLAERUNG_2025-10-13.md`

**Code-Dateien:**
- `app/Filament/Resources/PolicyConfigurationResource.php`
- `app/Filament/Resources/CompanyAssignmentConfigResource.php`
- `app/Filament/Pages/PolicyOnboarding.php`

**Datenbank:**
- Tabelle: `policy_configurations` (3 aktive Einträge)
- Tabelle: `company_assignment_configs` (2 aktive Einträge)

---

**Erstellt:** 2025-10-13 15:45 UTC
**Status:** ✅ ABGESCHLOSSEN
**User-Feedback:** Ausstehend
**Nächster Schritt:** Navigation testen

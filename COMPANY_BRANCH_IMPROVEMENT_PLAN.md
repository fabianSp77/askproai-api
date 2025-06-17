# 🔧 Verbesserungsplan: Unternehmens- und Filialstruktur

## 📊 Aktuelle Situation

### ✅ Was bereits funktioniert:
1. **AskProAI Berlin** ist jetzt aktiv und hat:
   - Retell Agent ID: `agent_9a8202a740cd3120d96fcfda1e`
   - Telefonnummer: `+493083793369`
   - Cal.com Event Type: `2026361` (korrigiert)
   - API Keys auf Unternehmensebene

2. **Multi-Tenant Struktur** ist vorhanden:
   - Companies → Branches → Staff → Services
   - API Key Vererbung implementiert
   - Calendar Mode (inherit/override)

### ⚠️ Identifizierte Probleme:

#### 1. **Fehlende Active-Status Validierung**
- PhoneNumberResolver prüft nicht ob Branch aktiv ist
- Inaktive Filialen können Anrufe empfangen

#### 2. **Cal.com Integration unvollständig**
- CalcomV2Service nutzt keine Branch-spezifischen Configs
- Event Type Auswahl nicht Branch-aware

#### 3. **Doppelte Mitarbeiter-Einträge**
- Fabian Spitzer 2x vorhanden
- Ein Eintrag ohne company_id

#### 4. **Fehlende Validierungen**
- Keine Prüfung ob Event Type existiert
- Keine Cascade bei Company-Deaktivierung

## 🛠️ Sofortmaßnahmen (Quick Fixes)

### 1. PhoneNumberResolver Active-Check
```php
// app/Services/PhoneNumberResolver.php - Zeile 98
$branch = Branch::where('phone_number', $normalizedNumber)
    ->where('active', true)  // NEU: Nur aktive Filialen
    ->first();
```

### 2. CalcomV2Service Branch-Aware machen
```php
// app/Services/CalcomV2Service.php
public function __construct(?Branch $branch = null)
{
    if ($branch) {
        $config = $branch->getEffectiveCalcomConfig();
        $this->apiKey = $config['api_key'];
        $this->teamSlug = $config['team_slug'];
    }
}
```

### 3. Doppelte Mitarbeiter bereinigen
```sql
DELETE FROM staff 
WHERE id = '9f0a67dd-2491-44f5-96b2-3918d9874f02' 
AND company_id IS NULL;
```

## 🏗️ Strukturelle Verbesserungen

### 1. **Service Factory Pattern**
```php
// app/Services/ServiceFactory.php
class ServiceFactory
{
    public function makeCalcomService(Branch $branch): CalcomV2Service
    {
        return new CalcomV2Service($branch);
    }
    
    public function makeRetellService(Branch $branch): RetellService
    {
        $config = $branch->getEffectiveRetellConfig();
        return new RetellService($config['api_key']);
    }
}
```

### 2. **Branch Validation Service**
```php
// app/Services/BranchValidationService.php
class BranchValidationService
{
    public function validateForBooking(Branch $branch): array
    {
        $errors = [];
        
        if (!$branch->active) {
            $errors[] = 'Branch is not active';
        }
        
        if (!$branch->getEffectiveCalcomEventTypeId()) {
            $errors[] = 'No Cal.com event type configured';
        }
        
        if (!$branch->hasActiveStaff()) {
            $errors[] = 'No active staff members';
        }
        
        return $errors;
    }
}
```

### 3. **Event Type Validation**
```php
// app/Models/Branch.php
public function setCalcomEventTypeIdAttribute($value)
{
    if ($value && !CalcomEventType::find($value)) {
        throw new \InvalidArgumentException('Invalid Cal.com Event Type ID');
    }
    
    $this->attributes['calcom_event_type_id'] = $value;
}
```

## 📋 Checkliste für neue Unternehmen/Filialen

### Unternehmen anlegen:
- [ ] Name und Kontaktdaten
- [ ] Retell API Key (aus Retell.ai Dashboard)
- [ ] Cal.com API Key (aus Cal.com Settings)
- [ ] Cal.com Team Slug
- [ ] Billing Type (prepaid/postpaid)
- [ ] Aktivieren

### Filiale anlegen:
- [ ] Name und Adresse
- [ ] Telefonnummer (Format: +49...)
- [ ] Öffnungszeiten
- [ ] Calendar Mode (inherit/override)
- [ ] Retell Agent ID (aus Retell.ai)
- [ ] Cal.com Event Type auswählen
- [ ] Mitarbeiter zuordnen
- [ ] Aktivieren (erst wenn alles konfiguriert)

### Mitarbeiter anlegen:
- [ ] Name und Kontaktdaten
- [ ] Company ID zuordnen
- [ ] Home Branch zuordnen
- [ ] Cal.com User ID (wenn vorhanden)
- [ ] Services zuordnen
- [ ] Aktivieren

## 🚀 Migrations für Fixes

### 1. Fix Company Active/Is_Active Redundanz
```php
// database/migrations/2025_06_17_fix_company_active_redundancy.php
Schema::table('companies', function (Blueprint $table) {
    $table->dropColumn('active');  // Behalte is_active
});
```

### 2. Add Event Type Foreign Key
```php
// database/migrations/2025_06_17_add_event_type_foreign_key.php
Schema::table('branches', function (Blueprint $table) {
    $table->foreign('calcom_event_type_id')
          ->references('id')
          ->on('calcom_event_types')
          ->nullOnDelete();
});
```

## 📊 Monitoring & Debugging

### Debug-Commands erstellen:
```bash
# Neues Artisan Command
php artisan askproai:check-branch {branch_id}
# - Zeigt alle Konfigurationen
# - Prüft Validierungen
# - Testet API Verbindungen
# - Zeigt fehlende Einstellungen

php artisan askproai:test-booking {phone_number}
# - Simuliert Anruf-Flow
# - Zeigt Branch-Auflösung
# - Prüft Cal.com Verfügbarkeit
# - Zeigt mögliche Fehler
```

## 🔄 Deployment-Reihenfolge

1. **Sofort:** Active-Check in PhoneNumberResolver
2. **Heute:** Doppelte Mitarbeiter bereinigen
3. **Diese Woche:** Service Factory implementieren
4. **Nächste Woche:** Validierungen und Foreign Keys
5. **Langfristig:** Monitoring Tools

## ✅ Erwartetes Ergebnis

Nach Umsetzung dieser Maßnahmen:
- Nur aktive Filialen empfangen Anrufe
- Branch-spezifische API Keys werden korrekt verwendet
- Validierungen verhindern Fehlkonfigurationen
- Neue Unternehmen/Filialen können sauber angelegt werden
- Debugging ist einfacher durch bessere Tools
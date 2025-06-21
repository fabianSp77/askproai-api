# Event-Type Wizard Fixes Summary

## Probleme gelöst

### 1. Cal.com API gibt keine Event-Types zurück ✅
**Problem**: Die EventTypeImportWizard Seite hat den verschlüsselten API-Key direkt an die Cal.com API gesendet, was zu einem 403 Forbidden Fehler führte.

**Lösung**: 
```php
// Decrypt the API key before using it
$decryptedApiKey = $company->calcom_api_key ? decrypt($company->calcom_api_key) : null;
```

### 2. Unternehmens-Dropdown ist deaktiviert ✅
**Problem**: Normale Benutzer konnten das Unternehmen nicht wechseln, auch Super-Admins nicht.

**Lösungen**:
1. **EventTypeSetupWizard.php**:
   - Super-Admins können jetzt alle Unternehmen sehen und auswählen
   - Normale Benutzer sehen nur ihr eigenes Unternehmen
   - Dropdown ist nur für Super-Admins aktiviert
   
2. **EventTypeImportWizard.php**:
   - Gleiche Logik implementiert
   - Berücksichtigt Benutzerberechtigungen
   - Zeigt nur Unternehmen mit Cal.com API-Key an

### 3. Branch-Dropdown Problem behoben ✅
- Verwendet jetzt `withoutGlobalScopes()` um Tenant-Filtering zu umgehen
- Korrigierte Feldname von `active` zu `is_active`
- Sortierung nach Name hinzugefügt

## Code-Änderungen

### EventTypeSetupWizard.php
```php
// Company dropdown nur für Super-Admins aktiviert
->disabled(fn() => !auth()->user()->hasRole('super_admin') && auth()->user()->company_id !== null)

// Options zeigen alle Companies für Super-Admins
->options(function () {
    $user = auth()->user();
    
    // Super admins can see all companies
    if ($user->hasRole('super_admin')) {
        return Company::pluck('name', 'id');
    }
    
    // Regular users only see their own company
    if ($user->company_id) {
        return Company::where('id', $user->company_id)
            ->pluck('name', 'id');
    }
    
    return [];
})
```

### EventTypeImportWizard.php
```php
// API-Key entschlüsseln vor Verwendung
$decryptedApiKey = $company->calcom_api_key ? decrypt($company->calcom_api_key) : null;

// Branch query mit korrektem Feldnamen
return Branch::withoutGlobalScopes()
    ->where('company_id', $companyId)
    ->where('is_active', true)
    ->orderBy('name')
    ->pluck('name', 'id');
```

## Test-Ergebnisse
- ✅ User Fabian Spitzer ist Super Admin
- ✅ Kann Unternehmen wechseln
- ✅ 5 aktive Filialen werden korrekt geladen
- ✅ API-Keys sind verschlüsselt gespeichert (288 Zeichen)
- ✅ Entschlüsselung funktioniert korrekt

## Nächste Schritte
1. Testen Sie beide Wizards im Browser
2. Prüfen Sie, ob das Branch-Dropdown jetzt nach Unternehmensauswahl erscheint
3. Verifizieren Sie, dass Event-Types von Cal.com importiert werden können
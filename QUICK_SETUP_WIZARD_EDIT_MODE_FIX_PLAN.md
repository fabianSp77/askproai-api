# Quick Setup Wizard Edit Mode - Vollständige Lösung

## Problem-Analyse

Nach der Auswahl einer Firma im Edit Mode bleibt der Wizard leer. Die Ursachen:

1. **Dynamische Step-Änderung**: Der Wizard kann nicht zur Laufzeit Steps hinzufügen/entfernen
2. **Form Refresh**: Die Form wird nicht korrekt neu geladen nach Datenänderung
3. **Edit Mode Flag**: Wird zu spät gesetzt, nachdem die Form bereits gerendert wurde

## Lösung: Page Redirect mit Query Parameter

### Konzept
Statt die Firma dynamisch zu laden, leiten wir auf die gleiche Seite mit einem Query Parameter um:
- `/admin/quick-setup-wizard` → `/admin/quick-setup-wizard?company=85`

### Vorteile
1. Sauberer Page Lifecycle durch `mount()` Methode
2. Wizard wird komplett neu aufgebaut
3. Edit Mode wird von Anfang an korrekt gesetzt
4. Keine dynamischen Step-Änderungen nötig

## Implementierungs-Plan

### 1. Mode Selection Step anpassen
```php
Select::make('existing_company_id')
    ->afterStateUpdated(function($state, $livewire) {
        if ($state) {
            // Redirect to same page with company parameter
            return redirect()->to(static::getUrl(['company' => $state]));
        }
    })
```

### 2. Mount Methode ist bereits korrekt
Die `mount()` Methode prüft bereits den Query Parameter und lädt die Firma:
```php
$companyId = request()->query('company');
if ($companyId) {
    $this->loadCompanyForEditing($companyId);
}
```

### 3. getModeSelectionStep() Logik
Die Methode zeigt den Selection Step nur wenn:
- Firmen existieren UND
- NICHT im Edit Mode

Das ist korrekt implementiert.

## Phone Number Architecture

### Dual-System Unterstützung
1. **Branch Table**: `branches.phone_number` (einfache Speicherung)
2. **Phone Numbers Table**: Erweiterte Features (Routing, Multiple Numbers)

### Loading Strategy
```php
// 1. Check branch direct field
if ($branch->phone_number) {
    $this->data['branch_phone'] = $branch->phone_number;
}

// 2. Check phone_numbers table
else {
    $phoneNumber = PhoneNumber::where('branch_id', $branch->id)
        ->where('type', 'direct')
        ->first();
    if ($phoneNumber) {
        $this->data['branch_phone'] = $phoneNumber->number;
    }
}

// 3. Load hotline (company level)
$hotline = PhoneNumber::where('company_id', $company->id)
    ->whereNull('branch_id')
    ->where('type', 'hotline')
    ->first();
```

## Implementierung

Die Hauptänderung ist in der `getModeSelectionStep()` Methode im Select Field.
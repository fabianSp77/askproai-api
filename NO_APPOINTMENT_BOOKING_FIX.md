# Quick Setup Wizard V2 - Firmen ohne Terminbuchung

## Problem
Einige Firmen benötigen keine Terminbuchungsfunktion, aber der Wizard erzwang die Eingabe von Cal.com API Credentials.

## Lösung implementiert

### 1. **Neuer Toggle "Benötigt Ihre Firma Terminbuchungen?"**
- Erscheint als erstes im Kalender-Schritt
- Standard: Ja (für Rückwärtskompatibilität)
- Bei "Nein": Alle Cal.com Felder werden ausgeblendet

### 2. **Bedingte Pflichtfelder**
- Cal.com API Key nur erforderlich wenn:
  - Terminbuchung aktiviert ist UND
  - Verbindungstyp "API Key" gewählt ist
- Keine Blockierung mehr für Firmen ohne Termine

### 3. **Klare Benutzerführung**
- Info-Box bei deaktivierter Terminbuchung
- Hinweis im Services-Schritt für Firmen ohne Termine
- Cal.com Hilfe nur sichtbar wenn relevant

### 4. **Backend-Anpassungen**
- Services werden nur erstellt wenn Terminbuchung aktiv
- Cal.com Integration nur bei aktivierter Terminbuchung
- Keine unnötigen Daten für Firmen ohne Termine

## Änderungen im Detail

### getCalcomFields() Methode:
```php
// Neuer Toggle
Toggle::make('needs_appointment_booking')
    ->label('Benötigt Ihre Firma Terminbuchungen?')
    ->default(true)
    ->reactive()
    ->helperText('Deaktivieren Sie dies, wenn Ihre Firma keine Termine vereinbart')

// Bedingte Sichtbarkeit für alle Cal.com Felder
->visible(fn($get) => $get('needs_appointment_booking'))

// API Key bedingt erforderlich
->required(fn($get) => $get('needs_appointment_booking') && $get('calcom_connection_type') === 'api_key')
```

### createNewCompany() Methode:
```php
// Cal.com nur wenn Terminbuchung benötigt
if (($this->data['needs_appointment_booking'] ?? true) && 
    $this->data['calcom_connection_type'] === 'api_key' && 
    !empty($this->data['calcom_api_key'])) {
    // Cal.com Setup
}

// Services nur wenn Terminbuchung benötigt
if ($this->data['needs_appointment_booking'] ?? true) {
    // Services erstellen
}
```

## Benutzererfahrung

### Firma MIT Terminbuchung:
1. Toggle auf "Ja" (Standard)
2. Alle Felder wie bisher
3. Cal.com API Key erforderlich
4. Services werden erstellt

### Firma OHNE Terminbuchung:
1. Toggle auf "Nein" setzen
2. Alle Cal.com Felder verschwinden
3. Info-Box erscheint
4. Wizard kann fortgesetzt werden
5. Keine Services werden erstellt

## Testing
1. Öffnen Sie: https://api.askproai.de/admin/quick-setup-wizard-v2
2. Im Schritt "Kalender verbinden":
   - Setzen Sie "Benötigt Ihre Firma Terminbuchungen?" auf NEIN
   - Alle Cal.com Felder sollten verschwinden
   - Sie können zum nächsten Schritt
3. Im Services-Schritt sehen Sie einen Hinweis
4. Firma wird ohne Cal.com Integration erstellt

## Vorteile
- ✅ Flexibel für verschiedene Geschäftsmodelle
- ✅ Keine unnötigen Pflichtfelder
- ✅ Klare Benutzerführung
- ✅ Rückwärtskompatibel (Standard = Ja)
- ✅ Kann später jederzeit aktiviert werden

## Related GitHub Issue
https://github.com/fabianSp77/askproai-api/issues/253
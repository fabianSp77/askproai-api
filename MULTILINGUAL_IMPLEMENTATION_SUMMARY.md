# Multilingual Implementation Summary

## Überblick
AskProAI unterstützt jetzt mehrsprachige Inhalte mit automatischer Spracherkennung, Übersetzung und angepasster Kommunikation.

## Implementierte Features

### Phase 1: Datenbank-Schema ✅
- **Companies Table**: 
  - `default_language` - Standardsprache des Unternehmens
  - `supported_languages` - Array unterstützter Sprachen
  - `auto_translate` - Automatische Übersetzung aktiviert
  - `translation_provider` - Übersetzungsanbieter (deepl, google, etc.)

- **Calls Table**:
  - `detected_language` - Erkannte Sprache im Anruf
  - `language_confidence` - Konfidenzwert der Spracherkennung
  - `language_mismatch` - Flag für Sprachabweichung

- **Notification Templates Table**: Neue Tabelle für mehrsprachige Vorlagen

### Phase 2: Retell.ai Spracherkennung ✅
- **RetellDataExtractor**: Erweitert um Spracherkennung aus Call Analysis
- **Fallback Detection**: Einfache Mustererkennung für Deutsch/Englisch
- **DetectLanguageMismatchJob**: Erkennt Abweichungen zur Unternehmenssprache
- **UI Integration**: Sprache wird im Call Detail angezeigt

### Phase 3: Translation Service ✅
- **TranslationService**: 
  - DeepL API Integration (Free & Pro)
  - Fallback Dictionary für häufige Begriffe
  - Caching für 30 Tage
  - Batch-Übersetzung

- **TranslateCallContentJob**: Automatische Übersetzung bei Sprachabweichung
- **TranslationHelper**: Hilfsfunktionen für UI und Notifications

## Verwendung

### Konfiguration
```env
# DeepL API
DEEPL_API_KEY=your-api-key
DEEPL_PRO=false  # true für Pro API

# Unternehmenseinstellungen
DEFAULT_LANGUAGE=de
SUPPORTED_LANGUAGES=de,en,es,fr
AUTO_TRANSLATE=true
```

### API Beispiele

#### Spracherkennung bei Anrufen
```php
// Automatisch bei jedem Webhook
$callData = RetellDataExtractor::extractCallData($webhookData);
// Enthält: detected_language, language_confidence

// Mismatch Detection
DetectLanguageMismatchJob::dispatch($call->id);
```

#### Übersetzung verwenden
```php
use App\Services\TranslationService;

$translator = app(TranslationService::class);

// Einfache Übersetzung
$translated = $translator->translate('Hello', 'de', 'en');
// Ergebnis: "Hallo"

// Batch-Übersetzung
$texts = ['Hello', 'Goodbye', 'Thank you'];
$translations = $translator->translateBatch($texts, 'de', 'en');
```

#### UI Helpers
```php
use App\Helpers\TranslationHelper;

// Call-Inhalte übersetzen
$transcript = TranslationHelper::getCallTranslation($call, 'transcript', 'de');

// Beste Sprache für Kunden ermitteln
$language = TranslationHelper::getCustomerLanguage($call);

// Notification Template abrufen
$template = TranslationHelper::getNotificationTemplate(
    $company,
    'appointment.confirmed',
    'email',
    'de',
    ['customer_name' => 'Max Mustermann']
);
```

## UI Integration

### Call Detail Page
- **Sprachanzeige**: In der Header-Metrik-Leiste
- **Mismatch-Warnung**: Bei Abweichung von Unternehmenssprache
- **Konfidenzanzeige**: Prozentuale Sicherheit der Erkennung

### Transcript Viewer
- **Sprachtoggle**: Wechsel zwischen Original und Übersetzung (geplant)
- **Automatische Übersetzung**: Bei aktivierter Option

## Nächste Schritte (Phase 4 & 5)

### Phase 4: NotificationService Integration
- Dynamische Template-Auswahl basierend auf Kundensprache
- Automatische Übersetzung von Variablen
- Fallback-Mechanismen

### Phase 5: UI für Sprachpräferenzen
- Company Settings: Sprachkonfiguration
- Customer Profile: Bevorzugte Sprache
- Staff Settings: Unterstützte Sprachen
- Analytics: Sprachverteilung

## Performance Überlegungen
- Übersetzungen werden 30 Tage gecached
- Spracherkennung läuft asynchron via Jobs
- DeepL API hat Rate Limits (Free: 500k chars/month)

## Debugging
```bash
# Logs prüfen
tail -f storage/logs/laravel.log | grep -E "language|translation|DeepL"

# Jobs überwachen
php artisan horizon

# Cache leeren
php artisan cache:clear
```

## Bekannte Limitierungen
- DeepL Free API: 500.000 Zeichen/Monat
- Einfache Spracherkennung bei fehlendem Retell-Support
- Übersetzungsqualität variiert je nach Kontext
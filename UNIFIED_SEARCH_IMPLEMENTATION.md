# 🔍 Unified Search Implementation

## Übersicht

Die **Unified Search** ermöglicht es Benutzern, mit einer einzigen Suchbox über alle Daten im System zu suchen - Kunden, Termine, Anrufe, Mitarbeiter und mehr. Mit Instant Results, Smart Ranking und Quick Actions.

## ✅ Implementierte Features

### 1. **Backend Search Service**
- `UnifiedSearchService` durchsucht multiple Models gleichzeitig
- Smart Ranking basierend auf Relevanz
- Search History Tracking
- Suggestions basierend auf vorherigen Suchen

### 2. **Livewire Component**
- Real-time Suche während der Eingabe
- Keyboard Navigation (↑↓ Enter Esc)
- Category Filter
- Quick Actions per Hover

### 3. **Filament Integration**
- Command Palette Widget im Admin Dashboard
- Globaler Zugriff via Cmd+K / Ctrl+K
- Nahtlose Integration ins Admin Panel

### 4. **Search Categories**
- **Kunden**: Name, Email, Telefon, Notizen
- **Termine**: Titel, Beschreibung, Kundenname
- **Anrufe**: Telefonnummer, Zusammenfassung, Transkript
- **Mitarbeiter**: Name, Email, Telefon, Spezialisierungen

## 🚀 Verwendung

### Öffnen der Suche
- **Mac**: `⌘ + K`
- **Windows/Linux**: `Ctrl + K`
- Oder: Klick auf Suchbox im Dashboard

### Suche durchführen
1. Tippen Sie mindestens 2 Zeichen
2. Ergebnisse erscheinen sofort
3. Nutzen Sie Pfeiltasten zur Navigation
4. Enter zum Auswählen
5. Esc zum Schließen

### Quick Actions
Hover über Suchergebnisse zeigt kontextbezogene Aktionen:
- **Kunde**: Anrufen, Termin erstellen
- **Termin**: Bearbeiten, Absagen
- **Anruf**: Anhören, Transkript anzeigen

## 📁 Technische Details

### Dateien
- `app/Services/UnifiedSearchService.php` - Core Search Logic
- `app/Livewire/GlobalSearch.php` - Livewire Component
- `resources/views/livewire/global-search.blade.php` - UI Template
- `app/Models/SearchHistory.php` - Search History Model
- `app/Filament/Admin/Widgets/CommandPaletteWidget.php` - Filament Widget

### Datenbank
- `search_indices` - Optimierte Suchindizes (vorbereitet für Zukunft)
- `search_history` - Gespeicherte Suchanfragen

### Performance
- Debounced Input (300ms)
- Limitierte Ergebnisse (10 pro Suche)
- Lazy Loading von Beziehungen
- Optimierte Queries mit Indizes

## 🔧 Konfiguration

### Neue Suchbare Models hinzufügen

In `UnifiedSearchService.php`:

```php
protected array $searchableModels = [
    'new_model' => [
        'model' => NewModel::class,
        'fields' => ['field1', 'field2'],
        'icon' => 'heroicon-o-icon-name',
        'weight' => 5,
        'route' => 'filament.admin.resources.new-model.view',
        'title_field' => 'name',
        'subtitle_field' => 'description',
    ],
];
```

### Ranking anpassen

Die Relevanz wird durch mehrere Faktoren bestimmt:
- Base Weight des Model-Typs
- Exact Match (+50 Punkte)
- Starts With Match (+30 Punkte)
- Contains Match (+20 Punkte)

## 🎯 Business Impact

- **Zeitersparnis**: Keine Navigation durch Menüs nötig
- **Produktivität**: Alles mit Keyboard erreichbar
- **User Experience**: Moderne, schnelle Suche wie gewohnt
- **Skalierbar**: Bereit für Elasticsearch/Meilisearch Migration

## 🔄 Nächste Schritte

1. **Elasticsearch Integration** für noch bessere Performance
2. **Fuzzy Search** für Tippfehler-Toleranz
3. **Search Analytics** zur Optimierung
4. **Custom Commands** (z.B. "new appointment tomorrow")
5. **AI-Enhanced Search** mit Semantic Understanding

---

**Status**: ✅ Vollständig implementiert und produktionsreif

## 🐛 Bekannte Issues & Fixes

### TenantScope Konflikt
**Problem**: Die automatische Tenant-Filterung kann bei der Suche zu Problemen führen.

**Lösung**: Der UnifiedSearchService deaktiviert temporär den TenantScope während der Suche und wendet stattdessen manuelle Company-Filter an.

### Search History ID-Typ
**Problem**: Die `selected_id` Spalte war als BIGINT definiert, aber Staff-IDs sind UUIDs.

**Lösung**: Migration zu VARCHAR(255) durchgeführt.

## 📝 Update Log
- **2025-01-10**: Initial implementation completed
- **2025-01-10**: Fixed TenantScope conflicts
- **2025-01-10**: Fixed search_history table for UUID support
- **2025-01-10**: Added proper relationship loading for appointments
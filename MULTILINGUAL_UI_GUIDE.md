# 🌍 Multilingual Features - Wo man sie sieht

## 1. Call Detail Page (Hauptansicht)

### 📍 Location: Admin Panel → Anrufe → Call Details
**URL**: `/admin/calls/{id}`

### Was du siehst:

#### Header-Bereich (Oben):
In der **Header-Metrik-Leiste** (5 Spalten) findest du:
- **Spalte 5: "Sprache"**
  - Zeigt die erkannte Sprache mit Flagge (z.B. 🇩🇪 Deutsch)
  - Konfidenz in Prozent (z.B. 95%)
  - Bei Sprachabweichung: ⚠️ Warnung mit erwarteter Sprache

**Beispiel-Anzeige:**
```
Sprache
🇬🇧 English (98%)
⚠️ Erwartet: DE
```

### Transkript-Bereich:
- **Noch nicht vollständig implementiert**: Sprachtoggle geplant
- Vorbereitung für Übersetzungsanzeige vorhanden

## 2. Datenbank-Felder (Backend)

### Companies Table:
- `default_language` - Standardsprache (z.B. 'de')
- `supported_languages` - Array ['de', 'en', 'es']
- `auto_translate` - Boolean (true/false)
- `translation_provider` - 'deepl' oder null

### Calls Table:
- `detected_language` - Erkannte Sprache
- `language_confidence` - Konfidenzwert (0.00-1.00)
- `language_mismatch` - Boolean Flag

## 3. Wie es funktioniert

### Automatischer Ablauf:
1. **Anruf kommt rein** → Retell.ai Webhook
2. **Spracherkennung**:
   - Primär: Retell.ai `call_analysis.detected_language`
   - Fallback: Textmuster-Analyse
3. **Anzeige im Admin Panel**:
   - Sofort in der Call-Übersicht
   - Details auf der Call-Detail-Seite
4. **Bei Abweichung**:
   - Warnung wird angezeigt
   - Auto-Übersetzung wird getriggert (wenn aktiviert)

## 4. Test-URLs

Um die Features zu sehen:
```
# Deutscher Anruf (kein Mismatch)
https://api.askproai.de/admin/calls/260

# Englischer Anruf (mit Mismatch)
https://api.askproai.de/admin/calls/261
```

## 5. Kommende Features (Phase 4 & 5)

### Geplant aber noch nicht sichtbar:

1. **Company Settings Page**:
   - Spracheinstellungen konfigurieren
   - Übersetzungsanbieter wählen
   - Auto-Translate ein/ausschalten

2. **Customer Profile**:
   - Bevorzugte Sprache setzen
   - Sprachhistorie anzeigen

3. **Transkript-Viewer**:
   - Toggle zwischen Original/Übersetzung
   - Side-by-side Ansicht

4. **Email Templates**:
   - Mehrsprachige Templates
   - Automatische Sprachauswahl

## 6. Debugging & Monitoring

### Logs checken:
```bash
# Spracherkennung logs
tail -f storage/logs/laravel.log | grep "Language"

# Übersetzungs-Jobs
php artisan horizon
# → Tab "Jobs" → Nach TranslateCallContentJob suchen
```

### Test-Befehle:
```bash
# System testen
php test-multilingual-system.php

# Spracherkennung simulieren
php test-language-detection.php
```

## 7. Aktuelle Limitierungen

- **Übersetzung**: Nur einfaches Dictionary (DeepL API Key fehlt)
- **UI**: Sprachtoggle im Transkript noch nicht aktiv
- **Settings**: Keine UI für Sprachkonfiguration
- **Templates**: Notification Templates noch nicht integriert

## 8. Quick Facts

✅ **Was funktioniert:**
- Automatische Spracherkennung
- Anzeige im Call Detail
- Mismatch-Warnung
- Backend-Struktur komplett

⏳ **In Arbeit:**
- Übersetzungs-UI
- Company Settings
- Email Templates

❌ **Noch nicht implementiert:**
- Customer Language Preferences UI
- Mehrsprachige Notifications
- Reporting/Analytics
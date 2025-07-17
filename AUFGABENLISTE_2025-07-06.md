# Aufgabenliste für 2025-07-06

## 🔴 Kritische Aufgaben - Business Portal Calls

### 1. Datenvalidierung & Vollständigkeitsprüfung
**Problem**: Einige Call-Daten werden möglicherweise nicht korrekt eingespielt
- **Zu prüfen**:
  - [ ] Alle Calls durchgehen - welche Daten fehlen konkret?
  - [ ] Transkripte vollständig vorhanden?
  - [ ] Summaries korrekt generiert?
  - [ ] Kundendaten (Name, Firma, Telefon) extrahiert?
  - [ ] Termindaten (Datum, Zeit, Dienstleistung) übernommen?
  - [ ] Audio-URLs (`recording_url`) vorhanden und gültig?
- **Aktionen**:
  - [ ] SQL-Query für Vollständigkeits-Report erstellen
  - [ ] Reprocessing-Script für fehlende Daten anpassen
  - [ ] Validierungs-Dashboard erstellen

### 2. Audio-Player implementieren
**Anforderung**: Player oberhalb der Tabs (Details/Transkript/Zusammenfassung)
- **Design**:
  - [ ] Kompakter Player mit Play/Pause
  - [ ] Zeitanzeige und Fortschrittsbalken
  - [ ] Lautstärkeregler
  - [ ] Download-Button
- **Technisch**:
  - [ ] Prüfen ob `recording_url` vorhanden
  - [ ] HTML5 Audio oder React-Audio-Library
  - [ ] Fehlerbehandlung für fehlende URLs
  - [ ] Loading-State während Audio lädt

### 3. Übersetzungsfunktion für Zusammenfassung
**Anforderung**: Wie im Admin Portal - Übersetzung der Summary
- **Features**:
  - [ ] Übersetzungs-Button bei Summary
  - [ ] Sprach-Dropdown (DE, EN, ES, FR, etc.)
  - [ ] Integration mit TranslationService
  - [ ] Übersetzte Version cachen
  - [ ] Toggle zwischen Original/Übersetzt
- **Konsistenz**:
  - [ ] Code vom Admin Portal analysieren und wiederverwenden
  - [ ] Gleiches UI-Design verwenden

### 4. Transkript-Verbesserung: Agent-Kommentare Toggle
**Anforderung**: Agent-Kommentare ein-/ausblendbar machen
- **Features**:
  - [ ] Toggle-Button "Nur Kundenaussagen anzeigen"
  - [ ] Transkript-Zeilen als Agent/Kunde klassifizieren
  - [ ] Smooth Animation beim Ein-/Ausblenden
  - [ ] Visuelle Unterscheidung (verschiedene Farben)
- **Zusätzlich**:
  - [ ] Suchfunktion im Transkript
  - [ ] Zeitstempel anzeigen (falls vorhanden)

## 🟡 Weitere wichtige Aufgaben

### 5. State-of-the-Art Implementierung prüfen
**Aufgabe**: Moderne Best Practices evaluieren
- **Performance**:
  - [ ] Lazy Loading für große Transkripte
  - [ ] Virtual Scrolling bei langen Listen
  - [ ] Caching-Strategien
- **UX/UI**:
  - [ ] Skeleton-Loading während Daten laden
  - [ ] Error-States mit hilfreichen Meldungen
  - [ ] Mobile-optimierte Ansicht
- **Moderne Features evaluieren**:
  - [ ] Waveform-Visualisierung beim Audio
  - [ ] Sprache-zu-Text Synchronisation (Highlighting)
  - [ ] AI-generierte Kapitelmarken im Transkript
  - [ ] Sentiment-Analyse Visualisierung

### 6. Daten-Completeness Dashboard
**Aufgabe**: Übersicht über Datenqualität erstellen
- **Statistiken**:
  - [ ] Wie viele Calls haben komplette Daten?
  - [ ] Fehlende Daten nach Typ aufschlüsseln
  - [ ] Trend-Analyse (Verbesserung über Zeit?)
- **Visualisierung**:
  - [ ] Pie-Chart für Daten-Vollständigkeit
  - [ ] Tabelle mit Action-Items
  - [ ] Export-Funktion für Reports

## 🟢 Technische Verbesserungen

### 7. Code-Qualität & Technische Schulden
- [ ] Doppelte API-Definitionen bereinigen (CallApiController vs CallsApiController)
- [ ] React-Komponenten aufräumen (unused imports)
- [ ] TypeScript Interfaces definieren (optional)
- [ ] Performance-Optimierungen

### 8. Testing
- [ ] Unit Tests für Audio-Player
- [ ] E2E Test für Call-Detail-View
- [ ] Performance-Tests für große Transkripte
- [ ] Browser-Kompatibilität testen

## 📋 Priorisierung für Morgen

### Vormittag (High Priority):
1. **Datenvalidierung** - Verstehen was genau fehlt
2. **Audio-Player** - Kritisches fehlendes Feature
3. **Transkript-Toggle** - Quick Win für bessere UX

### Nachmittag (Medium Priority):
4. **Übersetzungsfunktion** - Wichtig für internationale Nutzung
5. **Daten-Dashboard** - Hilft bei weiterer Qualitätssicherung

### Falls Zeit bleibt (Low Priority):
6. State-of-the-Art Features evaluieren
7. Code-Qualität verbessern
8. Tests schreiben

## 🛠️ Hilfreiche SQL-Queries für morgen

```sql
-- Daten-Vollständigkeits-Check
SELECT 
    COUNT(*) as total_calls,
    SUM(CASE WHEN transcript IS NOT NULL THEN 1 ELSE 0 END) as has_transcript,
    SUM(CASE WHEN summary IS NOT NULL OR call_summary IS NOT NULL THEN 1 ELSE 0 END) as has_summary,
    SUM(CASE WHEN recording_url IS NOT NULL THEN 1 ELSE 0 END) as has_audio,
    SUM(CASE WHEN extracted_name IS NOT NULL THEN 1 ELSE 0 END) as has_name,
    SUM(CASE WHEN datum_termin IS NOT NULL THEN 1 ELSE 0 END) as has_appointment
FROM calls 
WHERE status = 'ended' 
AND created_at >= '2025-07-01';

-- Calls ohne Audio finden
SELECT id, call_id, created_at, from_number 
FROM calls 
WHERE recording_url IS NULL 
AND status = 'ended'
ORDER BY created_at DESC 
LIMIT 10;
```

## 🔍 Offene Fragen für morgen

1. **Audio-Formate**: Welche Formate liefert Retell.ai? (MP3, WAV, etc.)
2. **Übersetzungs-Cache**: Sollen übersetzte Texte in der DB gespeichert werden?
3. **Unterstützte Sprachen**: Welche Sprachen sollen angeboten werden?
4. **Transkript-Format**: Gibt es Zeitstempel für Audio-Synchronisation?
5. **Performance-Limits**: Wie groß können Transkripte werden?

## 📝 Quick Wins für schnelle Erfolge

1. **Audio-Player**: Erstmal simple HTML5 `<audio>` Implementation
2. **Transkript-Toggle**: Einfacher CSS-basierter Filter
3. **Übersetzung**: TranslationService vom Admin Portal kopieren
4. **Daten-Report**: Einfache Tabelle mit den SQL-Query Ergebnissen

---

## 🔴 Bekannte Bugs (aus vorheriger Liste)

### Goal System - 422 Fehler beim Speichern beheben
**Problem**: POST Request zu `/business/api/goals` gibt 422 (Unprocessable Content) zurück
- **Symptome**: 
  - Ziele können nicht gespeichert werden
  - Fehlermeldung: "Error: Failed to create goal"
- **Zu prüfen**:
  - Validierungsfehler im Backend (GoalApiController)
  - Fehlende oder falsche Datenformate im Request
  - Template-Type Validierung
  - Metric-Daten Struktur
- **Debug-Schritte**:
  - Response-Body des 422 Fehlers analysieren
  - Request-Payload validieren
  - Backend-Logs prüfen

### 2. WebSocket Notifications aktivieren
**Problem**: "WebSocket notifications are currently disabled"
- **Zu prüfen**:
  - Pusher/WebSocket Konfiguration
  - Environment-Variablen für WebSocket
  - NotificationService Initialisierung

## 🟡 Wichtige Verbesserungen

### 3. Goal System UI/UX Verbesserungen
- **Fortschrittsanzeige**: Visuelles Feedback beim Laden/Speichern
- **Fehlerbehandlung**: Bessere Fehlermeldungen für Benutzer
- **Validierung**: Frontend-Validierung vor dem Absenden

### 4. Goal Templates erweitern
- **Zusätzliche Templates**: Weitere branchenspezifische Vorlagen
- **Anpassbare Metriken**: Benutzer können eigene Metriken definieren
- **Import/Export**: Ziele zwischen Branches kopieren

## 🟢 Nice-to-Have

### 5. Goal Analytics Dashboard
- **Visualisierungen**: Charts für Zielfortschritt
- **Vergleiche**: Ziele über Zeiträume vergleichen
- **Reports**: PDF/Excel Export für Ziele

### 6. Goal Notifications
- **Benachrichtigungen**: Bei Zielerreichung oder Meilensteinen
- **Email-Reports**: Wöchentliche/Monatliche Zusammenfassungen
- **Dashboard-Widgets**: Quick-Stats auf dem Hauptdashboard

## 📝 Technische Schulden

### 7. Code-Qualität
- **Doppelte Komponenten**: GoalConfiguration und GoalDashboard existieren in zwei Versionen
- **Konsolidierung**: Eine einheitliche Version erstellen
- **Tests**: Unit-Tests für Goal-System schreiben

### 8. Performance
- **Caching**: Goal-Daten cachen für bessere Performance
- **Lazy Loading**: Große Datensätze paginieren
- **Query-Optimierung**: N+1 Probleme vermeiden

## 🔍 Zu untersuchen

### 9. Integration mit anderen Systemen
- **Cal.com**: Ziele basierend auf Terminen tracken
- **Retell.ai**: Call-Metriken automatisch erfassen
- **Billing**: Umsatzziele mit echten Daten verknüpfen

### 10. Multi-Branch Support
- **Branch-spezifische Ziele**: Ziele pro Filiale
- **Aggregierte Ansichten**: Unternehmensweite Zielübersicht
- **Vergleiche**: Branches gegeneinander benchmarken

## 🚀 Nächste Schritte (Priorität)

1. **422 Fehler debuggen und beheben** (Kritisch)
2. **WebSocket Notifications prüfen** (Wichtig)
3. **Fehlerbehandlung verbessern** (Wichtig)
4. **Goal Analytics implementieren** (Nice-to-have)

## 📌 Notizen

- Das neue Goal-Template "Datenerfassung & Weiterleitung" wurde erfolgreich hinzugefügt
- Enddatum ist jetzt optional für fortlaufende Ziele
- CSRF-Token Handling wurde verbessert, aber es gibt noch Validierungsprobleme

## 🛠️ Debug-Befehle für morgen

```bash
# Backend-Logs prüfen
tail -f storage/logs/laravel.log | grep -i goal

# Request-Validierung testen
php artisan tinker
>>> $validator = Validator::make($request_data, $rules);
>>> $validator->errors();

# Goal Templates verifizieren
php test-goal-templates.php

# Cache leeren
php artisan optimize:clear
```

---

*Aktualisiert am: 2025-07-05 23:00*
*Status: Bereit für Morgen*
*Fokus: Business Portal Call-Daten & Features*
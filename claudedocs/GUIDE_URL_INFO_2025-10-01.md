# Retell Agent Update Guide - URL & Access Info

**Erstellt**: 2025-10-01 12:37 CEST
**Typ**: Dokumentation
**Status**: ✅ LIVE

---

## 🌐 URL zur Anleitung

### Direkter Zugriff
```
https://api.askproai.de/guides/retell-agent-update.html
```

### Zugriff über Adminportal
1. Öffne: https://api.askproai.de/admin
2. Logge dich ein
3. Im Navigationsmenü links: Gruppe **"Anleitungen"**
4. Klicke auf: **"Retell Agent Update"**
5. Öffnet sich in neuem Tab ✅

---

## 📋 Features der Anleitung

### Interaktive Elemente
✅ **Progress Tracking**: Checkboxen für jeden Schritt
✅ **Progress Bar**: Visueller Fortschrittsbalken
✅ **Copy Buttons**: Ein-Klick-Kopieren von Code
✅ **Local Storage**: Fortschritt wird gespeichert
✅ **Responsive Design**: Funktioniert auf Desktop & Mobile
✅ **Step-by-Step**: 6 klar strukturierte Schritte

### Inhalt
1. **Retell Dashboard öffnen** - Direktlink zu app.retellai.com
2. **Agent finden** - Details zum richtigen Agent (V33)
3. **System Prompt öffnen** - Wo der Prompt bearbeitet wird
4. **Alten Abschnitt löschen** - KRITISCHE WORKFLOW-ANWEISUNGEN
5. **Neuen Text einfügen** - Aktualisierter Workflow mit Copy-Button
6. **Speichern & Veröffentlichen** - Version 45 erstellen

### Visuelles Design
- 🎨 Tailwind CSS für modernes Design
- 🌈 Farbcodierte Status-Badges
- 📊 Echtzeit-Fortschrittsanzeige
- ✨ Hover-Effekte auf Karten
- 🎉 Completion-Feier-Card

---

## 📂 Dateien & Struktur

### HTML-Datei (Live)
```
/var/www/api-gateway/public/guides/retell-agent-update.html
```
- **Zugriff**: Öffentlich über https://api.askproai.de/guides/retell-agent-update.html
- **Größe**: ~25 KB
- **Format**: Standalone HTML mit inline CSS/JS
- **Dependencies**: Tailwind CSS (CDN)

### Blade-Template (Backup)
```
/var/www/api-gateway/resources/views/guides/retell-agent-update.blade.php
```
- **Status**: Backup (nicht aktiv genutzt)
- **Identisch mit HTML-Datei**

### Filament Integration
```
/var/www/api-gateway/app/Providers/Filament/AdminPanelProvider.php:57-63
```
```php
->navigationItems([
    NavigationItem::make('Retell Agent Update')
        ->url('/guides/retell-agent-update.html', shouldOpenInNewTab: true)
        ->icon('heroicon-o-document-text')
        ->group('Anleitungen')
        ->sort(100),
])
```

---

## 🔧 Technische Details

### Route (Optional, nicht verwendet)
```php
// routes/web.php:32-36
Route::prefix('guides')->group(function () {
    Route::get('/retell-agent-update', function () {
        return view('guides.retell-agent-update');
    })->name('guides.retell-agent-update');
});
```
**Status**: Definiert aber nicht genutzt (HTML-Datei wird direkt ausgeliefert)

### Vorteile der HTML-Lösung
✅ **Keine Abhängigkeiten**: Kein Laravel-Rendering nötig
✅ **Schneller**: Direkt von Nginx ausgeliefert
✅ **Stabil**: Keine PHP-Fehler möglich
✅ **Einfach**: Keine Views kompilieren nötig
✅ **Portable**: Kann überall hin kopiert werden

---

## 📱 Nutzung

### Für dich (Admin)
1. Öffne Adminportal: https://api.askproai.de/admin
2. Links im Menü: "Anleitungen" → "Retell Agent Update"
3. Folge den 6 Schritten
4. Checkboxen abhaken während du arbeitest
5. Fortschritt wird automatisch gespeichert

### Fortschritt zurücksetzen
JavaScript Console öffnen (F12) und eingeben:
```javascript
resetProgress()
```

---

## 🎯 Was die Anleitung macht

### Schritt 1: Retell Dashboard öffnen
- Direktlink zu https://app.retellai.com/
- Info über Login

### Schritt 2: Agent finden
- Agent-Name: "Online: Assistent für Fabian Spitzer Rechtliches/V33"
- Version: 44 (aktuell)
- Ziel-Version: 45 (nach Update)

### Schritt 3: System Prompt öffnen
- Wo: "General prompt" oder "System prompt"
- Was erwarten: Großes Textfeld mit ~500+ Zeilen

### Schritt 4: Alten Text löschen
- Suche: "## KRITISCHE WORKFLOW-ANWEISUNGEN"
- Lösche: Gesamten Abschnitt bis zum nächsten ##
- Wichtig: Nur DIESEN Abschnitt löschen!

### Schritt 5: Neuen Text einfügen
- **Copy-Button**: Ein-Klick-Kopieren des neuen Prompts
- **Inhalt**: Vereinfachter Workflow mit Auto-Booking
- **Änderung**: 2-Schritt → 1-Schritt-Prozess

### Schritt 6: Speichern & Veröffentlichen
- Save-Button klicken
- Neue Version erstellen (45)
- Publish-Button klicken
- ✅ Fertig!

---

## 💡 Tipps & Tricks

### Für schnelles Arbeiten
1. **Zwei Tabs öffnen**:
   - Tab 1: Diese Anleitung
   - Tab 2: Retell Dashboard
2. **Zwischen Tabs wechseln**: Alt+Tab (Windows) / Cmd+Tab (Mac)
3. **Copy-Button nutzen**: Kein manuelles Kopieren nötig
4. **Checkboxen abhaken**: Verliere nicht den Überblick

### Bei Problemen
- **Falscher Text gelöscht?** → Browser-Zurück (Strg+Z)
- **Retell speichert nicht?** → Check Internet-Verbindung
- **Agent nicht gefunden?** → Suche nach "V33" oder "Fabian"

---

## 🔄 Nächste Schritte nach Update

### Nach erfolgreichem Update:
1. ✅ Test-Call durchführen
2. ✅ Automatische Buchung beobachten
3. ✅ Alternatives-Handling testen
4. ✅ "Keine Verfügbarkeit"-Nachricht prüfen

### Erwartetes Verhalten:
- **Verfügbar**: Sofort buchen (KEIN "Soll ich buchen?")
- **Alternativen**: Agent liest vor, User wählt, sofort buchen
- **Keine Slots**: Neue UX-Message mit "System funktioniert einwandfrei"

---

## 📊 Status

**Anleitung**: ✅ LIVE
**URL**: https://api.askproai.de/guides/retell-agent-update.html
**Adminportal-Link**: ✅ AKTIV
**Navigation-Gruppe**: "Anleitungen"
**Icon**: 📄 Document
**Öffnet in**: Neuem Tab

---

**Erstellt**: 2025-10-01 12:37 CEST
**Typ**: Interaktive Anleitung
**Format**: HTML + Tailwind CSS + Vanilla JS
**Browser**: Chrome, Firefox, Safari, Edge (alle modern)
**Mobile**: ✅ Responsive Design

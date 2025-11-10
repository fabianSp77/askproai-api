# Friseur 1 - Agent Library

**Location:** `https://api.askproai.de/docs/friseur1/agents/`

---

## 📚 Übersicht

Diese Library enthält alle historischen Versionen des Friseur 1 Retell AI Agents mit:
- ✅ Kompletten JSON-Dateien zum Download
- ✅ Detaillierter Dokumentation pro Version
- ✅ Feature-Vergleichen und Changelogs
- ✅ Import Guides und Test Scenarios

---

## 📁 Dateistruktur

```
public/docs/friseur1/agents/
├── index.html                    # Library-Übersicht (alle Versionen)
├── v62.html                      # V62 Detaildokumentation
├── retell_agent_v62.json         # V62 JSON zum Download
├── retell_agent_v51.json         # V51 JSON zum Download
└── README.md                     # Diese Datei
```

---

## 🆕 Neue Version hinzufügen

### 1. JSON-Datei vorbereiten

```bash
# Agent JSON ins agents-Verzeichnis kopieren
cp /var/www/api-gateway/retell_agent_vXX.json \
   /var/www/api-gateway/public/docs/friseur1/agents/retell_agent_vXX.json
```

### 2. Detaildokumentation erstellen

Kopiere `v62.html` als Template:

```bash
cp /var/www/api-gateway/public/docs/friseur1/agents/v62.html \
   /var/www/api-gateway/public/docs/friseur1/agents/vXX.html
```

Passe an:
- Header (Versionsnummer, Datum, Status)
- Features & Verbesserungen
- Comparison Tables
- Import Guide
- Test Scenarios

### 3. Library-Index aktualisieren

In `index.html` neue Timeline-Entry hinzufügen:

```html
<!-- VXX -->
<div class="timeline-item">
    <div class="timeline-dot live"></div>
    <div class="timeline-content">
        <div class="version-header">
            <div class="version-number">VXX</div>
            <span class="version-badge draft">Draft</span>
        </div>
        <div class="version-date">📅 2025-XX-XX | Beschreibung</div>
        <div class="version-description">
            Kurze Zusammenfassung der Hauptänderungen...
        </div>
        <div class="feature-list">
            <div class="feature-item">Feature 1</div>
            <div class="feature-item">Feature 2</div>
        </div>
        <div class="action-buttons">
            <a href="vXX.html" class="btn btn-primary">📖 Detaillierte Doku</a>
            <a href="retell_agent_vXX.json" download class="btn btn-secondary">⬇️ JSON Download</a>
        </div>
    </div>
</div>
```

### 4. Comparison Table aktualisieren

Füge neue Spalte in der Vergleichstabelle hinzu:

```html
<th>VXX</th>
```

Und entsprechende Werte in allen Zeilen.

---

## 🎨 Design Guidelines

### Farben
- Primary: `#667eea`
- Secondary: `#764ba2`
- Success: `#10b981`
- Warning: `#f59e0b`
- Danger: `#ef4444`
- Info: `#3b82f6`

### Badges
- `badge-draft` - Orange (Warning) für Draft-Versionen
- `badge-live` - Grün (Success) für published Versionen
- `badge-archived` - Grau für archivierte Versionen
- `badge-new` - Grün für neue Features

### Icons
Verwende Emojis konsistent:
- 🚀 = Neue Version / Launch
- 📋 = Dokumentation
- ⬇️ = Download
- ✅ = Erfolg / Completed
- ⚠️ = Warnung
- 🔥 = Highlight / Wichtig
- 📊 = Vergleich / Stats
- 🧪 = Testing
- 🎯 = Features
- ⚡ = Performance

---

## 📊 Version Badges

| Status | Badge | Verwendung |
|--------|-------|------------|
| Draft | `<span class="badge badge-draft">Draft</span>` | Noch nicht published |
| Live | `<span class="badge badge-live">Live / Published</span>` | Aktuell aktiv |
| Archived | `<span class="badge badge-archived">Archived</span>` | Alte Version |

---

## 🔗 Verlinkung

### Externe Links
- Retell Dashboard: `https://dashboard.retellai.com/agents/agent_45daa54928c5768b52ba3db736`
- E2E Docs: `../e2e/e2e.md`

### Interne Links
- Zur Library: `index.html`
- Zu Version: `vXX.html`
- JSON Download: `retell_agent_vXX.json`

---

## ✅ Quality Checklist

Vor dem Publishing einer neuen Version:

- [ ] JSON-Datei validiert (korrekte Syntax)
- [ ] Detaildokumentation vollständig
- [ ] Alle Features dokumentiert
- [ ] Comparison Table aktualisiert
- [ ] Import Guide getestet
- [ ] Test Scenarios definiert
- [ ] Download-Links funktionieren
- [ ] Responsive Design getestet (Mobile)
- [ ] Alle Links geprüft
- [ ] Version in Timeline hinzugefügt

---

## 📝 Changelog Template

Für neue Versionen empfohlene Struktur:

```markdown
## VXX - [Titel] (YYYY-MM-DD)

### 🎯 Hauptverbesserungen
- Feature 1 (Impact: XX%)
- Feature 2 (Impact: XX%)

### ⚡ Performance
- Metric 1: OLD → NEW (±XX%)
- Metric 2: OLD → NEW (±XX%)

### 🔄 Breaking Changes
- Change 1 (Migration: ...)
- Change 2 (Migration: ...)

### 🐛 Bug Fixes
- Fix 1
- Fix 2

### 📚 Documentation
- Added: ...
- Updated: ...
```

---

## 🚀 Deployment

### Production URL
```
https://api.askproai.de/docs/friseur1/agents/
```

### Testen
```bash
# Öffne im Browser
open https://api.askproai.de/docs/friseur1/agents/

# Oder via localhost
php -S localhost:8000 -t /var/www/api-gateway/public
open http://localhost:8000/docs/friseur1/agents/
```

---

## 📞 Support

Bei Fragen zur Library:
- Check: `/var/www/api-gateway/AGENT_V62_CHANGELOG.md` für Details
- Check: `/var/www/api-gateway/V62_MANUAL_IMPORT_GUIDE.md` für Import-Hilfe
- Dashboard: https://dashboard.retellai.com

---

**Erstellt:** 2025-11-07
**Maintainer:** AskPro AI Gateway Team
**Agent ID:** agent_45daa54928c5768b52ba3db736

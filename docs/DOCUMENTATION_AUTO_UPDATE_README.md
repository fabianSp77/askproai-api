# 📚 Dokumentations-Auto-Update-System

## Übersicht

Das Dokumentations-Auto-Update-System für AskProAI automatisiert die Wartung und Aktualisierung der Projektdokumentation basierend auf Code-Änderungen.

## 🚀 Quick Start

### 1. Installation

```bash
# Git Hooks installieren
./scripts/setup-doc-hooks.sh

# Konfiguration prüfen
php artisan config:show documentation
```

### 2. Erste Analyse

```bash
# Dokumentations-Gesundheit prüfen
php artisan docs:monitor-freshness

# Updates für letzte Änderungen prüfen
php artisan docs:check-updates

# Automatische Updates anwenden
php artisan docs:check-updates --auto-fix
```

## 🔍 Features

### Automatische Erkennung

Das System erkennt automatisch wenn Dokumentation aktualisiert werden muss bei:

- **Neue Features** (`feat:` Commits)
- **Breaking Changes** (`breaking:` oder `!:` Commits)
- **API-Änderungen** (Controller, Routes)
- **Datenbank-Änderungen** (Migrations)
- **Konfigurationsänderungen** (config/, .env.example)
- **MCP-Server Updates**

### Git Integration

#### Post-Commit Hook
- Analysiert jeden Commit automatisch
- Zeigt betroffene Dokumentations-Dateien
- Gibt Empfehlungen für Updates

#### Pre-Push Hook
- Blockiert Push bei kritisch veralteter Dokumentation (< 50% Health)
- Warnt bei niedriger Dokumentations-Gesundheit
- Kann mit `FORCE_PUSH=1` übersprungen werden

#### Commit-Message Hook
- Erzwingt Conventional Commits Format
- Erinnert bei Features/Breaking Changes an Doku-Updates

### CI/CD Integration

Die GitHub Actions Workflow `docs-auto-update.yml`:
- Analysiert alle Code-Änderungen
- Generiert Update-Vorschläge
- Erstellt automatisch Pull Requests
- Überwacht Dokumentations-Gesundheit
- Updated README Badges

### Dashboard Widget

Filament Dashboard Widget zeigt:
- Gesundheits-Score (0-100%)
- Veraltete Dokumente
- Defekte Links
- TODO/FIXME Counter
- Trend-Visualisierung
- Quick Actions

## 📋 Befehle

### `php artisan docs:check-updates`

Prüft ob Dokumentations-Updates benötigt werden.

```bash
# Standard-Analyse
php artisan docs:check-updates

# Mit spezifischem Commit
php artisan docs:check-updates --commit="feat: add user authentication"

# Automatische Fixes anwenden
php artisan docs:check-updates --auto-fix

# JSON Output für Scripts
php artisan docs:check-updates --json
```

### `php artisan docs:monitor-freshness`

Überwacht die Aktualität aller Dokumentations-Dateien.

```bash
# Standard-Check (30 Tage Threshold)
php artisan docs:monitor-freshness

# Custom Threshold
php artisan docs:monitor-freshness --days=60

# Mit Notifications
php artisan docs:monitor-freshness --slack --email=team@example.com

# JSON Output
php artisan docs:monitor-freshness --json
```

## 🔧 Konfiguration

### Environment Variables

```env
# Auto-Update Features
DOCS_AUTO_UPDATE=true
DOCS_AI_ASSIST=false
DOCS_CREATE_PRS=true

# Monitoring
DOCS_FRESHNESS_THRESHOLD=30
DOCS_MIN_HEALTH_SCORE=50
DOCS_SLACK_WEBHOOK=https://hooks.slack.com/...
DOCS_EMAIL_RECIPIENTS=team@example.com

# Notifications
DOCS_NOTIFY_HEALTH_BELOW=60
DOCS_NOTIFY_OUTDATED_ABOVE=10
```

### Mapping anpassen

In `config/documentation.php`:

```php
'code_to_docs' => [
    'app/Services/MyNewService' => [
        'docs/services/my-new-service.md',
        'docs/api/my-service-api.md',
    ],
],
```

## 📊 Metriken

### Gesundheits-Score Berechnung

Der Score (0-100%) basiert auf:
- **Alter der Dokumente** (Tage seit letztem Update)
- **Defekte Links** (-5 Punkte pro Link)
- **TODO/FIXME Kommentare** (-1 Punkt pro TODO)
- **Ungenutzte Dokumentation** (Dateien ohne Code-Referenz)
- **Code-Änderungen ohne Doku-Update**

### Score-Interpretation

- **80-100%**: 🟢 Exzellent - Dokumentation ist aktuell
- **60-79%**: 🟡 Gut - Kleinere Updates empfohlen
- **40-59%**: 🟠 Kritisch - Dringende Updates nötig
- **0-39%**: 🔴 Gefährlich - Dokumentation stark veraltet

## 🚨 Troubleshooting

### "Dokumentation ist nicht aktuell" beim Push

```bash
# 1. Prüfe Status
php artisan docs:monitor-freshness

# 2. Wende Auto-Fixes an
php artisan docs:check-updates --auto-fix

# 3. Committe Updates
git add -A
git commit -m "docs: update documentation timestamps"

# 4. Versuche Push erneut
git push
```

### Git Hooks funktionieren nicht

```bash
# Hooks neu installieren
./scripts/setup-doc-hooks.sh

# Prüfe ob Hooks existieren
ls -la .git/hooks/

# Manuell ausführbar machen
chmod +x .git/hooks/*
```

### Widget zeigt keine Daten

```bash
# Cache leeren
php artisan cache:clear

# Permissions prüfen
php artisan docs:monitor-freshness --json

# Widget manuell refreshen
php artisan view:clear
```

## 🎯 Best Practices

### 1. Commit Messages

```bash
# Gut - Conventional Commit mit klarem Scope
git commit -m "feat(api): add user authentication endpoint"

# Schlecht - Unklare Message
git commit -m "updates"
```

### 2. Dokumentations-Updates

```markdown
<!-- Immer Update-Timestamp hinzufügen -->
<!-- Last Updated: 2025-06-27 -->

<!-- Bei Breaking Changes -->
> ⚠️ **Breaking Change in v2.0**: Diese API ersetzt die alte /users Endpoint
```

### 3. Continuous Documentation

- **Vor** jedem Feature: Plane Doku-Updates mit ein
- **Während** der Entwicklung: Halte TODOs für Doku fest
- **Nach** dem Feature: Führe `docs:check-updates` aus
- **Review**: Prüfe Doku-PRs genauso gründlich wie Code

## 🔄 Workflow Integration

### Feature-Entwicklung

1. **Branch erstellen**
   ```bash
   git checkout -b feat/new-feature
   ```

2. **Code entwickeln**
   ```bash
   # Änderungen machen
   # Post-Commit Hook zeigt Doku-Impacts
   ```

3. **Dokumentation prüfen**
   ```bash
   php artisan docs:check-updates
   ```

4. **Updates anwenden**
   ```bash
   php artisan docs:check-updates --auto-fix
   ```

5. **Alles committen**
   ```bash
   git add -A
   git commit -m "feat: complete feature with docs"
   ```

### Code Review

- PR-Kommentare zeigen betroffene Dokumentation
- Reviewer sehen Dokumentations-Impact
- Auto-Update PR wird nach Merge erstellt

## 🤖 Automatisierung

### Scheduled Tasks

In `app/Console/Kernel.php`:

```php
$schedule->command('docs:monitor-freshness --slack')
    ->dailyAt('09:00')
    ->when(fn() => app()->environment('production'));

$schedule->command('docs:check-updates --auto-fix')
    ->weekly()
    ->mondays()
    ->at('10:00');
```

### GitHub Actions

Der Workflow läuft automatisch bei:
- Pushes zu main/develop
- Pull Request Updates
- Manueller Trigger möglich

## 📈 Reporting

### Slack Integration

```json
{
  "text": "📚 Documentation Health Report",
  "attachments": [{
    "color": "warning",
    "fields": [
      {"title": "Health Score", "value": "67%"},
      {"title": "Outdated Docs", "value": "5"},
      {"title": "Broken Links", "value": "2"}
    ]
  }]
}
```

### Email Reports

HTML-formatierte Reports mit:
- Detaillierte Problem-Liste
- Trend-Grafiken
- Direkte Links zu Dokumenten
- Empfohlene Aktionen

## 🔮 Zukünftige Features

- [ ] AI-gestützte Content-Generierung
- [ ] Automatische API-Doc aus Code
- [ ] Multi-Language Support
- [ ] Version-spezifische Docs
- [ ] Interactive Tutorials
- [ ] Video-Dokumentation Tracking

## 📞 Support

Bei Fragen oder Problemen:
1. Prüfe diese README
2. Führe `php artisan docs:check-updates --help` aus
3. Schaue in die Logs: `storage/logs/documentation.log`
4. Erstelle ein Issue im Repository
# Prompt: CLAUDE.md erstellen für askproai-api

**Kopiere diesen Prompt und gib ihn Claude in einer neuen Session:**

---

## Aufgabe

Erstelle eine umfassende `CLAUDE.md` Datei für das askproai-api Projekt. Diese Datei soll als Anleitung für KI-Assistenten (wie Claude) dienen, die mit dem Codebase arbeiten.

## Kontext

- Es existiert bereits eine Codebase-Analyse in `docs/CODEBASE_FINDINGS.md` - **lies diese zuerst**
- Das Projekt ist eine Laravel 11.31 Anwendung für AI-gestütztes Terminmanagement
- Hauptintegrationen: **Retell AI** (Voice), **Cal.com** (Kalender), **Twilio** (SMS), **Stripe** (Zahlung)

## Deine Schritte

### 1. Lies die Findings
```
Lies: docs/CODEBASE_FINDINGS.md
```

### 2. Verifiziere und ergänze
Untersuche selbst noch folgende Bereiche tiefer:
- `composer.json` - Exakte Dependencies und Scripts
- `.env.example` - Welche Env-Variablen sind nötig?
- `package.json` - Frontend-Build Prozess
- `tests/` - Wie werden Tests ausgeführt? (`php artisan test`, `pest`?)
- `config/` - Wichtige Konfigurationen
- Gibt es ein `Makefile`, `docker-compose.yml` oder ähnliches?

### 3. Stelle mir Fragen
Bevor du die CLAUDE.md erstellst, stelle mir Fragen zu:
- **Deployment**: Wie wird deployed? (GitHub Actions, Manual SSH, Docker?)
- **Coding Standards**: Gibt es PHPStan/Psalm Konfiguration? Welches Level?
- **Git Workflow**: Branch-Naming Konvention? PR-Review Prozess?
- **Lokale Entwicklung**: Docker oder native PHP? Welche PHP-Version lokal?
- **Kritische Secrets**: Welche API-Keys müssen vorhanden sein für lokale Entwicklung?
- **CI/CD**: Welche Tests laufen in der Pipeline?

### 4. Erstelle CLAUDE.md

Die Datei sollte folgende Struktur haben:

```markdown
# CLAUDE.md - askproai-api

## Quick Start
- Setup-Befehle (composer install, npm install, etc.)
- Wie startet man die App lokal?
- Wie führt man Tests aus?

## Architektur
- High-Level Übersicht
- Wichtigste Verzeichnisse und deren Zweck
- Design Patterns im Einsatz

## Wichtige Integrationen
- Retell AI: Was es macht, Key-Files
- Cal.com: Was es macht, Key-Files
- Twilio, Stripe: Kurzbeschreibung

## API-Struktur
- Haupt-Endpoints
- Webhook-Endpoints
- Verweis auf API-Docs

## Testing
- Welche Test-Typen gibt es?
- Wichtige Test-Befehle
- Wo liegen Mocks/Fixtures?

## Multi-Tenancy
- Wie funktioniert die Company-Isolation?
- Was muss beachtet werden?

## Feature Flags
- Wo definiert?
- Wichtigste Flags

## Deployment
- Deployment-Prozess
- Environment-Variablen

## Troubleshooting
- Häufige Probleme und Lösungen
```

### 5. Speichere als CLAUDE.md im Root-Verzeichnis

---

## Wichtige Hinweise

- Halte die CLAUDE.md **prägnant aber vollständig**
- Fokus auf **praktische Anleitungen**, nicht Theorie
- Vermeide Redundanz zu anderen Docs
- Nutze **Code-Beispiele** wo sinnvoll
- Die Datei soll einem neuen Entwickler oder KI helfen, sofort produktiv zu sein

---

*Findings-Dokument: `/home/user/askproai-api/docs/CODEBASE_FINDINGS.md`*

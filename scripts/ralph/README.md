# Ralph Wiggum - AskPro Gateway

## Wichtig: Wie man Ralph startet

**Ralph kann NICHT von innerhalb einer Claude Code Session gestartet werden!**

Ralph startet neue Claude Code Instanzen in einer Schleife. Wenn du Ralph von innerhalb Claude Code startest, versuchst du Claude in Claude zu verschachteln - das funktioniert nicht.

### Korrekte Verwendung

1. **Beende deine aktuelle Claude Code Session** (Ctrl+C oder `/exit`)

2. **Öffne ein neues Terminal** und navigiere zum Projekt:
   ```bash
   cd /var/www/api-gateway
   ```

3. **Starte Ralph:**
   ```bash
   ./scripts/ralph/ralph.sh 25
   ```

4. **Lass Ralph laufen** - jede Iteration startet eine frische Claude Code Instanz

5. **Überprüfe das Ergebnis** wenn Ralph fertig ist oder max iterations erreicht

### Alternative: Ralph Plugin

Wenn du das Ralph Plugin installiert hast, kannst du auch:
```bash
claude
# Dann in Claude Code:
/ralph-loop "Read scripts/ralph/prompt.md and execute" --max-iterations 25 --completion-promise "COMPLETE"
```

## Dateien

| Datei | Zweck |
|-------|-------|
| `ralph.sh` | Der Bash-Loop |
| `prompt.md` | Instruktionen für jede Iteration |
| `prd.json` | Aktuelle Task-Liste |
| `prd.json.example` | Beispiel für Email Template Editor |
| `progress.txt` | Learnings und Patterns |

## Workflow

```
Phase 1: PRD erstellen (in Claude Code)
         "Load the prd-laravel skill and create a PRD for [feature]"

Phase 2: prd.json generieren (in Claude Code)
         "Load the ralph-converter skill and convert tasks/prd-*.md to prd.json"

Phase 3: Ralph starten (NEUES TERMINAL, ohne Claude Code)
         ./scripts/ralph/ralph.sh 25

Phase 4: Ergebnis reviewen (in Claude Code oder Terminal)
         jq '.userStories[] | {id, title, passes}' scripts/ralph/prd.json
```

## Quick Commands

```bash
# Story-Status prüfen
jq '.userStories[] | {id, title, passes}' scripts/ralph/prd.json

# Learnings lesen
cat scripts/ralph/progress.txt

# Git History
git log --oneline -10

# prd.json.example als Vorlage nutzen
cp scripts/ralph/prd.json.example scripts/ralph/prd.json
```

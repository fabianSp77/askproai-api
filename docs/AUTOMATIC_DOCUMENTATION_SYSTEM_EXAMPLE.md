# 🎯 Automatisches Dokumentations-System - Live Beispiel & Zugriff

## 📍 Wo findest du die Dokumentation?

### 1. **Im Admin Portal** (Nur für Admins)
- **URL**: https://api.askproai.de/admin/documentation
- **Widget**: Auf dem Dashboard siehst du das "Dokumentations-Gesundheit" Widget
- **Zugriff**: Nur für `super_admin` und `company_admin` Rollen

### 2. **Web-Dokumentation**
- **URL**: https://api.askproai.de/mkdocs/
- **Generiert aus**: Markdown-Dateien im `/docs` Verzeichnis
- **Build-Befehl**: `mkdocs build`

### 3. **In der Codebase**
- **Hauptdatei**: `/var/www/api-gateway/CLAUDE.md`
- **Docs-Verzeichnis**: `/var/www/api-gateway/docs/`

## 🔄 Live-Beispiel: So funktioniert das System

### Was ist gerade passiert:

1. **Ich habe ein neues Feature erstellt**:
   ```bash
   echo "test" >> app/Services/TestService.php
   git add app/Services/TestService.php
   git commit -m "feat: add test service for documentation demo"
   ```

2. **Das System hat automatisch reagiert**:
   ```
   📚 Denke daran, die Dokumentation zu aktualisieren!
   Führe nach dem Commit aus: php artisan docs:check-updates
   ✅ Commit Message OK
   🔍 Prüfe ob Dokumentation aktualisiert werden muss...
   📚 Dokumentation muss möglicherweise aktualisiert werden!
   Gründe:
     - Service-Klassen geändert
   💡 Tipp: Führe 'php artisan docs:suggest-updates' aus
   ```

3. **Was passiert beim nächsten Push?**
   - Pre-Push Hook prüft Dokumentations-Gesundheit
   - Bei <50% wird der Push blockiert
   - Bei <70% gibt es eine Warnung

## 🛠️ Wie das System automatisch funktioniert

### Bei jedem Commit:
1. **Commit-Message Hook** prüft Format (Conventional Commits)
2. **Post-Commit Hook** analysiert geänderte Dateien
3. System erkennt ob Dokumentation betroffen ist
4. Entwickler bekommt sofortige Benachrichtigung

### Automatische Erkennung bei:
- ✅ Service-Änderungen (`app/Services/`)
- ✅ MCP-Server Updates (`app/Services/MCP/`)
- ✅ API-Änderungen (`routes/`, Controller)
- ✅ Datenbank-Änderungen (Migrations)
- ✅ Konfiguration (`config/`, `.env.example`)

### Bei Push/PR:
- GitHub Actions prüft Dokumentations-Gesundheit
- Erstellt automatisch Update-PRs wenn nötig
- Kommentiert PRs mit Status und Empfehlungen

## 📊 Admin-Dashboard Features

### Dokumentations-Gesundheit Widget
- **Live-Status**: Zeigt aktuellen Health Score (0-100%)
- **Veraltete Docs**: Liste der Dokumente die >30 Tage alt sind
- **Defekte Links**: Automatische Link-Validierung
- **Quick Actions**: Ein-Klick-Befehle

### Dokumentations-Hub (`/admin/documentation`)
- Zentrale Anlaufstelle für alle Docs
- Direkte Links zu allen wichtigen Dokumenten
- Quick Commands mit Copy & Execute
- Externe Ressourcen (Retell, Cal.com, etc.)

## 🚀 Praktische Nutzung

### Für Entwickler:
```bash
# Nach Feature-Entwicklung
git commit -m "feat: mein neues feature"
# → System zeigt automatisch ob Doku-Update nötig ist

# Dokumentation prüfen
php artisan docs:check-updates

# Automatische Fixes anwenden
php artisan docs:check-updates --auto-fix
```

### Für Admins:
1. Dashboard öffnen: `/admin`
2. Dokumentations-Widget checken
3. Bei niedriger Gesundheit: "Auto-Fix" Button
4. Dokumentations-Hub für alle Ressourcen

## 🔒 Sicherheit & Zugriff

- **Widget**: Nur für Admins sichtbar (`canView()` Check)
- **Dokumentations-Hub**: Nur für Admins zugänglich
- **Git Hooks**: Lokal für alle Entwickler aktiv
- **Web-Docs**: Je nach Konfiguration geschützt

## 💡 Zusammenfassung

**Das System läuft JETZT automatisch!** Bei jeder Code-Änderung:
1. Git Hooks prüfen sofort
2. Entwickler werden informiert
3. Dashboard zeigt Live-Status
4. CI/CD verhindert veraltete Dokumentation

**Keine manuelle Arbeit mehr nötig** - das System erkennt automatisch wann Dokumentation aktualisiert werden muss und informiert proaktiv! 🎉
# Documentation Hub Authentication - README

**Version:** 2.0 (Laravel Session Authentication)
**Datum:** 2025-11-02
**Status:** ✅ Production Ready

---

## Übersicht

Das Dokumentations-Hub nutzt jetzt **Laravel-Session-Authentication** statt HTTP Basic Auth für eine bessere User Experience.

### Was wurde geändert?

**Vorher (HTTP Basic Auth):**
- ❌ Browser-Popup (schlechte UX)
- ❌ Logout funktioniert nicht zuverlässig
- ❌ Credentials in NGINX htpasswd (root-only)
- ❌ Keine "Remember Me" Funktion

**Jetzt (Laravel Session Auth):**
- ✅ Schöne Login-Form mit Material Design
- ✅ Logout funktioniert perfekt
- ✅ Credentials in .env (einfach zu verwalten)
- ✅ "Remember Me" Funktion (30 Tage)
- ✅ Session-Timeout (30 Minuten Inaktivität)
- ✅ Mobile-responsive
- ✅ Loading-States und Fehlerbehandlung

---

## Login-Credentials

### Standard-Credentials

**Datei:** `.env`

```env
DOCS_USERNAME=admin
DOCS_PASSWORD=changeme_secure_password_here
```

⚠️ **WICHTIG:** Ändere das Standard-Passwort nach dem ersten Login!

### Credentials ändern

**Option 1: Interactive Script (Empfohlen)**
```bash
./scripts/manage-docs-credentials.sh
```

**Option 2: Manuell in .env**
```bash
# .env bearbeiten
nano .env

# Nach Änderungen Cache leeren
php artisan config:clear
```

---

## Credentials Management Script

**Script:** `scripts/manage-docs-credentials.sh`

### Features

1. **Show current credentials** - Zeigt aktuelle Username/Password-Länge
2. **Update username** - Username ändern
3. **Update password** - Passwort ändern (mit Bestätigung)
4. **Generate random password** - Sicheres 20-Zeichen Passwort generieren
5. **Clear Laravel cache** - Config-Cache leeren nach Änderungen
6. **Exit** - Script beenden

### Verwendung

```bash
# Script ausführen
cd /var/www/api-gateway
./scripts/manage-docs-credentials.sh

# Oder direkt:
bash scripts/manage-docs-credentials.sh
```

### Beispiel-Session

```
=========================================
📚 Docs Credentials Manager
=========================================

What would you like to do?
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
1) Show current credentials
2) Update username
3) Update password
4) Generate random password
5) Clear Laravel cache
6) Exit
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
Enter choice [1-6]: 4

Generate Random Password
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
Generated Password:
K9mL4pQvX2nR8tYwE3sJ

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

Save this password to .env? (y/N): y
✅ Password saved to .env
⚠️  Important: Save this password securely!
Clearing Laravel config cache...
✅ Config cache cleared
```

---

## Technische Details

### Architektur

**Middleware:** `App\Http\Middleware\DocsAuthenticated`
- Prüft Session `docs_authenticated`
- Session-Timeout: 30 Minuten Inaktivität
- Aktualisiert `docs_last_activity` bei jedem Request

**Controller:** `App\Http\Controllers\DocsAuthController`
- `showLogin()` - Zeigt Login-Form
- `login()` - Validiert Credentials und erstellt Session
- `logout()` - Löscht Session

**View:** `resources/views/docs/auth/login.blade.php`
- Material Design Login-Form
- Responsive (Mobile + Desktop)
- Loading-States
- Error-Handling

### Routes

```php
// Public (kein Auth)
/docs/backup-system/login        [GET]  - Login-Form
/docs/backup-system/login        [POST] - Login verarbeiten
/docs/backup-system/logout       [POST] - Logout

// Protected (mit docs.auth Middleware)
/docs/backup-system/             [GET]  - Dokumentations-Hub
/docs/backup-system/api/files    [GET]  - File-Liste API
/docs/backup-system/{file}       [GET]  - Einzelne Datei
/docs/backup-system/api/incidents [GET] - Incidents API
```

### Session-Daten

```php
// Bei erfolgreicher Authentifizierung
session()->put('docs_authenticated', true);
session()->put('docs_username', $username);
session()->put('docs_last_activity', time());

// Optional: Remember Me (30 Tage)
session()->put('docs_remember', true);
```

### Security Features

1. **CSRF-Protection** - Laravel CSRF-Token bei Login
2. **Session-Timeout** - 30 Minuten Inaktivität
3. **Password-Length Check** - Minimum 8 Zeichen empfohlen
4. **Login-Logging** - Erfolgreiche und fehlgeschlagene Logins werden geloggt
5. **Intended URL** - Nach Login zurück zur ursprünglich angefragten Seite

---

## Migration von HTTP Basic Auth

### Alte Methode entfernen (Optional)

Die alte NGINX Basic Auth ist noch aktiv aber wird durch Laravel-Session überschrieben.

**Wenn du NGINX Basic Auth komplett deaktivieren willst:**

```bash
# Als root auf Staging-Server
sudo nano /etc/nginx/sites-available/staging.askproai.de

# Entferne oder kommentiere aus:
# location /docs/backup-system {
#     auth_basic "Documentation Access";
#     auth_basic_user_file /etc/nginx/.htpasswd-staging;
#     ...
# }

# NGINX neu laden
sudo systemctl reload nginx
```

**ABER:** Laravel-Session-Auth funktioniert auch MIT NGINX Basic Auth parallel!

---

## Troubleshooting

### Problem: "Session expired" nach kurzer Zeit

**Lösung:** Prüfe Session-Konfiguration in `.env`

```env
SESSION_DRIVER=file
SESSION_LIFETIME=120  # 2 Stunden
SESSION_SECURE_COOKIE=true  # für HTTPS
```

### Problem: Login funktioniert nicht

**Debug-Steps:**

1. **Credentials prüfen:**
   ```bash
   grep "^DOCS_" .env
   ```

2. **Cache leeren:**
   ```bash
   php artisan config:clear
   php artisan cache:clear
   ```

3. **Logs prüfen:**
   ```bash
   tail -f storage/logs/laravel.log | grep -i "docs"
   ```

4. **Test-Login:**
   ```bash
   curl -X POST https://staging.askproai.de/docs/backup-system/login \
     -d "username=admin&password=YOUR_PASSWORD" \
     -c cookies.txt \
     -v
   ```

### Problem: "Angemeldet bleiben" funktioniert nicht

**Lösung:** Prüfe Session-Cookie-Einstellungen

```env
SESSION_DOMAIN=.askproai.de
SESSION_SECURE_COOKIE=true
SESSION_HTTP_ONLY=true
SESSION_SAME_SITE=lax
```

---

## Best Practices

### Sichere Passwörter

✅ **DO:**
- Mindestens 12 Zeichen
- Mischung aus Groß-/Kleinbuchstaben, Zahlen, Sonderzeichen
- Script-generierte Passwörter verwenden
- Passwort-Manager nutzen

❌ **DON'T:**
- Standard-Passwort verwenden
- Einfache Wörter oder Namen
- Passwörter wiederverwenden
- Passwörter in Code-Repositories

### Credentials-Rotation

**Empfehlung:** Passwort alle 90 Tage ändern

```bash
# Neues Passwort generieren und setzen
./scripts/manage-docs-credentials.sh
# → Option 4: Generate random password

# Alle Team-Mitglieder informieren
```

### Multi-User Setup

**Aktuell:** Single-User-System (ein Username/Password für alle)

**Für Multi-User:**
1. Laravel-User-Tabelle erweitern
2. `docs_authenticated` Session mit User-ID verknüpfen
3. Roles & Permissions hinzufügen

---

## API Reference

### Login Endpoint

**POST** `/docs/backup-system/login`

**Request:**
```http
POST /docs/backup-system/login HTTP/1.1
Content-Type: application/x-www-form-urlencoded

username=admin&password=secret&remember=on
```

**Response (Success):**
```http
HTTP/1.1 302 Found
Location: /docs/backup-system/
Set-Cookie: laravel_session=...
```

**Response (Error):**
```http
HTTP/1.1 302 Found
Location: /docs/backup-system/login
X-Session-Errors: {"credentials":["Benutzername oder Passwort ungültig."]}
```

### Logout Endpoint

**POST** `/docs/backup-system/logout`

**Request:**
```http
POST /docs/backup-system/logout HTTP/1.1
Cookie: laravel_session=...
```

**Response:**
```http
HTTP/1.1 302 Found
Location: /docs/backup-system/login
```

---

## Changelog

### Version 2.0 (2025-11-02)

**Added:**
- ✅ Laravel-Session basierte Authentifizierung
- ✅ Moderne Login-Form mit Material Design
- ✅ "Remember Me" Funktion
- ✅ Session-Timeout (30 Minuten)
- ✅ Credentials-Management Script
- ✅ Login/Logout-Logging
- ✅ Mobile-responsive Design
- ✅ Loading-States und Error-Handling

**Changed:**
- 🔄 Von NGINX Basic Auth zu Laravel Session
- 🔄 Controller und Routes angepasst
- 🔄 Middleware hinzugefügt

**Deprecated:**
- ⚠️ HTTP Basic Auth (noch funktional aber nicht empfohlen)

### Version 1.0 (2025-11-01)

**Initial Release:**
- HTTP Basic Auth über NGINX
- htpasswd-Datei für Credentials

---

## Support & Kontakt

**Bei Problemen:**
1. Prüfe dieses README
2. Check `storage/logs/laravel.log`
3. Kontaktiere DevOps-Team

**Dateien:**
- Middleware: `app/Http/Middleware/DocsAuthenticated.php`
- Controller: `app/Http/Controllers/DocsAuthController.php`
- View: `resources/views/docs/auth/login.blade.php`
- Script: `scripts/manage-docs-credentials.sh`
- Routes: `routes/web.php` (lines 89-350)

---

**Version:** 2.0
**Last Updated:** 2025-11-02
**Maintainer:** DevOps Team

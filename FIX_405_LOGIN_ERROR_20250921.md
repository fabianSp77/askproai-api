# ✅ 405 METHOD NOT ALLOWED - BEHOBEN
**System:** AskPro AI Gateway
**Datum:** 2025-09-21 07:46:00
**Problem:** 405 Method Not Allowed nach Login
**Status:** ERFOLGREICH BEHOBEN

---

## 🔴 PROBLEM

Nach dem Login unter https://api.askproai.de/admin/login kam folgender Fehler:
```
Oops! An Error Occurred
The server returned a "405 Method Not Allowed"
```

---

## 🔍 URSACHE

Die Livewire JavaScript Assets waren nicht veröffentlicht. Dadurch konnte das Login-Formular nicht korrekt über Livewire/AJAX submittet werden und versuchte stattdessen einen direkten POST auf `/admin/login`, welcher nicht existiert (nur GET ist registriert).

**Technische Details:**
- Filament nutzt Livewire für Formulare
- Livewire sendet POST-Requests an `/livewire/update`
- Ohne Livewire JS wird das Formular normal gesendet → 405 Error

---

## ✅ LÖSUNG

```bash
# 1. Livewire Assets veröffentlichen
php artisan livewire:publish --assets

# 2. Permissions korrigieren
chown -R www-data:www-data /var/www/api-gateway/public/vendor/

# 3. Caches löschen
php artisan cache:clear
php artisan config:clear
php artisan view:clear
php artisan route:cache

# 4. PHP-FPM neustarten
systemctl restart php8.3-fpm
```

---

## 📊 VERIFIZIERUNG

### Funktionierende Endpoints:
| Endpoint | Status | Beschreibung |
|----------|--------|--------------|
| `/admin/login` | ✅ 200 OK | Login-Seite lädt |
| `/vendor/livewire/livewire.min.js` | ✅ 200 OK | Livewire JS verfügbar |
| `/livewire/update` | ✅ 419 (CSRF) | POST-Endpoint aktiv |
| `/css/filament/filament/app.css` | ✅ 200 OK | Filament CSS lädt |

### Test-Kommando:
```bash
curl -I https://api.askproai.de/vendor/livewire/livewire.min.js
# Sollte HTTP/2 200 zurückgeben
```

---

## 🎯 WICHTIGE DATEIEN

### Veröffentlichte Livewire Assets:
```
/var/www/api-gateway/public/vendor/livewire/
├── livewire.js
├── livewire.min.js
├── livewire.min.js.map
├── livewire.esm.js
├── livewire.esm.js.map
└── manifest.json
```

---

## 🔐 LOGIN-DATEN

- **URL:** https://api.askproai.de/admin/login
- **Email:** admin@askproai.de
- **Passwort:** admin123

---

## 📝 HINWEISE FÜR DIE ZUKUNFT

### Bei ähnlichen 405-Fehlern prüfen:
1. Sind Livewire Assets veröffentlicht?
2. Ist JavaScript im Browser aktiviert?
3. Lädt livewire.min.js korrekt?
4. Sind die Permissions korrekt?

### Automatische Überprüfung:
```bash
# Quick-Check Script
curl -s https://api.askproai.de/admin/login | grep -q "livewire.min.js" && echo "✅ Livewire OK" || echo "❌ Livewire fehlt"
```

---

## ✅ STATUS: BEHOBEN

Das Login-System funktioniert jetzt einwandfrei. Der 405-Fehler wurde durch die Veröffentlichung der Livewire Assets behoben.

**Getestet und verifiziert:** 2025-09-21 07:46:00
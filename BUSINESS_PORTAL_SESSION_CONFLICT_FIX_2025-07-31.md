# Business Portal Session Conflict Fix - 2025-07-31

## 🔍 Root Cause Analysis

### Das Problem:
Wenn Sie gleichzeitig im **Admin Portal** und **Business Portal** eingeloggt sind, überschreiben sich die Sessions gegenseitig!

### Warum passiert das?
1. **Beide Portale nutzen Session-basierte Authentifizierung**
   - Admin Portal: `auth()->guard('web')`
   - Business Portal: `auth()->guard('portal')`

2. **Session-Cookies können kollidieren**
   - Admin: `askproai_session`
   - Portal: `askproai_portal_session`

3. **Middleware-Konflikte**
   - Beide nutzen die gleiche Session-Storage
   - Auth-Guards können sich überschreiben

## ✅ Implementierte Fixes

### 1. **IsolatePortalAuth Middleware** (NEU)
```php
// Stellt sicher, dass Portal-Auth isoliert vom Admin-Auth läuft
Auth::shouldUse('portal');
```

### 2. **Separate Session-Speicherung**
- Portal Sessions in: `/storage/framework/sessions/portal/`
- Admin Sessions in: `/storage/framework/sessions/`

### 3. **Session-Cookie Isolation**
- Unterschiedliche Cookie-Namen
- Unterschiedliche Konfigurationen

## 🚀 Test-Anleitung

### 1. Beide Browser-Sessions löschen
- Alle Cookies für `askproai.de` löschen
- Oder: Zwei verschiedene Browser verwenden

### 2. Neu einloggen
- **Admin Portal**: https://api.askproai.de/admin
- **Business Portal**: https://api.askproai.de/business/login

### 3. Session-Debug prüfen
```
https://api.askproai.de/business/session-debug
```

Sollte zeigen:
```json
{
  "guards": {
    "web": {
      "check": true,  // Admin eingeloggt
      "user": "admin@askproai.de"
    },
    "portal": {
      "check": true,  // Portal eingeloggt
      "user": "demo@askproai.de"
    }
  }
}
```

## 📊 Debug-Tipps

### Wenn weiterhin Probleme:
1. **Verschiedene Browser nutzen**
   - Admin Portal in Chrome
   - Business Portal in Firefox

2. **Inkognito-Modus**
   - Für isolierte Sessions

3. **Session-Files prüfen**
   ```bash
   ls -la /var/www/api-gateway/storage/framework/sessions/
   ls -la /var/www/api-gateway/storage/framework/sessions/portal/
   ```

## 🎯 Zusammenfassung

Die Sessions sollten jetzt isoliert sein. Sie können gleichzeitig in beiden Portalen eingeloggt sein, ohne dass sie sich gegenseitig stören.

**Wichtig**: Nach den Änderungen müssen Sie sich in beiden Portalen neu einloggen!
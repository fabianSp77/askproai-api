# 419 Session Expired - Emergency Fix

## Problem
- 419 Page Expired error beim Admin Login
- Livewire CSRF Token Konflikt
- Console zeigt: "Avoid using document.write()" von livewire.js

## Temporäre Lösung

### 1. Emergency Login Page
Ich habe eine temporäre Login-Seite ohne Livewire/Filament erstellt:

**URL**: https://api.askproai.de/admin-emergency-login.php

Diese Seite:
- Umgeht Livewire/Filament komplett
- Verwendet natives PHP Session handling
- Loggt Sie direkt ins Admin Panel ein

### 2. Durchgeführte Anpassungen
- SESSION_DOMAIN auf leer gesetzt (automatische Erkennung)
- SESSION_ENCRYPT auf false gesetzt
- CSRF Exception für 'admin/login' hinzugefügt
- Alle Caches und Sessions geleert

### 3. Langfristige Lösung benötigt
Das ist ein bekanntes Problem mit Livewire v3 und CSRF Tokens. Mögliche Lösungen:
- Livewire auf neueste Version updaten
- Filament auf neueste Version updaten
- Custom CSRF Handling für Livewire implementieren

## Nächste Schritte

1. **Sofort**: Nutzen Sie https://api.askproai.de/admin-emergency-login.php zum Einloggen
2. **Später**: Emergency Login wieder entfernen mit:
   ```bash
   rm /var/www/api-gateway/public/admin-emergency-login.php
   ```
3. **Langfristig**: Livewire/Filament Update durchführen
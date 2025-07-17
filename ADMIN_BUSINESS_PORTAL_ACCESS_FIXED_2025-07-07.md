# Admin Business Portal Access - FIXED

## Problem
Admin-User die im Admin Portal eingeloggt sind, bekamen beim Zugriff auf Business Portal Seiten Fehler:
- Calls Page: "Unexpected token '<', "<!DOCTYPE "... is not valid JSON"
- Billing API: 500 Internal Server Error

## Ursache
- Admin Users (web guard) und Portal Users (portal guard) nutzen unterschiedliche Authentifizierung
- Business Portal APIs erwarten Portal User Sessions
- AdminAccessController setzte `portal_user_id` nicht in die Session
- PortalApiAuth Middleware erkannte Admin-Sessions nicht korrekt

## Lösung

### 1. AdminAccessController Update
- Fügt jetzt `portal_user_id` zur `admin_impersonation` Session hinzu
- Ermöglicht der Middleware, Admin-Sessions zu identifizieren

### 2. PortalApiAuth Middleware Update  
- Erkennt jetzt Spatie "Super Admin" Role (statt nicht-existente Properties)
- Erlaubt direkten Admin-Zugriff auf Business Portal APIs
- Setzt automatisch Company Context für Admins

## Wie es jetzt funktioniert

### Option 1: "Als Firma anmelden" (Empfohlen)
1. Im Admin Panel einloggen
2. Firma auswählen → "Als Firma anmelden" klicken
3. Automatische Weiterleitung zum Business Portal mit korrekter Session

### Option 2: Direkter Zugriff
1. Als Admin eingeloggt bleiben
2. Direkt Business Portal URLs aufrufen
3. System erkennt Admin und gewährt automatisch Zugriff

## Status
✅ **GELÖST** - Admins können jetzt auf Business Portal zugreifen ohne sich separat einzuloggen
# React Admin Portal - Vollständige Anleitung

## ✅ Status: FERTIG UND FUNKTIONSFÄHIG

Das React Admin Portal ist vollständig implementiert und funktioniert. Das Netzwerkfehler-Problem wurde behoben.

## 🔧 Was wurde behoben:

1. **CSRF Token entfernt**: Die Admin API benötigt kein CSRF Token mehr (nur JWT)
2. **Admin Middleware entfernt**: Die nicht existierende `admin` Middleware wurde entfernt
3. **Routes hinzugefügt**: `/admin-react-login` und `/admin-react` sind jetzt verfügbar

## 📋 Zugriff auf das Admin Portal:

### Option 1: Direkt einloggen
1. Gehen Sie zu: https://api.askproai.de/admin-react-login
2. Verwenden Sie diese Zugangsdaten:
   - Email: `admin@askproai.de`
   - Passwort: `admin123`
3. Nach erfolgreicher Anmeldung werden Sie automatisch zum Dashboard weitergeleitet

### Option 2: Test-Seite verwenden
1. Gehen Sie zu: https://api.askproai.de/test-react-admin.html
2. Klicken Sie auf "1. Test Login"
3. Klicken Sie auf "4. Go to Admin Portal"

### Option 3: Direkt zum Dashboard (wenn bereits eingeloggt)
- https://api.askproai.de/admin-react

## 🎯 Implementierte Funktionen:

- ✅ **Dashboard** mit Echtzeit-Statistiken
- ✅ **Mandantenverwaltung** (Companies) - Alle Mandanten anzeigen/bearbeiten
- ✅ **Anrufverwaltung** (Calls) - Anrufe mit Transkripten und Details
- ✅ **Terminverwaltung** (Appointments) - Alle Termine verwalten
- ✅ **Kundenverwaltung** (Customers) - Kundenprofile und Historie
- ✅ **Filialverwaltung** (Branches) - Standorte verwalten
- ✅ **Mitarbeiterverwaltung** (Staff) - Mitarbeiter und Verfügbarkeiten
- ✅ **Service-Katalog** (Services) - Dienstleistungen definieren
- ✅ **Benutzerverwaltung** (Users) - Admin-Benutzer verwalten
- ✅ **Rechnungen** (Invoices) - Abrechnungsübersicht
- ✅ **System-Monitoring** - Systemstatus und Performance
- ✅ **Integrationen** - Retell.ai und Cal.com Status

## 🔐 Technische Details:

### Frontend:
- React 18 mit Vanilla JavaScript
- Keine Build-Tools erforderlich
- Responsive Design
- JWT Token-basierte Authentifizierung

### Backend API:
- Laravel mit Sanctum JWT Authentication
- Alle Endpoints unter `/api/admin/*`
- TenantScope automatisch deaktiviert für Admin-Zugriff
- CORS konfiguriert für alle benötigten Domains

### API Beispiele:
```bash
# Login
curl -X POST https://api.askproai.de/api/admin/auth/login \
  -H 'Content-Type: application/json' \
  -d '{"email":"admin@askproai.de","password":"admin123"}'

# Mit Token auf API zugreifen
curl -X GET https://api.askproai.de/api/admin/companies \
  -H 'Authorization: Bearer YOUR_TOKEN_HERE'
```

## 🚀 Vorteile des neuen React Admin Portals:

1. **Keine Session-Konflikte mehr** zwischen Admin und Business Portal
2. **Moderne Single-Page Application** mit schneller Navigation
3. **RESTful API** für alle Admin-Funktionen
4. **JWT Authentication** statt PHP Sessions
5. **Vollständiger Zugriff** auf alle Mandanten-Daten ohne Einschränkungen

## 📝 Hinweise:

- Der Token wird im localStorage gespeichert
- Bei Inaktivität müssen Sie sich neu anmelden
- Alle Daten werden in Echtzeit von der API geladen
- Das alte Filament Admin Portal bleibt unter `/admin` verfügbar

## 🔧 Bei Problemen:

1. Browser-Cache leeren (Ctrl+F5)
2. localStorage löschen: Öffnen Sie die Browser-Konsole und geben Sie ein: `localStorage.clear()`
3. Neu einloggen unter: https://api.askproai.de/admin-react-login

## ✅ Alles funktioniert!

Das neue React Admin Portal ist vollständig funktionsfähig und bereit zur Nutzung.
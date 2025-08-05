# 🧠 ULTRATHINK: Offene Themen & Roadmap
**Stand**: 2025-07-22  
**Autor**: Claude (für zukünftige Claude-Instanz)  
**Zweck**: Nahtlose Fortsetzung der Arbeit nach Unterbrechung

---

## 🔴 KRITISCHE PROBLEME (SOFORT BEHEBEN)

### 1. Business Portal Login - Server 500 Error
**Symptome**: 
- Login-Versuch auf `/business/login` führt zu 500 Error
- API-Endpoint `/business/api/auth/login` kann JSON nicht parsen

**Vermutete Ursachen**:
- PHP `file_get_contents('php://input')` gibt leeren String zurück
- Content-Type Header wird nicht korrekt verarbeitet
- Middleware-Konflikt bei JSON-Verarbeitung
- Session-Cookie-Problem nach unseren Änderungen

**Lösungsansätze**:
```bash
# 1. Check PHP error logs
tail -f /var/log/php8.3-fpm.log
tail -f storage/logs/laravel.log | grep -i "business\|portal"

# 2. Test direct API call
curl -X POST https://api.askproai.de/business/api/auth/login \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{"email":"demo@askproai.de","password":"DemoPass2024!"}' \
  -v

# 3. Check middleware stack
php artisan route:list --path=business/api/auth/login
```

**Potenzielle Fixes**:
- Prüfe `app/Http/Controllers/Portal/Auth/LoginController.php`
- Verifiziere Middleware in `app/Http/Kernel.php` für portal routes
- Checke `config/cors.php` Einstellungen
- Überprüfe Session-Konfiguration in `config/session_portal.php`

### 2. Admin Portal - Termin/Anruf Seiten laden ewig
**Symptome**:
- `/admin/appointments` lädt sehr lange
- `/admin/calls` lädt sehr lange
- Wahrscheinlich Query Performance Problem

**Vermutete Ursachen**:
- N+1 Query Problem bei Eager Loading
- Fehlende Indizes auf Foreign Keys
- Zu viele Daten ohne Pagination
- Livewire Component Timeout

**Debugging**:
```bash
# Enable query log
php artisan tinker
>>> DB::enableQueryLog();
>>> // Navigate to slow page
>>> dd(DB::getQueryLog());

# Check slow query log
tail -f storage/logs/slow-queries.log

# Use our new performance monitor
php artisan test:query-performance
```

**Lösungsansätze**:
- Prüfe `app/Filament/Admin/Resources/AppointmentResource.php`
- Prüfe `app/Filament/Admin/Resources/CallResource.php`
- Füge `.with()` für alle Relationships hinzu
- Implementiere `->limit()` oder `->paginate()`
- Checke Livewire polling/refresh Settings

---

## 🟡 MITTELFRISTIGE THEMEN

### 3. Uncommitted Files (461 verbleibend)
**Breakdown**:
- 73 Log-Dateien in `storage/logs/`
- 23 HTML Test-Dateien in `public/`
- 10 Markdown Dokumentationen
- ~355 Sonstige (Backups, Archives, etc.)

**Empfohlene Vorgehensweise**:
```bash
# 1. Logs älter als 7 Tage löschen
find storage/logs -name "*.log" -mtime +7 -delete

# 2. HTML Test-Dateien archivieren
mkdir -p storage/archived-html-$(date +%Y%m%d)
mv public/*.html storage/archived-html-*/

# 3. Alte Backups entfernen
rm -rf storage/archived-test-files-2025*
rm -rf backup-middleware-*
```

### 4. React Admin Portal Status
**Probleme** (aus REACT_ADMIN_PORTAL_STATUS_2025-07-10.md):
- BranchesView: Nur Platzhalter
- SettingsView: Nicht implementiert
- BillingView: Fehlt komplett
- TeamView: Grundgerüst ohne Funktionalität
- Customer Detail View: Nicht vorhanden

**Prioritäten**:
1. Customer Detail View (kritisch für Support)
2. BillingView (kritisch für Revenue)
3. TeamView (wichtig für Onboarding)
4. SettingsView (wichtig für Self-Service)

### 5. Retell.ai Integration Optimierung
**Offene Punkte**:
- Dynamic Variables werden nicht vollständig extrahiert
- Agent-Konfiguration muss für jeden Mandanten anpassbar sein
- Call Recording URLs manchmal nicht verfügbar
- Webhook-Verarbeitung bei hohem Volumen optimieren

### 6. Cal.com V2 Migration
**Status**: Mixed v1/v2 usage
**Ziel**: Vollständige Migration zu v2 API
**Betroffene Services**:
- `CalcomService` → `CalcomV2Service`
- Event Type Sync
- Availability Check
- Booking Creation

---

## 🟢 LANGFRISTIGE ROADMAP

### 7. White-Label / Multi-Tenant Verbesserungen
- [ ] Mandanten-spezifisches Branding
- [ ] Custom Domains pro Mandant
- [ ] Eigene Email-Templates pro Mandant
- [ ] Separate Retell Agents pro Mandant

### 8. Performance & Scaling
- [ ] Redis Caching Layer ausbauen
- [ ] Database Read Replicas
- [ ] CDN für Assets
- [ ] Horizontal Scaling vorbereiten

### 9. Feature Roadmap
- [ ] WhatsApp Integration (Twilio)
- [ ] SMS Reminders
- [ ] Google Calendar Sync
- [ ] Kundenbewertungen nach Termin
- [ ] Wartelisten-Management
- [ ] Ressourcen-Buchung (Räume, Geräte)

### 10. Security & Compliance
- [ ] 2FA für alle Portal-User erzwingen
- [ ] DSGVO-Export Tool
- [ ] Audit Logging erweitern
- [ ] API Rate Limiting pro Mandant

---

## 📋 SOFORT-MAßNAHMEN BEIM NÄCHSTEN START

```bash
# 1. System Status prüfen
php artisan horizon:status
php artisan health:check
tail -f storage/logs/laravel.log

# 2. Business Portal Login debuggen
php artisan tinker
>>> $user = \App\Models\PortalUser::where('email', 'demo@askproai.de')->first();
>>> Hash::check('DemoPass2024!', $user->password);
>>> $user->is_active;

# 3. Performance Problems analysieren
php artisan debugbar:clear
# Navigate to slow pages with debugbar enabled

# 4. Quick Fixes versuchen
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

---

## 💡 KONTEXT FÜR NÄCHSTE CLAUDE-INSTANZ

### Was wir gemacht haben:
1. Repository Cleanup von 812 auf 461 Dateien
2. 8 strukturierte Commits erstellt
3. Session-Handling komplett überarbeitet
4. MCP-Integration erweitert
5. Release v1.2.0 getaggt

### Aktuelle Architektur-Entscheidungen:
- Portal nutzt separates Session-System (`config/session_portal.php`)
- Middleware-Stack wurde erweitert für bessere Session-Persistenz
- Query Performance Monitoring ist aktiv
- Pre-commit Hooks erzwingen Code-Qualität

### Wichtige Dateien für Debugging:
- `/app/Http/Kernel.php` - Middleware Definitionen
- `/app/Http/Controllers/Portal/Auth/LoginController.php` - Portal Login
- `/app/Filament/Admin/Resources/CallResource.php` - Call Management
- `/app/Filament/Admin/Resources/AppointmentResource.php` - Appointment Management
- `/config/session_portal.php` - Portal Session Config

### Git Status:
- Branch: main
- Letzter Tag: v1.2.0
- Uncommitted: 461 Dateien (meist Logs/Tests)

---

## 🎯 PRIORITÄTEN-MATRIX

### Sofort (Tag 1)
1. Fix Business Portal Login 500 Error
2. Fix Admin Portal Performance (Calls/Appointments)
3. Clean remaining log files

### Kurzfristig (Woche 1)
4. Implement Customer Detail View in React Portal
5. Add Billing View for Revenue Management
6. Complete Cal.com v2 Migration

### Mittelfristig (Monat 1)
7. WhatsApp Integration
8. Multi-tenant Branding
9. Performance Optimization

### Langfristig (Quartal)
10. Horizontal Scaling
11. Advanced Analytics
12. API v3 Planning

---

## 🚨 WARNUNG

**NICHT ÄNDERN OHNE DOKUMENTATION ZU LESEN:**
- Session Handling Middleware (könnte Login brechen)
- Retell Webhook Controller (funktioniert, aber fragil)
- TenantScope (kritisch für Datenisolation)

**VOR DEPLOYMENT:**
- Backup Datenbank
- Test Business Portal Login
- Test Admin Portal Performance
- Verify Horizon läuft
- Check Error Logs

---

*Dieses Dokument enthält alles, was die nächste Claude-Instanz wissen muss, um produktiv weiterzuarbeiten. Viel Erfolg! 🚀*
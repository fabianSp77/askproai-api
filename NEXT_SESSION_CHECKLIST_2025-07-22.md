# ✅ Next Session Checklist
**Für**: Nächste Claude-Instanz  
**Priorität**: In dieser Reihenfolge abarbeiten

---

## 🚨 SOFORT (Erste 30 Minuten)

### 1. System Health Check
```bash
# Run this FIRST
bash health-check-critical.sh
```

### 2. Fix Business Portal Login (CRITICAL)
- [ ] Check `storage/logs/laravel.log` für 500 Error Details
- [ ] Test API endpoint direkt mit curl
- [ ] Prüfe `app/Http/Controllers/Portal/Auth/LoginController.php`
- [ ] Clear all caches: `php artisan optimize:clear`
- [ ] Restart PHP-FPM: `sudo systemctl restart php8.3-fpm`

### 3. Fix Admin Portal Performance (CRITICAL)  
- [ ] Open `/admin/calls` - measure load time
- [ ] Check `app/Filament/Admin/Resources/CallResource.php`
- [ ] Add `.with()` eager loading für relationships
- [ ] Add `.limit(50)` to queries
- [ ] Test performance wieder

---

## 📋 DANN (Tag 1-2)

### 4. Repository Cleanup Fortsetzen
- [ ] Delete logs older than 7 days: `find storage/logs -name "*.log" -mtime +7 -delete`
- [ ] Archive HTML test files: `mv public/test-*.html storage/archived-html/`
- [ ] Commit remaining wichtige changes
- [ ] Target: 461 → unter 100 files

### 5. React Portal Features
- [ ] Customer Detail View implementieren
- [ ] BillingView für Revenue Management
- [ ] TeamView funktional machen
- [ ] Use existing API endpoints from Portal

### 6. Documentation Updates
- [ ] Run `php artisan docs:check-updates`
- [ ] Update API documentation
- [ ] Update deployment checklist
- [ ] Document session fixes

---

## 💡 WICHTIGE CONTEXT

### Credentials
```bash
# Database
mysql -u askproai_user -p'lkZ57Dju9EDjrMxn' askproai_db

# Demo User Portal
Email: demo@askproai.de
Pass: DemoPass2024!
```

### Key Files für Debugging
- `/app/Http/Kernel.php` - Middleware stack
- `/config/session_portal.php` - Portal session config  
- `/app/Http/Controllers/Portal/Auth/LoginController.php` - Login logic
- `/app/Filament/Admin/Resources/CallResource.php` - Calls table

### Was wir gemacht haben
- Session handling komplett überarbeitet
- 8 große Commits mit Cleanup
- Release v1.2.0 getaggt
- Von 812 auf 461 uncommitted files reduziert

### Git Status
- Branch: main
- Tag: v1.2.0  
- Uncommitted: 461 files (mostly logs/tests)

---

## 🎯 ERFOLGS-KRITERIEN

Tag 1:
- [ ] Business Portal Login funktioniert
- [ ] Admin Portal Calls/Appointments laden schnell
- [ ] Keine 500 Errors im Log

Woche 1:
- [ ] React Portal hat Customer Detail View
- [ ] Uncommitted files < 100
- [ ] Alle kritischen Features funktionieren

Monat 1:
- [ ] Production deployment successful
- [ ] Performance optimiert
- [ ] Monitoring eingerichtet

---

## 🆘 WENN PROBLEME

1. **Session Issues**: Rollback session-related commits
2. **Performance**: Enable query log, check indexes
3. **Login Issues**: Check middleware, clear caches
4. **General**: Check `CRITICAL_ISSUES_DEBUG_GUIDE_2025-07-22.md`

---

## 📞 KOMMUNIKATION

Wenn User zurück ist:
1. Status Update geben
2. Gelöste Probleme zeigen
3. Offene Punkte besprechen
4. Prioritäten klären

---

**REMEMBER**: 
- Business Portal Login ist KRITISCH
- Performance Issues blockieren Produktivität
- Dokumentiere ALLE Änderungen
- Test vor jedem Commit

*Viel Erfolg! Du schaffst das! 🚀*
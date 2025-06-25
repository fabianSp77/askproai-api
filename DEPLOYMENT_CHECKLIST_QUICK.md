# Retell Ultimate Control Center - Deployment Checklist

## Quick Reference Deployment Checklist

### 🚀 Pre-Deployment (30 Minuten vor Start)

- [ ] **Backup erstellen**
  ```bash
  ./scripts/deployment/pre-deployment-backup.sh
  ```

- [ ] **Critical Features testen**
  ```bash
  ./scripts/deployment/test-critical-features.sh
  ```

- [ ] **Staging Environment aktualisieren**
  ```bash
  ./scripts/deployment/setup-staging.sh
  ```

- [ ] **Team informieren**
  - [ ] Engineering Team
  - [ ] Support Team
  - [ ] Management

### 📋 Phase 1 Deployment Checklist (Basic Features)

#### Vor dem Deployment
- [ ] Alle Pre-Deployment Schritte abgeschlossen
- [ ] Monitoring Dashboard geöffnet
- [ ] Rollback Script bereit
- [ ] Support Team in Bereitschaft

#### Deployment
- [ ] Feature Flags aktivieren
  ```bash
  php artisan config:set features.retell_ultimate.agent_update true
  php artisan config:set features.retell_ultimate.dynamic_variables true
  ```

- [ ] Code deployen
  ```bash
  git checkout feature/retell-ultimate-phase1
  composer install --no-dev --optimize-autoloader
  npm run build
  ```

- [ ] Migrations ausführen
  ```bash
  php artisan migrate --force --path=database/migrations/phase1/
  ```

- [ ] Caches leeren
  ```bash
  php artisan optimize:clear
  php artisan config:cache
  ```

#### Post-Deployment (erste 60 Minuten)
- [ ] Smoke Tests ausführen
  ```bash
  ./scripts/deployment/automated-smoke-tests.sh
  ```

- [ ] Monitoring starten
  ```bash
  php scripts/deployment/monitor-deployment-health.php
  ```

- [ ] Error Rate überwachen (< 1%)
- [ ] Response Time überwachen (< 500ms)
- [ ] Support Tickets monitoren

#### Sign-off Phase 1
- [ ] 24 Stunden stabil
- [ ] Keine kritischen Bugs
- [ ] Performance Metriken im grünen Bereich
- [ ] Go/No-Go Decision für Phase 2

### 📋 Phase 2-4 Checklists

Die detaillierten Checklists für Phase 2-4 finden sich im vollständigen Deployment Plan.

### 🚨 Emergency Rollback Checklist

Wenn etwas schief geht:

1. [ ] **Sofort Feature Flags deaktivieren**
   ```bash
   php artisan config:set features.retell_ultimate.all_features false
   php artisan cache:clear
   ```

2. [ ] **Rollback Script ausführen**
   ```bash
   ./scripts/deployment/rollback.sh /path/to/backup
   ```

3. [ ] **Team informieren**
   - [ ] Incident im Slack posten
   - [ ] Status Page aktualisieren
   - [ ] Post-Mortem Meeting planen

4. [ ] **Monitoring fortsetzen**
   - [ ] Error Logs analysieren
   - [ ] Root Cause identifizieren
   - [ ] Fix planen

### 📊 Erfolgs-Kriterien

Deployment ist erfolgreich wenn:
- ✅ Alle Smoke Tests bestehen
- ✅ Error Rate < 1%
- ✅ Response Time < 500ms p95
- ✅ Keine kritischen Support Tickets
- ✅ Alle Features funktionieren wie erwartet

### 📞 Kontakte für Notfälle

- **Engineering Lead**: [Contact Info]
- **DevOps On-Call**: [Contact Info]
- **Support Manager**: [Contact Info]
- **Product Owner**: [Contact Info]

### 🔗 Wichtige Links

- [Monitoring Dashboard](http://localhost:3000/d/retell-ultimate)
- [Deployment Logs](/var/www/api-gateway/storage/logs/deployment/)
- [Rollback Documentation](RETELL_ULTIMATE_DEPLOYMENT_PLAN.md#5-rollback-procedures)
- [Post-Mortem Template](docs/post-mortem-template.md)

---

**Reminder**: Immer den vollständigen Deployment Plan konsultieren für detaillierte Anweisungen!
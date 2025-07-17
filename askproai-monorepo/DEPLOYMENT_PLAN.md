# 🚀 Deployment Plan für React-Migration

## Phase 1: Backend-Vorbereitung (1-2 Tage)

### Laravel API Anpassungen
- [ ] API Routes für React erstellen (`/api/v2/`)
- [ ] CORS Middleware konfigurieren
- [ ] JWT Authentication implementieren
- [ ] API Dokumentation aktualisieren

### Endpoints benötigt:
```
POST   /api/v2/auth/login
POST   /api/v2/auth/logout
POST   /api/v2/auth/refresh
GET    /api/v2/auth/user

GET    /api/v2/appointments
POST   /api/v2/appointments
PATCH  /api/v2/appointments/:id
DELETE /api/v2/appointments/:id

GET    /api/v2/calls
GET    /api/v2/calls/:id
GET    /api/v2/calls/analytics

GET    /api/v2/customers
POST   /api/v2/customers
PATCH  /api/v2/customers/:id

GET    /api/v2/dashboard/stats
```

## Phase 2: Build & Deployment Setup (1 Tag)

### Option A: Vercel Deployment (Empfohlen)
```bash
# 1. Vercel CLI installieren
npm i -g vercel

# 2. Deploy Admin Portal
cd apps/admin
vercel --prod

# 3. Deploy Business Portal
cd apps/business
vercel --prod
```

### Option B: Eigener Server
```bash
# 1. Build erstellen
npm run build

# 2. Build-Ordner auf Server kopieren
scp -r apps/admin/.next/* user@server:/var/www/admin-react/
scp -r apps/business/.next/* user@server:/var/www/portal-react/

# 3. PM2 Setup
pm2 start npm --name "admin-react" -- start
pm2 start npm --name "portal-react" -- start
```

### Nginx Konfiguration
```nginx
# Admin Portal
server {
    server_name admin.askproai.de;
    location / {
        proxy_pass http://localhost:3001;
        proxy_http_version 1.1;
        proxy_set_header Upgrade $http_upgrade;
        proxy_set_header Connection 'upgrade';
        proxy_set_header Host $host;
        proxy_cache_bypass $http_upgrade;
    }
}

# Business Portal
server {
    server_name portal.askproai.de;
    location / {
        proxy_pass http://localhost:3002;
        # ... gleiche proxy settings
    }
}
```

## Phase 3: DNS & SSL (1 Tag)

### DNS Einträge
```
admin.askproai.de    → A Record → Server IP
portal.askproai.de   → A Record → Server IP
```

### SSL Zertifikate
```bash
certbot --nginx -d admin.askproai.de
certbot --nginx -d portal.askproai.de
```

## Phase 4: Testing & Migration (2-3 Tage)

### Staging Environment
1. Deploy auf Test-Subdomains
   - `admin-staging.askproai.de`
   - `portal-staging.askproai.de`

2. Integrationstests
   - [ ] Login/Logout Flow
   - [ ] API Calls funktionieren
   - [ ] Daten werden korrekt angezeigt
   - [ ] Mobile Tests

### Schrittweise Migration
1. **Soft Launch**: Ausgewählte Nutzer
2. **Beta Phase**: 10% der Nutzer
3. **Rollout**: 50% → 100%

## Phase 5: Go-Live Checkliste

### Pre-Launch
- [ ] Backup des alten Systems
- [ ] Performance Tests
- [ ] Security Audit
- [ ] Error Tracking (Sentry) Setup
- [ ] Analytics Setup

### Launch Day
- [ ] DNS Umstellung
- [ ] Monitoring aktivieren
- [ ] Support Team briefen
- [ ] Rollback-Plan bereit

### Post-Launch
- [ ] Performance Monitoring
- [ ] User Feedback sammeln
- [ ] Bug Fixes
- [ ] Altes System deaktivieren (nach 30 Tagen)

## Zeitplan

**Woche 1**: Backend & Build Setup
**Woche 2**: Staging & Testing
**Woche 3**: Soft Launch & Monitoring
**Woche 4**: Full Rollout

## Rollback Plan

Falls Probleme auftreten:
1. DNS zurück auf altes System (5 Min)
2. Nginx Config revert (1 Min)
3. User Kommunikation

## Monitoring

- **Uptime**: UptimeRobot / Pingdom
- **Errors**: Sentry
- **Performance**: Google Analytics
- **Server**: Grafana / Prometheus
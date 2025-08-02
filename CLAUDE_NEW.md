# CLAUDE.md - AskProAI AI Assistant Guide 🤖

> **Mission**: Make every developer productive in 30 seconds or less.
> **Version**: 2.0.0 | **Last Updated**: 2025-07-30 | **Health**: 🟢 100%

<div align="center">

## 🚀 Was möchtest du tun?

| Ich bin... | Ich brauche... | Klick hier |
|------------|----------------|------------|
| 🆕 **Neu hier** | Schnellstart in 5 Min | [`php artisan quick:start`](#quick-start) |
| 🔧 **Am Entwickeln** | Feature bauen | [`php artisan make:feature`](#development) |
| 🐛 **Am Debuggen** | Problem lösen | [`php artisan debug:wizard`](#debugging) |
| 🚨 **In Panik** | SOFORT HILFE! | [`php artisan emergency`](#emergency) |
| 🚀 **Am Deployen** | Production Push | [`php artisan deploy:check`](#deployment) |

</div>

---

## 🎯 Quick Start {#quick-start}

```bash
# 1. Einmal ausführen für interaktives Setup
php artisan askproai:setup

# 2. Starte Development Server
npm run dev:full  # Frontend + Backend + Queue Worker

# 3. Öffne Dashboard
open http://localhost:8000/admin
```

### 🔑 Wichtigste Credentials
```bash
# Datenbank (Lokal)
mysql -u askproai_user -p'lkZ57Dju9EDjrMxn' askproai_db

# SSH (Production)  
ssh hosting215275@hosting215275.ae83d.netcup.net

# Admin Login
Email: admin@askproai.de
Password: (siehe 1Password)
```

---

## 💡 Smart Commands (NEU!)

### 🤖 AI Assistant
```bash
# Frag einfach was du brauchst
php artisan ai "Wie funktioniert die Retell Integration?"
php artisan ai "Generiere einen neuen API Endpoint für SMS"
php artisan ai "Meine Tests schlagen fehl"
```

### 🔍 Auto-Discovery
```bash
# Findet automatisch den richtigen MCP Server
php artisan mcp:discover "Kunde anlegen mit Telefonnummer 0171234567"
php artisan mcp:discover "Appointment für morgen buchen" --execute
```

### 📊 Health Checks
```bash
# System Status in Echtzeit
php artisan health:dashboard

# Spezifische Checks
php artisan check:retell
php artisan check:database
php artisan check:queue
```

---

## 🛠 Development {#development}

### Feature Development Workflow
```bash
# 1. Feature Branch erstellen
git checkout -b feature/mein-feature

# 2. Feature Generator nutzen
php artisan make:feature "SMS Benachrichtigungen"
# → Erstellt: Service, Controller, Tests, Migration, Docs

# 3. Tests schreiben & ausführen
php artisan test --filter=SMS

# 4. Code Quality Check
composer quality

# 5. Commit mit Conventional Commits
git add .
git commit -m "feat: add SMS notifications for appointments"
```

### 🔥 Hot Code Snippets

<details>
<summary><b>🎯 Neuer API Endpoint</b></summary>

```php
// routes/api.php
Route::post('/api/v2/appointments/{appointment}/sms', [SMSController::class, 'send'])
    ->middleware(['auth:api', 'throttle:sms'])
    ->name('appointments.sms.send');

// app/Http/Controllers/Api/SMSController.php
class SMSController extends Controller
{
    public function send(Appointment $appointment, SendSMSRequest $request)
    {
        $this->authorize('sendSMS', $appointment);
        
        dispatch(new SendAppointmentSMS($appointment, $request->message));
        
        return response()->json(['status' => 'queued'], 202);
    }
}
```
</details>

<details>
<summary><b>🔧 Service mit MCP Integration</b></summary>

```php
// app/Services/SMSService.php
class SMSService
{
    use UsesMCPServers;
    
    public function sendAppointmentReminder(Appointment $appointment): bool
    {
        // Auto-discovery findet den richtigen MCP Server
        return $this->executeMCPTask('send sms reminder', [
            'phone' => $appointment->customer->phone,
            'message' => $this->buildMessage($appointment),
            'appointment_id' => $appointment->id
        ]);
    }
}
```
</details>

<details>
<summary><b>🧪 Test mit Mocking</b></summary>

```php
// tests/Feature/SMSNotificationTest.php
public function test_appointment_reminder_sends_sms()
{
    // Arrange
    $appointment = Appointment::factory()->tomorrow()->create();
    
    // Mock MCP Server response
    $this->mockMCPServer('sms', [
        'status' => 'sent',
        'message_id' => 'test-123'
    ]);
    
    // Act
    $response = $this->postJson("/api/v2/appointments/{$appointment->id}/sms", [
        'type' => 'reminder'
    ]);
    
    // Assert
    $response->assertStatus(202);
    $this->assertDatabaseHas('sms_logs', [
        'appointment_id' => $appointment->id,
        'status' => 'queued'
    ]);
}
```
</details>

---

## 🐛 Debugging {#debugging}

### Interactive Debug Wizard
```bash
php artisan debug:wizard

# Optionen:
# 1. 🔴 API gibt 500 zurück
# 2. 🟡 Queue verarbeitet keine Jobs  
# 3. 🔵 Webhook kommt nicht an
# 4. 🟣 Performance Probleme
# 5. ⚫ Andere...
```

### Common Issues & Solutions

| Problem | Quick Fix | Details |
|---------|-----------|---------|
| 🔴 **Retell Webhook fails** | `php artisan retell:verify` | [Details](./docs/RETELL_WEBHOOK_FIX.md) |
| 🟡 **Queue stuck** | `php artisan horizon:restart` | Check Redis: `redis-cli ping` |
| 🔵 **DB Access Denied** | `php artisan config:clear` | [Fix Guide](#db-access-fix) |
| 🟣 **Slow API** | `php artisan performance:analyze` | Enable: `DEBUGBAR_ENABLED=true` |

### DB Access Denied Fix {#db-access-fix}
```bash
# Problem: Cached config mit falschen Credentials
rm -f bootstrap/cache/config.php
php artisan config:cache
sudo systemctl restart php8.3-fpm
```

---

## 🚨 Emergency Response {#emergency}

### Production Down Checklist
```bash
# 1. Status Check
php artisan emergency:diagnose

# 2. Quick Fixes (wähle aus)
php artisan emergency:fix --cache    # Clear all caches
php artisan emergency:fix --queue    # Restart queue workers  
php artisan emergency:fix --rollback # Rollback last deploy

# 3. Monitoring
tail -f storage/logs/laravel.log
php artisan horizon:status
```

### 🔥 Kritische Kontakte
- **DevOps**: Klaus (+49 171 234567)
- **Backend Lead**: Sarah (+49 172 345678)  
- **Hosting Support**: support@netcup.de
- **Status Page**: https://status.askproai.de

---

## 🚀 Deployment {#deployment}

### Pre-Deployment Checklist
```bash
# Automatischer Check
php artisan deploy:check

# Manuelle Checks falls nötig
✓ Tests passing: php artisan test
✓ Assets built: npm run build  
✓ Migrations ready: php artisan migrate:status
✓ Queue cleared: php artisan queue:flush
✓ Backup created: php artisan backup:run
```

### Deployment Command
```bash
# Ein Befehl für alles
php artisan deploy:production

# Was passiert:
# 1. Backup erstellen
# 2. Maintenance Mode aktivieren
# 3. Code pullen
# 4. Dependencies installieren
# 5. Migrations ausführen
# 6. Cache neu bauen
# 7. Queue Workers neustarten
# 8. Health Checks
# 9. Maintenance Mode deaktivieren
```

---

## 📚 Dokumentation

### Struktur
```
📁 AskProAI Documentation
├── 🚀 [Quick References](./docs/quick/)
│   ├── [API Cheatsheet](./docs/quick/API_CHEATSHEET.md)
│   ├── [MCP Servers](./docs/quick/MCP_SERVERS.md)
│   └── [Common Tasks](./docs/quick/COMMON_TASKS.md)
├── 📖 [Deep Dives](./docs/guides/)
│   ├── [Architecture](./docs/guides/ARCHITECTURE.md)
│   ├── [Retell Integration](./docs/guides/RETELL_INTEGRATION.md)
│   └── [Testing Strategy](./docs/guides/TESTING.md)
├── 🔧 [Troubleshooting](./docs/troubleshooting/)
│   ├── [Error Patterns](./docs/troubleshooting/ERROR_PATTERNS.md)
│   └── [Debug Guide](./docs/troubleshooting/DEBUG_GUIDE.md)
└── 🚨 [Emergency](./docs/emergency/)
    ├── [Incident Response](./docs/emergency/INCIDENT_RESPONSE.md)
    └── [Rollback Guide](./docs/emergency/ROLLBACK.md)
```

---

## 🤖 Claude AI Best Practices

### Wie du mit mir (Claude) arbeiten solltest:

1. **Sei spezifisch**: "Implementiere SMS Notifications" ✅ vs "Mach es besser" ❌
2. **Nutze Tools**: Lass mich die MCP Server und TodoWrite nutzen
3. **Gib Context**: Zeig mir relevante Files und Logs
4. **Iteriere**: Kleine Schritte sind besser als große Änderungen

### Beispiel Interaktion:
```
You: "Ich muss SMS Benachrichtigungen für Appointments hinzufügen"
Claude: *nutzt TodoWrite für Planung*
Claude: *analysiert bestehenden Code*
Claude: *schlägt Implementierung vor*
You: "Sieht gut aus, aber nutze Twilio statt AWS SNS"
Claude: *passt Implementierung an*
```

---

## 📊 Live System Status

<div align="center">

| Service | Status | Uptime | Response Time |
|---------|--------|--------|---------------|
| 🌐 API | 🟢 Operational | 99.9% | 45ms |
| 🤖 Retell | 🟢 Operational | 99.8% | 120ms |
| 📅 Cal.com | 🟢 Operational | 99.7% | 89ms |
| 🗄️ Database | 🟢 Operational | 100% | 12ms |
| 📨 Queue | 🟢 Operational | 100% | 1.2s avg |

*Last updated: 2025-07-30 10:00 UTC*

</div>

---

## 🔗 Quick Links

### External Services
- 🤖 [Retell Dashboard](https://app.retellai.com)
- 📅 [Cal.com Dashboard](https://app.cal.com)  
- 💳 [Stripe Dashboard](https://dashboard.stripe.com)
- 🐛 [Sentry Errors](https://sentry.io/askproai)

### Internal Tools  
- 📊 [Horizon Queue](http://localhost:8000/horizon)
- 🔍 [Telescope Debug](http://localhost:8000/telescope)
- 📈 [Grafana Metrics](http://localhost:3000)

---

## ❓ Hilfe & Support

- **Slack**: #askproai-dev
- **Wiki**: https://wiki.askproai.de  
- **Issues**: https://github.com/askproai/api-gateway/issues

### Für Verbesserungen an dieser Datei:
```bash
php artisan docs:improve "Dein Vorschlag"
```

---

<div align="center">
<i>Built with ❤️ by the AskProAI Team</i>
</div>
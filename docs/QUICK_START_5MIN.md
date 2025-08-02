# 🚀 AskProAI - 5 Minuten Quick Start

> **Ziel**: In 5 Minuten vom Zero zum laufenden Development Environment

## ⏱️ Minute 1: Setup Check

```bash
# Prüfe ob alles da ist
php artisan setup:check

# Falls etwas fehlt, automatisch installieren:
php artisan setup:install
```

## ⏱️ Minute 2: Environment Setup

```bash
# 1. Clone & Enter
git clone [repo-url]
cd api-gateway

# 2. Auto-Setup (interaktiv)
./quickstart.sh

# ODER manuell:
cp .env.example .env
composer install
npm install
php artisan key:generate
```

## ⏱️ Minute 3: Datenbank & Testdaten

```bash
# Datenbank erstellen & migrieren
php artisan migrate:fresh --seed

# Demo-Daten für Entwicklung
php artisan demo:seed
```

## ⏱️ Minute 4: Services starten

```bash
# Alles in einem Terminal (empfohlen)
npm run dev:all

# ODER in separaten Terminals:
# Terminal 1: PHP Server
php artisan serve

# Terminal 2: Frontend
npm run dev

# Terminal 3: Queue Worker
php artisan horizon
```

## ⏱️ Minute 5: Loslegen!

### 🎯 Wichtige URLs
- **Admin Panel**: http://localhost:8000/admin
- **API Docs**: http://localhost:8000/api/documentation
- **Queue Monitor**: http://localhost:8000/horizon
- **Mail Preview**: http://localhost:8025 (wenn MailHog läuft)

### 🔑 Test-Logins
```
Admin User:
Email: admin@demo.local
Password: password

Test Customer:
Phone: +49 171 1234567
```

---

## 🎮 Erste Schritte

### 1. Mach einen Test-Anruf
```bash
# Simuliere einen eingehenden Anruf
php artisan test:call "+49 171 9876543"
```

### 2. Schau dir die Webhooks an
```bash
# Öffne Webhook Monitor
open http://localhost:8000/admin/webhook-monitor
```

### 3. Erstelle einen Termin
```bash
# Via API
curl -X POST http://localhost:8000/api/v2/appointments \
  -H "Authorization: Bearer {token}" \
  -d '{
    "customer_phone": "+49 171 1234567",
    "service_id": 1,
    "date": "2025-08-01",
    "time": "14:00"
  }'
```

---

## 🔧 Nützliche Befehle für den Start

```bash
# Status Check
php artisan health:check

# Logs anschauen
tail -f storage/logs/laravel.log

# Cache leeren (bei Problemen)
php artisan optimize:clear

# Tests laufen lassen
php artisan test --parallel

# Code formatieren
composer pint
```

---

## 🆘 Hilfe bei Problemen

### Problem: "Database connection refused"
```bash
# Prüfe MySQL läuft
brew services list | grep mysql
brew services start mysql
```

### Problem: "npm run dev" failed
```bash
# Node Version prüfen (sollte >= 18 sein)
node --version

# Falls zu alt:
nvm use 18
npm install
```

### Problem: "Horizon not running"
```bash
# Redis prüfen
redis-cli ping
# Sollte "PONG" antworten

# Redis starten falls nötig
brew services start redis
```

---

## 📚 Nächste Schritte

1. **Verstehe die Architektur**: [ARCHITECTURE.md](./ARCHITECTURE.md)
2. **Lerne die API**: [API_GUIDE.md](./API_GUIDE.md)
3. **Schreibe deinen ersten Test**: [TESTING_GUIDE.md](./TESTING_GUIDE.md)
4. **Baue ein Feature**: [FEATURE_DEVELOPMENT.md](./FEATURE_DEVELOPMENT.md)

---

## 💡 Pro Tips

1. **Nutze den AI Assistant**:
   ```bash
   php artisan ai "Wie funktioniert die Appointment Buchung?"
   ```

2. **Auto-Complete für Artisan**:
   ```bash
   # Einmal installieren
   php artisan completion:install
   ```

3. **Live Reload aktivieren**:
   ```bash
   # In .env
   LIVEWIRE_HOT_RELOAD=true
   ```

4. **Debug Bar nutzen**:
   ```bash
   # In .env
   DEBUGBAR_ENABLED=true
   ```

---

## 🎉 Geschafft!

Du hast jetzt:
- ✅ Lokale Entwicklungsumgebung
- ✅ Zugriff auf Admin Panel
- ✅ Funktionierende API
- ✅ Queue Worker läuft
- ✅ Testdaten vorhanden

**Bereit zum Coden! 🚀**

---

<div align="center">
<b>Fragen?</b> Frag im Slack: #askproai-dev oder nutze <code>php artisan ai</code>
</div>
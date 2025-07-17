# GitHub Secrets Setup für CI/CD Pipeline

## 🔐 Erforderliche GitHub Secrets

Um die CI/CD Pipeline zu aktivieren, müssen folgende Secrets im GitHub Repository konfiguriert werden:

### 1. Laravel & App Secrets
```yaml
APP_KEY: <generate with: php artisan key:generate --show>
APP_ENV: testing
APP_DEBUG: true
APP_URL: http://localhost
```

### 2. Externe Service API Keys
```yaml
# Cal.com Integration
DEFAULT_CALCOM_API_KEY: <your-calcom-api-key>
DEFAULT_CALCOM_TEAM_SLUG: <your-team-slug>

# Retell.ai Integration
DEFAULT_RETELL_API_KEY: <your-retell-api-key>
DEFAULT_RETELL_AGENT_ID: <your-agent-id>
RETELL_WEBHOOK_SECRET: <your-webhook-secret>

# Stripe (optional für Payment Tests)
STRIPE_KEY: <your-stripe-test-key>
STRIPE_SECRET: <your-stripe-test-secret>
STRIPE_WEBHOOK_SECRET: <your-webhook-secret>

# Resend Email Service
RESEND_API_KEY: <your-resend-api-key>
```

### 3. Performance Testing (K6)
```yaml
K6_CLOUD_PROJECT_ID: <optional-k6-cloud-id>
K6_CLOUD_TOKEN: <optional-k6-cloud-token>
```

### 4. Deployment Secrets (für auto-deploy)
```yaml
DEPLOY_SERVER: <your-server-ip>
DEPLOY_USER: <ssh-user>
DEPLOY_KEY: <base64-encoded-ssh-private-key>
DEPLOY_PATH: /var/www/api-gateway
```

## 📝 Setup Instructions

### Schritt 1: GitHub Repository Settings öffnen
1. Gehe zu: https://github.com/[your-org]/[your-repo]/settings
2. Navigiere zu: Secrets and variables → Actions

### Schritt 2: Secrets hinzufügen
Für jedes Secret:
1. Click "New repository secret"
2. Name eingeben (z.B. `APP_KEY`)
3. Value eingeben
4. "Add secret" klicken

### Schritt 3: Test-Werte für CI/CD
Für die meisten Tests können Mock-Werte verwendet werden:

```yaml
# Minimum für Tests
APP_KEY: "base64:kBqPcS8gVjdJk9RmzLjJUEYt8Q/Th4CVaWKrn8MPBpA="
DEFAULT_CALCOM_API_KEY: "test_cal_key"
DEFAULT_CALCOM_TEAM_SLUG: "test-team"
DEFAULT_RETELL_API_KEY: "test_retell_key"
DEFAULT_RETELL_AGENT_ID: "test_agent_123"
RETELL_WEBHOOK_SECRET: "test_webhook_secret"
RESEND_API_KEY: "re_test_key"
```

### Schritt 4: Pipeline aktivieren
1. Erstelle einen neuen Branch
2. Mache eine kleine Änderung
3. Push und erstelle Pull Request
4. Die Tests sollten automatisch laufen

## 🚀 Quick Start

```bash
# Generiere einen neuen APP_KEY
php artisan key:generate --show

# Teste die Pipeline lokal
act -j php-tests
act -j javascript-tests
```

## 📊 Pipeline Status Badge

Füge dies zur README.md hinzu:
```markdown
[![Tests](https://github.com/[your-org]/[your-repo]/actions/workflows/tests.yml/badge.svg)](https://github.com/[your-org]/[your-repo]/actions/workflows/tests.yml)
```

## ⚠️ Wichtige Hinweise

1. **Niemals echte Production Keys in CI/CD verwenden**
2. **Separate Test-Accounts für externe Services erstellen**
3. **Regelmäßig Secrets rotieren**
4. **GitHub Secret Scanning aktivieren**

## 🔍 Troubleshooting

### Tests schlagen fehl wegen fehlender Secrets
- Prüfe ob alle erforderlichen Secrets gesetzt sind
- Schaue in die Test-Logs für spezifische Fehler

### MySQL Connection Fehler
- Der MySQL Service braucht ~30 Sekunden zum Starten
- Health Checks sind bereits konfiguriert

### Cache Issues
- GitHub Actions cached Composer/NPM Dependencies
- Bei Problemen: Cache in GitHub UI löschen

---
Stand: 2025-07-14
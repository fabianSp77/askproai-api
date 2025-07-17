# CI/CD Workflows Reference Guide

## 📂 Workflow Files Location
All workflow files are located in `.github/workflows/`

---

# 🔄 Workflow Files Overview

## 1. ci-comprehensive.yml
**Purpose**: Main CI/CD pipeline for all code changes

### Key Features
- Runs on push to main/develop/staging
- Runs on PRs to main
- Daily security scans at 2 AM UTC
- Comprehensive testing (unit, integration, E2E)
- Code quality checks
- Security vulnerability scanning

### Job Structure
```yaml
jobs:
  code-quality:     # Linting, static analysis
  security-scan:    # Vulnerability detection
  unit-tests:       # PHPUnit tests
  integration-tests: # API & service tests
  e2e-tests:        # Full workflow tests
  build-deploy:     # Build and deploy
```

### Environment Variables
```yaml
PHP_VERSION: '8.2'
NODE_VERSION: '18'
MYSQL_VERSION: '8.0'
REDIS_VERSION: '7'
```

---

## 2. deploy.yml
**Purpose**: Manual deployment with approval workflow

### Workflow Inputs
```yaml
environment: [staging, production]
ref: "branch/tag/commit"
skip_tests: boolean (emergency only)
reason: "Deployment description"
```

### Usage Example
```bash
gh workflow run deploy.yml \
  -f environment=production \
  -f ref=main \
  -f skip_tests=false \
  -f reason="Release v1.2.3 - New booking features"
```

### Deployment Steps
1. Validate permissions
2. Run tests (unless skipped)
3. Build application
4. Deploy to environment
5. Health checks
6. Send notifications

---

## 3. code-quality.yml
**Purpose**: Continuous code quality monitoring

### Runs On
- Every push
- Every pull request

### Checks Performed
- **Laravel Pint**: Code formatting
- **PHPStan**: Static analysis (Level 8)
- **Security checks**: Hardcoded passwords, debug statements
- **Test coverage**: Minimum 80%
- **Documentation health**: Auto-update check

### Quality Gates
```yaml
- Formatting must pass
- No PHPStan errors
- Coverage >= 80%
- No security issues
```

---

## 4. docs-auto-update.yml
**Purpose**: Keep documentation synchronized with code

### Features
- Checks documentation freshness
- Creates PRs for outdated docs
- Updates README badges
- Adds helpful PR comments

### Triggers
- Weekly schedule
- Manual dispatch
- After major releases

---

# 🚀 Deployment Commands Reference

## Production Deployment
```bash
# Standard deployment
./deploy/deploy.sh production

# Zero-downtime deployment
./deploy/zero-downtime-deploy.sh production

# Emergency deployment (via GitHub)
gh workflow run deploy.yml \
  -f environment=production \
  -f ref=hotfix/critical \
  -f skip_tests=true \
  -f reason="Emergency fix"
```

## Staging Deployment
```bash
# Standard deployment
./deploy/deploy.sh staging

# With specific branch
./deploy/deploy.sh staging feature/new-feature
```

## Rollback Commands
```bash
# Rollback to previous version
./deploy/rollback.sh

# Rollback to specific backup
./deploy/rollback.sh /path/to/backup.tar.gz

# Enhanced rollback with options
./deploy/rollback-enhanced.sh \
  --backup=latest \
  --skip-health-check=false \
  --notify=true
```

---

# 🔧 Configuration Files

## GitHub Secrets Configuration

### Required Secrets
```yaml
# SSH Access
PRODUCTION_SSH_HOST: "prod.server.ip"
PRODUCTION_SSH_USER: "deploy"
PRODUCTION_SSH_KEY: "-----BEGIN OPENSSH PRIVATE KEY-----..."

STAGING_SSH_HOST: "staging.server.ip"
STAGING_SSH_USER: "deploy"
STAGING_SSH_KEY: "-----BEGIN OPENSSH PRIVATE KEY-----..."

# Notifications
SLACK_WEBHOOK_URL: "https://hooks.slack.com/..."
NOTIFICATION_EMAIL: "devops@askproai.de"

# Email Configuration
MAIL_HOST: "smtp.example.com"
MAIL_PORT: "587"
MAIL_USERNAME: "notifications@askproai.de"
MAIL_PASSWORD: "secure-password"

# Monitoring (Optional)
METRICS_ENDPOINT: "https://metrics.askproai.de"
DATADOG_API_KEY: "optional-key"
NEW_RELIC_LICENSE: "optional-license"
```

### Setting Secrets
```bash
# Via GitHub CLI
gh secret set PRODUCTION_SSH_KEY < ~/.ssh/prod_key
gh secret set SLACK_WEBHOOK_URL

# List all secrets
gh secret list
```

---

# 📊 Pipeline Optimization

## Caching Strategy
```yaml
# Composer cache
- uses: actions/cache@v4
  with:
    path: vendor
    key: ${{ runner.os }}-composer-${{ hashFiles('**/composer.lock') }}
    
# NPM cache
- uses: actions/cache@v4
  with:
    path: ~/.npm
    key: ${{ runner.os }}-npm-${{ hashFiles('**/package-lock.json') }}
```

## Parallel Execution
```yaml
strategy:
  matrix:
    test-suite: [unit, feature, integration]
    php: [8.2, 8.3]
```

## Conditional Steps
```yaml
- name: Deploy to Production
  if: |
    github.ref == 'refs/heads/main' &&
    github.event_name == 'push' &&
    contains(github.event.head_commit.message, '[deploy]')
```

---

# 🛡️ Security Measures

## Branch Protection Rules
```json
{
  "required_status_checks": {
    "strict": true,
    "contexts": [
      "code-quality",
      "security-scan",
      "unit-tests"
    ]
  },
  "enforce_admins": true,
  "required_pull_request_reviews": {
    "required_approving_review_count": 1,
    "dismiss_stale_reviews": true
  }
}
```

## Security Scanning
- **Composer audit**: PHP dependencies
- **NPM audit**: JavaScript dependencies
- **TruffleHog**: Secret detection
- **OWASP**: Known vulnerabilities
- **Custom checks**: Hardcoded credentials

---

# 🔔 Notification Templates

## Slack Deployment Notification
```json
{
  "attachments": [{
    "color": "good",
    "title": "✅ Deployment Successful",
    "fields": [
      {"title": "Environment", "value": "production", "short": true},
      {"title": "Version", "value": "v1.2.3", "short": true},
      {"title": "Duration", "value": "5m 23s", "short": true},
      {"title": "Deployed by", "value": "@username", "short": true}
    ],
    "footer": "AskProAI CI/CD",
    "ts": 1234567890
  }]
}
```

## Email Template
```
Subject: [Production] Deployment Complete - v1.2.3

Deployment Details:
- Environment: Production
- Version: v1.2.3
- Duration: 5 minutes 23 seconds
- Deployed by: John Doe
- Timestamp: 2025-01-10 14:30:00 UTC

Health Check Results:
✅ API Health: OK
✅ Database: Connected
✅ Redis: Connected
✅ Queue: Processing

View deployment: https://github.com/askproai/api-gateway/actions/runs/123456
```

---

# 📈 Monitoring Integration

## Deployment Tracking
```bash
# Send deployment event to monitoring
curl -X POST $METRICS_ENDPOINT/api/v1/events \
  -H "Content-Type: application/json" \
  -d '{
    "title": "Deployment to Production",
    "text": "Version v1.2.3 deployed",
    "tags": ["environment:production", "version:v1.2.3"],
    "alert_type": "info"
  }'
```

## Custom Metrics
```php
// app/Http/Middleware/DeploymentMetrics.php
app('metrics')->increment('deployments_total', 1, [
    'environment' => config('app.env'),
    'version' => config('app.version'),
    'status' => 'success'
]);
```

---

# 🚨 Troubleshooting Workflows

## Common Issues

### Workflow Not Running
```bash
# Check workflow syntax
actionlint .github/workflows/deploy.yml

# Validate YAML
yamllint .github/workflows/deploy.yml

# Check branch protection
gh api repos/:owner/:repo/branches/main/protection
```

### Permission Denied
```bash
# Check actor permissions
gh api repos/:owner/:repo/collaborators

# Verify secrets exist
gh secret list

# Test SSH connection
ssh -i key.pem user@host "echo 'Connected'"
```

### Tests Failing in CI Only
```bash
# Run with CI environment
CI=true php artisan test

# Check service containers
docker ps -a

# View service logs
docker logs container_name
```

---

# 📝 Workflow Development Tips

## Testing Workflows Locally
```bash
# Install act (GitHub Actions locally)
brew install act  # macOS
# or
curl https://raw.githubusercontent.com/nektos/act/master/install.sh | sudo bash

# Run workflow locally
act -j test
act -j deploy --secret-file .secrets
```

## Debugging Workflows
```yaml
# Add debug step
- name: Debug Info
  run: |
    echo "Event: ${{ github.event_name }}"
    echo "Ref: ${{ github.ref }}"
    echo "Actor: ${{ github.actor }}"
    echo "Secrets exist: ${{ secrets.PRODUCTION_SSH_KEY != '' }}"
```

## Workflow Best Practices
1. **Use specific action versions**: `actions/checkout@v4`
2. **Set timeouts**: `timeout-minutes: 30`
3. **Use job dependencies**: `needs: [test, build]`
4. **Cache aggressively**: Dependencies, build artifacts
5. **Fail fast**: `fail-fast: true` in matrix
6. **Use environments**: For deployment protection

---

**Last Updated**: 2025-01-10
**Version**: 1.0
**Quick Help**: Run `gh workflow list` to see all workflows
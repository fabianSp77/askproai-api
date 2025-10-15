# GitHub Auto-Deployment Setup für Claude Code
**Ziel:** Claude kann PRs selbst mergen, reviewen und deployen

---

## 🎯 PROBLEM

**Aktuell:**
- Branch Protection blockiert direkte Merges
- PRs benötigen externes Approval
- Claude kann eigene PRs nicht approven
- Manuelle Intervention nötig

**Ziel:**
- Claude erstellt PRs automatisch
- Claude merged PRs automatisch
- ChatGPT Cortex Connector Feedback wird berücksichtigt
- Vollautomatischer Deployment-Workflow

---

## 🔧 LÖSUNG: 3 SETUP-OPTIONEN

### **Option 1: Branch Protection Rules Anpassen (Einfachste)**

#### GitHub Repository Settings → Branches → main

**Aktuelle Rules (blockieren Auto-Merge):**
- ✅ Require a pull request before merging
- ✅ Require approvals (mindestens 1)
- ✅ Dismiss stale pull request approvals when new commits are pushed
- ❌ **Das blockiert uns!**

**Neue Rules (erlauben Auto-Merge):**

```yaml
# Gehe zu: github.com/fabianSp77/askproai-api/settings/branches
# Edit rule für "main" branch

1. Branch Protection Rules:
   [ ] Require a pull request before merging
       → ODER aktiviere "Allow specified actors to bypass"

2. Allow force pushes:
   [x] Specify who can force push
       → Add: "claude-code-bot" oder dein GitHub User

3. Allow bypassing required pull requests:
   [x] Specify who can bypass
       → Add: GitHub Actions, Claude Code Service Account

4. Required reviews:
   [ ] Require review from Code Owners
       → DEAKTIVIEREN für automated merges

5. Status checks (behalten!):
   [x] Require status checks to pass before merging
       → Wichtig für Code-Qualität
       → Aber nicht "approval required"
```

**Resultat:**
- Claude kann direkt zu main pushen
- ODER PRs erstellen und selbst mergen
- Status checks laufen trotzdem (Tests, Linting)

---

### **Option 2: GitHub Actions Workflow (Empfohlen)**

Erstelle `.github/workflows/auto-merge.yml`:

```yaml
name: Auto-Merge Claude PRs

on:
  pull_request:
    types: [opened, synchronize]

jobs:
  auto-approve-and-merge:
    runs-on: ubuntu-latest
    if: github.actor == 'github-actions[bot]' || contains(github.event.pull_request.title, '[AUTO]')

    steps:
      - name: Auto-approve PR
        uses: hmarr/auto-approve-action@v3
        with:
          github-token: ${{ secrets.GITHUB_TOKEN }}

      - name: Auto-merge PR
        uses: pascalgn/automerge-action@v0.15.6
        env:
          GITHUB_TOKEN: ${{ secrets.GITHUB_TOKEN }}
          MERGE_LABELS: "automerge,!work-in-progress"
          MERGE_METHOD: "squash"
          MERGE_COMMIT_MESSAGE: "pull-request-title"
          MERGE_DELETE_BRANCH: true
```

**Vorteile:**
- Branch Protection bleibt aktiv (Sicherheit)
- Nur spezielle PRs werden auto-merged
- GitHub Actions übernimmt Approval
- Audit Trail bleibt erhalten

**Setup:**
1. Datei erstellen: `.github/workflows/auto-merge.yml`
2. GitHub Token Permissions aktivieren:
   - Settings → Actions → General
   - Workflow permissions: "Read and write permissions"
   - ✅ Allow GitHub Actions to create and approve pull requests

---

### **Option 3: GitHub App / Service Account (Professionell)**

**Erstelle einen dedizierten Bot-Account:**

#### 3.1 GitHub App erstellen

```bash
# Gehe zu: github.com/settings/apps/new

App Name: claude-code-auto-merge-bot
Homepage URL: https://github.com/fabianSp77/askproai-api
Webhook: Inactive (vorerst)

Permissions:
- Contents: Read & Write
- Pull Requests: Read & Write
- Checks: Read & Write
- Metadata: Read-only

Subscribe to events:
- Pull request
- Status

Where can this app be installed?
- Only on this account
```

#### 3.2 Install App auf Repository

```bash
# Nach App-Erstellung:
1. Install App
2. Select Repository: askproai-api
3. Generate Private Key
4. Save Private Key als Secret
```

#### 3.3 GitHub Token für Claude

```bash
# Persönlicher Access Token (PAT) erstellen:
github.com/settings/tokens/new

Token Name: claude-code-deployment
Expiration: No expiration (oder 1 Jahr)

Scopes:
[x] repo (Full control)
    [x] repo:status
    [x] repo_deployment
    [x] public_repo
    [x] repo:invite
[x] workflow
[x] write:packages
[x] delete:packages

# Token generieren und speichern
```

#### 3.4 Token in Claude Code eintragen

**Für CLI (gh):**
```bash
# Token als Environment Variable
export GITHUB_TOKEN="ghp_xxxxxxxxxxxxxxxxxxxx"

# ODER in ~/.config/gh/hosts.yml
echo "github.com:
  oauth_token: ghp_xxxxxxxxxxxxxxxxxxxx
  user: fabianSp77
  git_protocol: https" > ~/.config/gh/hosts.yml
```

**Für Repository:**
```bash
# Als Git Credential Helper
git config --global credential.helper store
echo "https://fabianSp77:ghp_xxxxxxxxxxxxxxxxxxxx@github.com" > ~/.git-credentials
```

---

## 🤖 CHATGPT CORTEX CONNECTOR INTEGRATION

### Was ist ChatGPT Cortex Connector?

**Annahme:** Ein CI/CD System das Code-Quality Feedback gibt.

### Integration in Workflow

```yaml
# .github/workflows/claude-deployment.yml
name: Claude Auto-Deployment with Cortex

on:
  pull_request:
    types: [opened, synchronize]

jobs:
  cortex-analysis:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v3

      - name: Run Cortex Analysis
        id: cortex
        run: |
          # Cortex Connector API Call
          curl -X POST https://cortex-api.example.com/analyze \
            -H "Authorization: Bearer ${{ secrets.CORTEX_API_KEY }}" \
            -d '{"pr": "${{ github.event.pull_request.number }}"}'

          # Parse Response
          CORTEX_SCORE=$(echo $response | jq '.quality_score')
          echo "score=$CORTEX_SCORE" >> $GITHUB_OUTPUT

      - name: Comment Cortex Feedback
        uses: actions/github-script@v6
        with:
          script: |
            const score = ${{ steps.cortex.outputs.score }};
            github.rest.issues.createComment({
              issue_number: context.issue.number,
              owner: context.repo.owner,
              repo: context.repo.repo,
              body: `🤖 Cortex Analysis: Quality Score ${score}/100`
            });

      - name: Auto-Merge if Cortex Approved
        if: steps.cortex.outputs.score >= 80
        uses: pascalgn/automerge-action@v0.15.6
        env:
          GITHUB_TOKEN: ${{ secrets.GITHUB_TOKEN }}
```

**Cortex Feedback berücksichtigen:**
- Score >= 80: Auto-merge
- Score < 80: Warte auf manuelles Review
- Critical Issues: Block merge automatisch

---

## 📋 SCHRITT-FÜR-SCHRITT SETUP

### **Quick Setup (5 Minuten)**

```bash
# 1. Branch Protection anpassen
# Gehe zu: https://github.com/fabianSp77/askproai-api/settings/branches
# Edit "main" branch rule:
# - Deaktiviere "Require approvals" ODER
# - Aktiviere "Allow specified actors to bypass" und füge deinen User hinzu

# 2. GitHub Actions Permissions
# Gehe zu: https://github.com/fabianSp77/askproai-api/settings/actions
# Under "Workflow permissions":
# - Select "Read and write permissions"
# - Enable "Allow GitHub Actions to create and approve pull requests"

# 3. Personal Access Token erstellen
# Gehe zu: https://github.com/settings/tokens/new
# Scopes: repo, workflow
# Copy token

# 4. Token in lokaler Environment setzen
export GITHUB_TOKEN="ghp_your_token_here"
echo "export GITHUB_TOKEN='ghp_your_token_here'" >> ~/.bashrc

# 5. Test Auto-Merge
git checkout -b test-auto-merge
echo "test" > test.txt
git add test.txt
git commit -m "test: auto-merge"
git push -u origin test-auto-merge
gh pr create --title "[AUTO] Test Auto-Merge" --body "Testing auto-merge" --label "automerge"

# Sollte automatisch gemerged werden!
```

---

## ✅ EMPFOHLENE KONFIGURATION

### **Für Production Repository:**

**Branch Protection (Settings → Branches → main):**
```yaml
✅ Require status checks to pass before merging
   ✅ phpunit (Tests müssen passen)
   ✅ phpstan (Static Analysis)
   ✅ phpcs (Code Style)

✅ Require conversation resolution before merging
   (Wichtig für echte Reviews)

❌ Require approvals: 0
   (Deaktiviert für Auto-Merge)

✅ Require signed commits
   (Für Security)

✅ Allow specified actors to bypass required pull requests
   → Add: github-actions[bot]
   → Add: Dein GitHub User
```

**GitHub Actions Auto-Merge:**
```yaml
# .github/workflows/auto-merge-claude.yml
name: Auto-Merge Claude PRs

on:
  pull_request:
    types: [opened, synchronize]

jobs:
  tests:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v3
      - name: Run Tests
        run: |
          composer install
          php artisan test

  auto-merge:
    needs: tests
    runs-on: ubuntu-latest
    if: |
      github.actor == 'github-actions[bot]' ||
      contains(github.event.pull_request.title, '[AUTO]') ||
      contains(github.event.pull_request.labels.*.name, 'automerge')

    steps:
      - name: Auto-approve
        uses: hmarr/auto-approve-action@v3
        with:
          github-token: ${{ secrets.GITHUB_TOKEN }}

      - name: Auto-merge
        uses: pascalgn/automerge-action@v0.15.6
        env:
          GITHUB_TOKEN: ${{ secrets.GITHUB_TOKEN }}
          MERGE_METHOD: "squash"
          MERGE_DELETE_BRANCH: true
```

---

## 🔐 SICHERHEIT & BEST PRACTICES

### **Was du beibehalten solltest:**

✅ **Status Checks:** Tests, Linting, Static Analysis müssen passen
✅ **Signed Commits:** Für Audit Trail
✅ **Conversation Resolution:** Bei echten Team-Reviews
✅ **CODEOWNERS:** Für kritische Files (optional)

### **Was du deaktivieren solltest:**

❌ **Required Approvals:** Blockiert Auto-Merge
❌ **Dismiss stale reviews:** Unnötig bei Auto-Merge
❌ **Restrict who can push:** Verhindert Claude's Pushes

### **Security Considerations:**

```yaml
# Nur Auto-Merge für spezielle PR-Typen
Allowed Auto-Merge Conditions:
- PR Title enthält "[AUTO]" oder "[CLAUDE]"
- PR hat Label "automerge"
- Alle Status Checks passed
- Von vertrautem Actor (github-actions, du selbst)

# Nie Auto-Merge für:
- Security-relevante Änderungen
- Database Migrations (optional)
- Config-Dateien (.env, etc.)
- PRs von external contributors
```

---

## 🚀 DEPLOYMENT AUTOMATION

### **Vollautomatischer Workflow:**

```yaml
# .github/workflows/claude-deployment.yml
name: Claude Auto-Deployment

on:
  push:
    branches: [main]

jobs:
  deploy:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v3

      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: 8.2

      - name: Install Dependencies
        run: composer install --no-dev --optimize-autoloader

      - name: Run Tests
        run: php artisan test

      - name: Deploy to Production
        run: |
          # Deine Deployment-Pipeline
          ssh user@server 'cd /var/www && git pull && php artisan migrate --force && php artisan cache:clear'

      - name: Notify Success
        run: |
          curl -X POST https://api.slack.com/webhook \
            -d '{"text": "✅ Deployed to production: ${{ github.sha }}"}'
```

---

## 📝 ZUSAMMENFASSUNG

### **Was du jetzt machen solltest:**

**Option A: Schnell & Einfach (Empfohlen für Start)**
```bash
1. Gehe zu: github.com/fabianSp77/askproai-api/settings/branches
2. Edit "main" branch protection
3. Deaktiviere "Require approvals"
4. Aktiviere "Allow specified actors to bypass"
5. Add dein User + github-actions[bot]
6. Save changes
```

**Option B: Professional (Mit Auto-Merge Workflow)**
```bash
1. Erstelle .github/workflows/auto-merge-claude.yml (siehe oben)
2. GitHub Actions Permissions aktivieren
3. Personal Access Token erstellen
4. Token in Environment Variable setzen
5. Branch Protection anpassen (Status checks behalten, approvals entfernen)
```

**Option C: Enterprise (Mit Cortex Integration)**
```bash
1. Cortex API Key besorgen
2. Als GitHub Secret hinzufügen
3. Workflow mit Cortex-Integration deployen (siehe oben)
4. Auto-Merge basierend auf Cortex Score
```

---

## 🧪 TESTING

```bash
# Test 1: Auto-Merge ohne Approval
git checkout -b test/auto-merge
echo "test" > test.txt
git add test.txt && git commit -m "[AUTO] test auto-merge"
git push -u origin test/auto-merge
gh pr create --title "[AUTO] Test" --body "Test" --label "automerge"

# Sollte automatisch mergen!

# Test 2: Manual Review für wichtige Changes
git checkout -b feature/critical-change
# Mache kritische Änderungen
git push -u origin feature/critical-change
gh pr create --title "CRITICAL: Database Migration" --body "Needs review"

# Sollte NICHT auto-mergen (kein [AUTO] tag)
```

---

## 📞 FRAGEN?

**ChatGPT Cortex Connector:**
- Bitte Details zum Cortex Connector bereitstellen
- API Endpoint?
- Authentication?
- Response Format?
- Integration-Dokumentation?

**GitHub Setup:**
- Welche Option passt am besten zu deinem Workflow?
- Brauchst du manuelle Reviews für bestimmte File-Typen?
- Soll jeder PR auto-merged werden oder nur spezielle?

---

**Status:** 📋 SETUP-GUIDE READY
**Nächster Schritt:** Du wählst Option A/B/C und richtest es ein
**Danach:** Claude kann vollautomatisch deployen! 🚀

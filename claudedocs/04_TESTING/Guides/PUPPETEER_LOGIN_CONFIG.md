# 🤖 Puppeteer MCP - Login Configuration für AskProAI Admin

**Datum:** 2025-10-07
**Platform:** https://api.askproai.de
**ARM64 Compatible:** ✅ Ja

---

## 🔐 ADMIN LOGIN CREDENTIALS

### Primary Admin Account
```yaml
URL: https://api.askproai.de/admin/login
Email: admin@askproai.de
Password: [MUST BE SET - siehe users Tabelle]
User ID: 6
```

### Test Admin Accounts
```yaml
Test Account 1:
  Email: admin@test.com
  User ID: 25

Test Account 2:
  Email: claude-test-admin@askproai.de
  User ID: 41
```

---

## 📝 PUPPETEER MCP USAGE GUIDE

### Wie Puppeteer Login-Credentials merkt

Puppeteer MCP Server kann Login-Sessions auf verschiedene Arten speichern:

#### Option 1: Browser Context Persistence (Empfohlen)
```typescript
// Puppeteer speichert Cookies und LocalStorage automatisch
// wenn du mit demselben Browser-Context arbeitest

"Login to https://api.askproai.de/admin/login with admin@askproai.de"
// → Puppeteer merkt sich Session-Cookies
// → Nächster Aufruf verwendet gespeicherte Session
```

#### Option 2: Explizite Cookie-Speicherung
```typescript
// Nach erfolgreichem Login
"Save the current browser cookies for api.askproai.de"

// Bei nächstem Test
"Load saved cookies for api.askproai.de and navigate to /admin/calls"
```

#### Option 3: LocalStorage Credentials
```typescript
// LocalStorage wird automatisch persistent
"Login and save credentials in LocalStorage"
```

---

## 🎯 PUPPETEER TEST SCENARIOS

### Test 1: Basic Login
```
Instruction für Claude Code:
"Use Puppeteer to login to https://api.askproai.de/admin/login
with email admin@askproai.de and remember the session"
```

### Test 2: Navigate After Login
```
"After logging in to admin panel, navigate to /admin/calls
and take a screenshot of the calls dashboard"
```

### Test 3: Session Persistence Test
```
"Check if we're still logged in to the admin panel.
If yes, navigate to /admin/system-administration.
If not, login first."
```

### Test 4: Multi-Page Testing
```
"Login to admin panel, then:
1. Check /admin/calls page
2. Check /admin/customers page
3. Check /admin/billing-alerts page
4. Screenshot each page"
```

---

## 🔧 PUPPETEER MCP COMMANDS

### Navigation Commands
```typescript
"Navigate to https://api.askproai.de/admin/login"
"Go to the admin dashboard"
"Click on the 'Calls' menu item"
"Scroll down on the current page"
```

### Interaction Commands
```typescript
"Fill in the email field with admin@askproai.de"
"Click the 'Login' button"
"Type 'test query' into the search box"
"Select 'Active' from the status dropdown"
```

### Verification Commands
```typescript
"Take a screenshot of the current page"
"Get the text content of the page title"
"Check if element with class 'success-message' is visible"
"Wait for the table to load"
```

### Session Commands
```typescript
"Save current cookies"
"Load saved cookies"
"Clear browser cache"
"Get localStorage data"
```

---

## 🚀 INTEGRATION WITH CLAUDE CODE

### Automatischer Login-Flow

Wenn du Puppeteer mit Claude Code verwendest, kannst du fragen:

```bash
# Beispiel 1: Einfacher Login-Test
"Test the admin login at https://api.askproai.de/admin/login
with admin@askproai.de"

# Beispiel 2: Full E2E Test
"Login to admin panel and verify all main pages are accessible:
- /admin/calls
- /admin/customers
- /admin/billing-alerts
- /admin/system-administration"

# Beispiel 3: Visual Regression Test
"Login to admin and take screenshots of:
- Dashboard
- Calls page
- Customer list
Save screenshots in claudedocs/screenshots/"
```

---

## 📊 SESSION MANAGEMENT

### Cookie Storage Location

Puppeteer MCP speichert Cookies in:
```bash
~/.puppeteer/
~/.cache/puppeteer/
```

### Session Persistence Strategy

1. **First Login:**
   - Puppeteer navigiert zu Login-Seite
   - Füllt Credentials aus
   - Klickt Login-Button
   - Wartet auf Redirect
   - **Speichert Session-Cookies automatisch**

2. **Subsequent Requests:**
   - Lädt gespeicherte Cookies
   - Navigiert direkt zur gewünschten Seite
   - Prüft ob Session noch gültig
   - Bei Logout → automatischer Re-Login

3. **Session Expiry Handling:**
   - Erkennt 401/403 Responses
   - Detected Redirect zu /login
   - Führt automatischen Re-Login durch
   - Fortsetzung der ursprünglichen Aufgabe

---

## 🔐 SECURITY BEST PRACTICES

### 1. Credentials Management

```bash
# NICHT im Code speichern:
❌ password: "admin123"

# Stattdessen Environment Variables:
✅ password: process.env.ADMIN_PASSWORD

# Oder Claude Code Credentials Store:
✅ "Use stored admin credentials for askproai.de"
```

### 2. Session Security

```yaml
Best Practices:
  - Cookies nur für HTTPS
  - Session Timeout beachten
  - Regelmäßig Re-Authentifizierung
  - Keine Credentials in Screenshots
  - Sensitive Data ausblenden
```

### 3. Test Isolation

```yaml
Recommendations:
  - Separate Test-Accounts verwenden
  - Test-Daten nicht in Production
  - Nach Tests aufräumen
  - Browser Context nach Test löschen
```

---

## 💡 PRAKTISCHE BEISPIELE

### Beispiel 1: Login-Test mit Verification

```javascript
// Was Claude Code macht:
1. "Navigate to https://api.askproai.de/admin/login"
2. "Fill email field with admin@askproai.de"
3. "Fill password field with [PASSWORD]"
4. "Click login button"
5. "Wait for redirect to /admin"
6. "Verify we see 'Dashboard' heading"
7. "Take screenshot of dashboard"
8. "Save cookies for future use"
```

### Beispiel 2: Multi-Page Testing

```javascript
// Efficient testing flow:
1. "Login once" → Session saved
2. "Check /admin/calls" → Uses saved session
3. "Check /admin/customers" → Uses saved session
4. "Check /admin/billing-alerts" → Uses saved session
5. "Take screenshots of all pages"
```

### Beispiel 3: Automated Regression Test

```javascript
// Full regression with Puppeteer:
"Run a visual regression test:
1. Login to admin panel
2. Navigate through all main pages
3. Take screenshot of each page
4. Compare with baseline screenshots
5. Report any visual differences"
```

---

## 🛠️ TROUBLESHOOTING

### Problem: Session wird nicht gespeichert

```bash
Solution:
"Clear Puppeteer cache and login fresh:
1. Clear ~/.puppeteer/ cache
2. Login again to admin panel
3. Verify cookies are saved
4. Test session persistence"
```

### Problem: Login-Button nicht gefunden

```bash
Solution:
"Inspect login page and find correct selectors:
1. Navigate to login page
2. Get page HTML
3. Find button selector
4. Update login script"
```

### Problem: ARM64 Chromium Issues

```bash
Solution:
# Puppeteer ist bereits für ARM64 installiert
puppeteer-mcp-server@0.7.2 ✅
puppeteer@24.19.0 ✅

# Bei Problemen:
npm install -g puppeteer-mcp-server --force
```

---

## 📚 WEITERE RESSOURCEN

### Puppeteer MCP Documentation
- GitHub: https://github.com/executeautomation/mcp-puppeteer
- NPM: https://www.npmjs.com/package/puppeteer-mcp-server

### AskProAI Admin Routes
```
/admin/login          - Login page
/admin                - Dashboard
/admin/calls          - Call management
/admin/customers      - Customer list
/admin/billing-alerts - Billing alerts
/admin/system-administration - System settings
```

---

## ✅ VALIDATION CHECKLIST

Nach Puppeteer Setup:
- [ ] Puppeteer MCP Server installiert
- [ ] ARM64 Kompatibilität bestätigt
- [ ] Admin Login-Credentials konfiguriert
- [ ] Session Persistence getestet
- [ ] Screenshot-Funktionalität getestet
- [ ] Multi-Page Navigation getestet
- [ ] Cookie-Speicherung verifiziert

---

**Status:** ✅ Konfiguriert und dokumentiert
**Nächster Schritt:** Puppeteer Login-Test durchführen

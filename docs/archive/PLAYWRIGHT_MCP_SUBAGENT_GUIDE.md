# 🎭 Playwright MCP Server - Subagent Integration Guide

## ✅ Installation Status

**✅ ERFOLGREICH INSTALLIERT**
- Playwright MCP Server: `npx @playwright/mcp@latest` 
- Status: Connected ✓
- Konfiguration: Optimiert für AskProAI Frontend-Testing
- Output-Verzeichnis: `/var/www/api-gateway/storage/playwright-output/`

---

## 🎯 Für Subagent: **ui-auditor**

### 🔧 Wichtige MCP Tools für UI-Auditing

#### 1. **browser_navigate** - Zur Website navigieren
```javascript
// Navigation zur AskProAI Admin Login Page
browser_navigate("https://api.askproai.de/admin/login")
```

#### 2. **browser_snapshot** - Accessibility-Snapshot (BESSER als Screenshots!)
```javascript
// Erfasst strukturierte Accessibility-Daten - optimal für UI-Analyse
browser_snapshot()
```

#### 3. **browser_take_screenshot** - Screenshots für Dokumentation
```javascript
// Vollständiger Screenshot
browser_take_screenshot({
  "filename": "admin-dashboard-audit.png",
  "fullPage": true
})

// Element-Screenshot
browser_take_screenshot({
  "filename": "navigation-closeup.png",
  "element": "Navigation sidebar",
  "ref": "sidebar-element-ref"
})
```

#### 4. **browser_resize** - Responsive Testing
```javascript
// Desktop
browser_resize(1920, 1080)

// Tablet 
browser_resize(768, 1024)

// Mobile
browser_resize(375, 667)
```

#### 5. **browser_click** - UI-Interaktion
```javascript
// Login-Button klicken
browser_click({
  "element": "Login button",
  "ref": "login-btn-ref"
})
```

### 📋 UI-Audit Workflow für ui-auditor

1. **Navigation & Setup**
   ```
   → browser_navigate("https://api.askproai.de/admin/login")
   → browser_resize(1920, 1080)  // Desktop-Ansicht
   ```

2. **Initial Snapshot & Analysis**
   ```
   → browser_snapshot()  // Strukturelle Analyse
   → browser_take_screenshot({"filename": "login-page.png", "fullPage": true})
   ```

3. **Responsive Testing**
   ```
   → browser_resize(768, 1024)   // Tablet
   → browser_take_screenshot({"filename": "login-tablet.png"})
   → browser_resize(375, 667)    // Mobile
   → browser_take_screenshot({"filename": "login-mobile.png"})
   ```

4. **Navigation & Interaction Testing**
   ```
   → Login mit Test-Credentials
   → browser_navigate("https://api.askproai.de/admin")
   → browser_snapshot()  // Dashboard-Struktur analysieren
   ```

5. **Element-spezifische Tests**
   ```
   → browser_take_screenshot({"element": "Navigation sidebar", "ref": "nav-ref"})
   → browser_click({"element": "Dashboard menu item", "ref": "dashboard-ref"})
   ```

---

## 🎯 Für Subagent: **general-purpose**

### 🔧 Allgemeine Frontend-Testing Tools

#### 1. **browser_tab_management** - Multi-Tab Testing
```javascript
browser_tab_new("https://api.askproai.de/admin-v2/portal")
browser_tab_list()
browser_tab_select(1)
```

#### 2. **browser_type** - Formulare ausfüllen
```javascript
browser_type({
  "element": "Email input field",
  "ref": "email-input-ref",
  "text": "admin@askproai.de"
})
```

#### 3. **browser_wait_for** - Warten auf Inhalte
```javascript
browser_wait_for({
  "text": "Dashboard loaded",
  "time": 5
})
```

#### 4. **browser_console_messages** - JavaScript-Fehler prüfen
```javascript
browser_console_messages()
```

#### 5. **browser_network_requests** - Performance-Analyse
```javascript
browser_network_requests()
```

### 📋 General Testing Workflow

1. **Multi-Panel Testing**
   ```
   → browser_navigate("https://api.askproai.de/admin")
   → browser_tab_new("https://api.askproai.de/admin-v2/portal")
   → Vergleich beider Admin-Interfaces
   ```

2. **Performance-Monitoring**
   ```
   → browser_network_requests()  // Netzwerk-Performance
   → browser_console_messages()  // JavaScript-Errors
   ```

---

## 🎯 Für Subagent: **security-auditor**

### 🔧 Security-spezifische Tests

#### 1. **HTTPS & Security Headers prüfen**
```javascript
browser_network_requests()  // Security Headers analysieren
```

#### 2. **Authentication Flow testen**
```javascript
// Logout testen
browser_navigate("https://api.askproai.de/admin/logout")
// Redirect-Verhalten prüfen
```

#### 3. **CSRF & Session Testing**
```javascript
// Mehrere Tabs für Session-Tests
browser_tab_new("https://api.askproai.de/admin/login")
```

---

## ⚙️ Optimierte Konfiguration

### Verwendung der erweiterten Konfiguration:
```bash
npx @playwright/mcp@latest --config /var/www/api-gateway/playwright-mcp-config.json
```

### Konfiguration Details:
- **Browser:** Chromium (Chrome Channel)
- **Viewport:** 1920x1080 (Desktop-optimiert)
- **Headless:** Deaktiviert für UI-Debugging
- **Output-Dir:** `/var/www/api-gateway/storage/playwright-output/`
- **Capabilities:** Tabs, PDF, Vision aktiviert
- **Network:** Optimiert für AskProAI-Domains

---

## 🎯 Best Practices für Subagents

### ✅ DO - Empfohlene Practices

1. **Immer `browser_snapshot()` vor Screenshots**
   - Accessibility-Tree ist strukturierter als Pixel-Daten
   - Besser für KI-Analyse geeignet
   
2. **Responsive Testing durchführen**
   ```
   → Desktop (1920x1080)
   → Tablet (768x1024) 
   → Mobile (375x667)
   ```

3. **Element-References verwenden**
   - Nutze `ref` Parameter für präzise Element-Identifikation
   - Besser als Koordinaten-basierte Clicks

4. **Screenshots für Dokumentation**
   - Immer aussagekräftige Dateinamen verwenden
   - `fullPage: true` für komplette Seitenansichten

5. **Performance-Monitoring**
   - `browser_console_messages()` für JavaScript-Errors
   - `browser_network_requests()` für Netzwerk-Performance

### ❌ DON'T - Zu vermeidende Practices

1. **Keine koordinaten-basierten Clicks ohne Vision-Capability**
2. **Keine Screenshots ohne strukturelle Analyse (`browser_snapshot()`)**
3. **Keine Tests ohne Responsive-Checks**
4. **Keine Navigation ohne Error-Handling**

---

## 🔧 Erweiterte Funktionen

### PDF-Generation (für Reports)
```javascript
browser_pdf_save({
  "filename": "ui-audit-report.pdf"
})
```

### File-Upload Testing
```javascript
browser_file_upload({
  "paths": ["/path/to/test-file.png"]
})
```

### Dialog-Handling
```javascript
browser_handle_dialog({
  "accept": true,
  "promptText": "Test input"
})
```

---

## 📊 Output-Struktur

```
/var/www/api-gateway/storage/playwright-output/
├── screenshots/
│   ├── login-page.png
│   ├── dashboard-desktop.png
│   ├── dashboard-tablet.png
│   └── dashboard-mobile.png
├── traces/
│   └── session-trace.zip
├── reports/
│   └── ui-audit-report.pdf
└── logs/
    └── console-errors.json
```

---

## 🎯 Speziell für AskProAI Testing

### Wichtige URLs zum Testen:
- **Hauptadmin:** `https://api.askproai.de/admin`
- **AdminV2:** `https://api.askproai.de/admin-v2/portal`
- **Login:** `https://api.askproai.de/admin/login`
- **Business Portal:** `https://api.askproai.de/business/login`

### Test-Credentials:
```javascript
// Admin Login
browser_type({"element": "Email field", "ref": "email-ref", "text": "admin@askproai.de"})
browser_type({"element": "Password field", "ref": "pwd-ref", "text": "password"})
```

### Navigation-Testing (Issue #577/#578 gelöst):
```javascript
// Grid-Layout prüfen
browser_snapshot()
// Navigation-Sidebar Sichtbarkeit prüfen
browser_take_screenshot({"element": "Navigation sidebar", "ref": "nav-ref"})
```

---

## 🚀 Integration in Subagent-Workflows

### Für **ui-auditor** - Vollständiger UI-Audit:
1. Navigate to target page
2. Take accessibility snapshot
3. Test responsive design (3 breakpoints)
4. Test navigation interactions
5. Document findings with screenshots
6. Generate PDF report

### Für **general-purpose** - Allgemeine Tests:
1. Multi-panel testing (Admin vs AdminV2)
2. Performance monitoring
3. Error detection
4. Cross-browser compatibility

### Für **security-auditor** - Sicherheitstests:
1. Authentication flow testing
2. Session management verification
3. HTTPS/Security header validation
4. CSRF protection testing

---

## ✅ Verification Commands

```bash
# MCP Status prüfen
claude mcp list

# Playwright Version prüfen  
npx @playwright/mcp@latest --version

# Output-Verzeichnis prüfen
ls -la /var/www/api-gateway/storage/playwright-output/
```

---

**Status:** ✅ **VOLLSTÄNDIG INSTALLIERT UND KONFIGURIERT**
**Bereit für:** ui-auditor, general-purpose, security-auditor Subagents
**Letzte Aktualisierung:** 14. August 2025

---

## 💡 Pro-Tipps für Subagents

1. **Kombination mit Puppeteer MCP**: Nutze Playwright für strukturelle Tests, Puppeteer für spezielle Browser-Features
2. **Batch-Screenshots**: Mehrere Viewport-Größen in einem Workflow testen
3. **Traces aktivieren**: `--save-trace` für detaillierte Debugging-Informationen
4. **Isolated Sessions**: `--isolated` für saubere Test-Umgebungen
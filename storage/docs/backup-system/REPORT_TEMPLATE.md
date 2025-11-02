# Report Template - State of the Art

**Zweck:** Standard-Template für alle Reports im Dokumentations-Hub
**Version:** 1.0
**Datum:** 2025-11-02

---

## Verwendung

Dieses Template soll für **alle neuen Reports** verwendet werden, um:
- Konsistente Struktur zu gewährleisten
- Automatische Kategorisierung zu ermöglichen
- Kopierbare Markdown-Versionen bereitzustellen
- Multi-User-Zusammenarbeit zu unterstützen

---

## Dateinamen-Konvention

**Format:** `KATEGORIE_BESCHREIBUNG_YYYY-MM-DD_HHMM.{md,html}`

**Kategorien (für automatische Zuordnung):**
- `E2E_` → E2E Validation Reports ✅
- `DEPLOY_` → Deployment & Gates 🚀
- `INCIDENT_` / `RCA_` / `FIX_` → Incident Reports & Fixes 🔥
- `BACKUP_` / `PITR_` → Backup & PITR 💾
- `TEST_` / `VALIDATION_` → Testing & Validation 🧪
- `SECURITY_` / `AUTH_` → Security & Access 🔒
- `EXECUTIVE_` / `SUMMARY_` → Executive / Management 👔

**Beispiele:**
```
E2E_WORKFLOW_HARDENING_VALIDATION_2025-11-02_1330.md
DEPLOY_STAGING_RELEASE_2025-11-02_1200.md
INCIDENT_DATABASE_CONNECTION_TIMEOUT_2025-11-02_0945.md
```

---

## Markdown Template (.md)

```markdown
# [Report Titel]
**Datum:** YYYY-MM-DD HH:MM UTC
**Typ:** [E2E Validation / Deployment / Incident / etc.]
**Umgebung:** [Staging / Production]
**Status:** [✅ SUCCESS / ❌ FAILED / ⚠️ PARTIAL]

---

## Executive Summary

**Status:** [Eine Zeile Zusammenfassung]
**Root Cause:** [Hauptursache in 1-2 Sätzen]
**Impact:** [Auswirkung]
**Recommendation:** [Go/No-Go Entscheidung]

### Key Findings

- **[P0/P1/P2]:** [Kurze Beschreibung des Findings]
- **[P0/P1/P2]:** [Weitere Findings]

---

## Detailed Analysis

[Detaillierte Beschreibung des Problems/Tests/Deployments]

### Timeline

| Zeit (UTC) | Event | Status | Details |
|------------|-------|--------|---------|
| HH:MM | Event 1 | ✅ PASS | Description |
| HH:MM | Event 2 | ❌ FAIL | Error details |

---

## Technical Details

### Configuration
```yaml
environment: staging
version: 1.2.3
commit: abc123
```

### Logs
```
[Log excerpts]
```

---

## Recommendations

### Immediate Actions (P0)
1. [Action 1]
2. [Action 2]

### Follow-up (P1)
1. [Action 1]
2. [Action 2]

---

## Go/No-Go Decision

[⛔ NO-GO / ✅ GO]

**Rationale:**
1. [Reason 1]
2. [Reason 2]

**Next Steps:**
- [Step 1]
- [Step 2]

---

## Report Metadata

**Generated:** YYYY-MM-DD HH:MM:SS UTC
**Author:** [Name / System]
**Version:** 1.0
**Classification:** Internal / CI/CD Analysis
```

---

## HTML Template mit Markdown (State of the Art)

**Wichtig:** Alle HTML-Reports MÜSSEN eine kopierbare Markdown-Version enthalten!

### Struktur

```html
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>[Report Titel] - [Datum]</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@mdi/font@7.4.47/css/materialdesignicons.min.css">
    <style>
        /* Standard CSS (siehe E2E_WORKFLOW_HARDENING_VALIDATION_2025-11-02_1330.html) */
        :root {
            --primary: #2c3e50;
            --success: #27ae60;
            --danger: #e74c3c;
            --warning: #f39c12;
            /* ... weitere CSS Variablen */
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .container { padding: 1rem; }
            /* ... weitere mobile styles */
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header Section -->
        <div class="header">
            <h1><span class="mdi mdi-[icon]"></span> [Report Titel]</h1>
            <div class="metadata">
                <div class="metadata-item">
                    <span class="mdi mdi-calendar"></span>
                    <span>[Datum]</span>
                </div>
                <!-- Weitere Metadaten -->
            </div>
        </div>

        <!-- Executive Summary -->
        <div class="section">
            <h2 class="section-title"><span class="mdi mdi-chart-box"></span> Executive Summary</h2>
            <div class="alert alert-[danger/success/info]">
                <!-- Summary Content -->
            </div>
        </div>

        <!-- Weitere Sections -->

        <!-- WICHTIG: Kopierbare Markdown Section (immer am Ende!) -->
        <div class="section">
            <h2 class="section-title"><span class="mdi mdi-content-copy"></span> Kopierbare Markdown-Version</h2>
            <div class="card info">
                <p style="margin-bottom: 1rem;">Hier ist die vollständige Markdown-Version dieses Reports zum Kopieren:</p>
                <div style="position: relative;">
                    <button onclick="copyMarkdownToClipboard()" style="[button styles]">
                        <span class="mdi mdi-content-copy"></span>
                        <span id="copyButtonText">Copy to Clipboard</span>
                    </button>
                    <textarea id="markdownContent" readonly style="[textarea styles]">
# [Report Titel]
[... vollständiger Markdown Content hier ...]
</textarea>
                </div>
            </div>
        </div>
    </div>

    <script>
    function copyMarkdownToClipboard() {
        const textarea = document.getElementById('markdownContent');
        const button = document.getElementById('copyButtonText');

        textarea.select();
        textarea.setSelectionRange(0, textarea.value.length);

        try {
            const successful = document.execCommand('copy');
            if (successful) {
                button.textContent = 'Copied!';
                button.parentElement.style.background = '#27ae60';
                setTimeout(() => {
                    button.textContent = 'Copy to Clipboard';
                    button.parentElement.style.background = 'var(--info)';
                }, 2000);
            } else {
                throw new Error('Copy command failed');
            }
        } catch (err) {
            button.textContent = 'Failed to copy';
            button.parentElement.style.background = '#e74c3c';
            console.error('Failed to copy:', err);
            setTimeout(() => {
                button.textContent = 'Copy to Clipboard';
                button.parentElement.style.background = 'var(--info)';
            }, 3000);
        }

        window.getSelection().removeAllRanges();
    }

    // Modern Clipboard API (fallback)
    if (navigator.clipboard && window.isSecureContext) {
        const originalFunc = window.copyMarkdownToClipboard;
        window.copyMarkdownToClipboard = async function() {
            const textarea = document.getElementById('markdownContent');
            const button = document.getElementById('copyButtonText');

            try {
                await navigator.clipboard.writeText(textarea.value);
                button.textContent = 'Copied!';
                button.parentElement.style.background = '#27ae60';
                setTimeout(() => {
                    button.textContent = 'Copy to Clipboard';
                    button.parentElement.style.background = 'var(--info)';
                }, 2000);
            } catch (err) {
                originalFunc();
            }
        };
    }
    </script>
</body>
</html>
```

---

## Automatische Kategorisierung

Die Kategorisierung erfolgt automatisch basierend auf dem Dateinamen:

**Backend:** `routes/web.php` (Zeilen 228-246)

```php
if (str_contains($filename, 'E2E') || str_contains($filename, 'WORKFLOW_HARDENING') || str_contains($filename, 'GATE_VALIDATION')) {
    $category = 'E2E Validation Reports';
} elseif (str_contains($filename, 'DEPLOY') || str_contains($filename, 'deployment-release') || str_contains($filename, 'STAGING')) {
    $category = 'Deployment & Gates';
}
// ... weitere Kategorien
```

**Frontend:** `index.html` zeigt automatisch:
1. **"Neueste Reports"** - Die 8 aktuellsten Dateien (nach mtime sortiert)
2. **E2E Reports hervorgehoben** - Grüner Rahmen + "✨ E2E Report" Badge
3. **Kategorien** - Automatisch gruppiert mit Icons

---

## Best Practices

### DO's ✅
- **Immer** Markdown + HTML Version erstellen
- **Immer** Dateiname-Konvention einhalten
- **Immer** Executive Summary mit P0/P1/P2 Findings
- **Immer** Go/No-Go Entscheidung dokumentieren
- **Immer** Timestamps in UTC angeben
- **Immer** Copy-to-Clipboard Funktion einbauen
- **Immer** Responsive Design (Mobile-First)

### DON'Ts ❌
- ❌ Keine lokalen Pfade oder Secrets im Report
- ❌ Keine Screenshots ohne Alt-Text (Accessibility)
- ❌ Keine unstrukturierten Logs (maximal Auszüge)
- ❌ Keine großen Bilder (> 500 KB)
- ❌ Keine unformatierten Code-Blöcke

---

## Icons (Material Design Icons)

**Verwendung:** `<span class="mdi mdi-[icon-name]"></span>`

**Häufig verwendete Icons:**
- `mdi-alert-circle` - Fehler/Warnung
- `mdi-check-circle` - Erfolg
- `mdi-clock-outline` - Zeit/Latest
- `mdi-chart-box` - Executive Summary
- `mdi-bug` - Bugs
- `mdi-shield-alert` - Sicherheit
- `mdi-rocket-launch` - Deployment
- `mdi-database` - Datenbank
- `mdi-content-copy` - Kopieren
- `mdi-calendar` - Datum
- `mdi-git` - Git/Commit
- `mdi-server` - Server

**Vollständige Liste:** https://pictogrammers.com/library/mdi/

---

## Checkliste vor Commit

- [ ] Dateiname folgt Konvention: `KATEGORIE_BESCHREIBUNG_YYYY-MM-DD_HHMM.{md,html}`
- [ ] HTML Version enthält kopierbare Markdown im `<textarea>`
- [ ] Markdown Version separat gespeichert (.md)
- [ ] Executive Summary mit P0/P1/P2 Findings
- [ ] Go/No-Go Entscheidung dokumentiert
- [ ] Alle Timestamps in UTC
- [ ] Responsive Design getestet (Mobile + Desktop)
- [ ] Copy-Button funktioniert
- [ ] Keine Secrets oder lokale Pfade
- [ ] Git Commit Message beschreibt den Report:
  ```
  docs(staging): [Kategorie] - [Kurzbeschreibung]

  [Längere Beschreibung]

  Reports:
  - [Dateiname.md]
  - [Dateiname.html]
  ```

---

## Beispiel-Commit

```bash
git add storage/docs/backup-system/E2E_*.md storage/docs/backup-system/E2E_*.html
git commit -m "docs(staging): E2E validation after workflow hardening - deployment failed

Run: https://github.com/fabianSp77/askproai-api/actions/runs/19011797015
Status: FAILED at artifact download (Gate 5/11)

Critical Findings:
- P0: BUILD_RUN_ID null string bug
- P1: Health endpoints return 401/403

Recommendation: NO-GO until bugs fixed

Reports:
- E2E_WORKFLOW_HARDENING_VALIDATION_2025-11-02_1330.md
- E2E_WORKFLOW_HARDENING_VALIDATION_2025-11-02_1330.html"
git push origin develop
```

---

## Support & Feedback

Bei Fragen oder Verbesserungsvorschlägen:
1. Check `storage/docs/backup-system/index.html` (Dokumentations-Hub)
2. Review bestehende Reports als Beispiele
3. Kontaktiere das DevOps Team

---

**Version:** 1.0
**Last Updated:** 2025-11-02
**Maintainer:** DevOps Team

# 🔒 Branch Protection - UI Aktivierungs-Anleitung

**Status**: ⏳ Wartet auf manuelle Aktivierung
**Zeit**: 5 Minuten
**Ziel**: `main` Branch schützen mit Required Checks

---

## ✅ Schritt-für-Schritt (5 Steps)

### 1. Öffne Branch Protection Settings

**URL**: https://github.com/fabianSp77/askproai-api/settings/branches

**Screenshot-Anweisung**: Mache Screenshot dieser Seite → speichere als `docs/evidence/branch-protection-step1.png`

---

### 2. Klicke "Add branch protection rule"

**Button-Position**: Oben rechts, grüner Button

**Aktion**: Klicken

---

### 3. Konfiguriere Branch Pattern & Reviews

**Branch name pattern**: `main` (exakt eingeben)

**Dann aktiviere folgende Checkboxen**:

```
☑ Require a pull request before merging
  ☑ Require approvals: 1
  ☑ Dismiss stale pull request approvals when new commits are pushed
  ☑ Require review from Code Owners (OPTIONAL)
  ☑ Require conversation resolution before merging
```

**Screenshot-Anweisung**: Screenshot dieser Sektion → `docs/evidence/branch-protection-step3-reviews.png`

---

### 4. Konfiguriere Required Status Checks (WICHTIGSTER TEIL!)

**Aktiviere**:
```
☑ Require status checks to pass before merging
  ☑ Require branches to be up to date before merging
```

**Dann füge folgende Required checks hinzu** (in das Suchfeld tippen):

```
Build Artifacts / build-frontend
Build Artifacts / build-backend
Build Artifacts / static-analysis
Build Artifacts / run-tests
Visual Tests (Staging) / visual-tests-firefox
Verify Staging Health / check-staging
```

**WICHTIG**: Jeder Check muss einzeln hinzugefügt werden. Nach Eingabe "Enter" drücken.

**Screenshot-Anweisung**: Screenshot der hinzugefügten Checks → `docs/evidence/branch-protection-step4-checks.png`

---

### 5. Aktiviere Enforcement & Speichern

**Aktiviere**:
```
☑ Do not allow bypassing the above settings
☑ Restrict who can push to matching branches
  → Admins only (oder spezifische Teams)
```

**Deaktiviere** (falls angezeigt):
```
☐ Allow force pushes
☐ Allow deletions
```

**Dann klicke**: "Create" oder "Save changes" (grüner Button unten)

**Screenshot-Anweisung**:
1. Screenshot der Enforcement-Sektion → `docs/evidence/branch-protection-step5-enforcement.png`
2. Screenshot nach dem Speichern (Bestätigung) → `docs/evidence/branch-protection-complete.png`

---

## ✅ Verifikation

Nach Aktivierung führe folgenden Befehl aus:

```bash
gh api repos/fabianSp77/askproai-api/branches/main/protection | jq '.required_status_checks.contexts'
```

**Erwartete Ausgabe**:
```json
[
  "Build Artifacts / build-frontend",
  "Build Artifacts / build-backend",
  "Build Artifacts / static-analysis",
  "Build Artifacts / run-tests",
  "Visual Tests (Staging) / visual-tests-firefox",
  "Verify Staging Health / check-staging"
]
```

---

## 📸 Checkliste für Screenshots

- [ ] `docs/evidence/branch-protection-step1.png` - Initiale Settings-Seite
- [ ] `docs/evidence/branch-protection-step3-reviews.png` - Review-Konfiguration
- [ ] `docs/evidence/branch-protection-step4-checks.png` - Required Status Checks
- [ ] `docs/evidence/branch-protection-step5-enforcement.png` - Enforcement-Settings
- [ ] `docs/evidence/branch-protection-complete.png` - Finale Bestätigung

---

**Nach Aktivierung**: Sag mir Bescheid, dann verifiziere ich via API und fahre mit den restlichen Tasks fort!

# Active Issues & Feature Tracking - Friseur 1 V50

**Last Updated**: 2025-11-06
**Agent Version**: V50 (agent_45daa54928c5768b52ba3db736)
**Status**: LIVE on Production

---

## 🚨 Critical Issues

| ID | Issue | Status | Reported | Priority | Assigned |
|----|-------|--------|----------|----------|----------|
| - | No critical issues | ✅ Resolved | - | - | - |

---

## ⚠️ High Priority Issues

| ID | Issue | Status | Reported | Priority | Notes |
|----|-------|--------|----------|----------|-------|
| TBD-001 | Booking aus Intent-Modi nicht möglich | 🔍 Investigating | 2025-11-06 | HIGH | User kann nicht aus "Termine anzeigen/stornieren/verschieben/Services auflisten" direkt neuen Termin buchen |
| TBD-002 | Knowledge Base Feature fehlt | 🔍 Investigating | 2025-11-06 | HIGH | Keine Möglichkeit für Agent, Filial-/Unternehmensinformationen abzurufen |

---

## 🔄 Medium Priority Issues

| ID | Issue | Status | Reported | Priority | Notes |
|----|-------|--------|----------|----------|-------|
| - | No medium priority issues | - | - | - | - |

---

## ✅ Recently Resolved Issues

| ID | Issue | Resolved Date | Solution |
|----|-------|---------------|----------|
| V50-001 | Tool-Call Infinite Loops | 2025-11-05 | Critical enforcement rules added to global prompt |
| V50-002 | Year Bug (2024 instead of 2025) | 2025-11-05 | Dynamic date injection in prompt |
| V50-003 | Service Disambiguation with Prices | 2025-11-05 | Removed prices from disambiguation flow |
| V50-004 | Repetitive Confirmation Loops | 2025-11-05 | Anti-repetition rules in prompt |

---

## 📋 Feature Requests

| ID | Feature | Status | Requested | Priority | Implementation Effort |
|----|---------|--------|-----------|----------|----------------------|
| FR-001 | Intent-Switch für Booking | 📝 Planned | 2025-11-06 | HIGH | Medium (Prompt + Flow Update) |
| FR-002 | Knowledge Base Integration | 📝 Planned | 2025-11-06 | HIGH | Large (New Function + Database) |

---

## 🔍 Investigation Details

### TBD-001: Booking aus Intent-Modi nicht möglich

**Problem**:
- User befindet sich in Intent "Termine anzeigen", "Termin stornieren", "Termin verschieben" oder "Services auflisten"
- User möchte direkt neuen Termin buchen
- System erlaubt keinen Wechsel zu Booking-Flow

**Expected Behavior**:
- User kann JEDERZEIT aus jedem Intent heraus neuen Termin buchen
- Nahtloser Übergang ohne Dialog-Neustart

**Current Status**: 🔍 Investigating
- Prüfung ob Feature bereits implementiert aber nicht dokumentiert
- Wenn nicht: Implementation Planning erforderlich

**Technical Analysis**:
```
Location: GLOBAL_PROMPT_V50_CRITICAL_ENFORCEMENT_2025.md
Function: Conversational Flow State Management
Impact: UX - User Experience eingeschränkt
```

---

### TBD-002: Knowledge Base Feature fehlt

**Problem**:
- Agent hat keine Möglichkeit, hinterlegte Informationen über Filiale/Unternehmen abzurufen
- Bei Kundenfragen zu Öffnungszeiten, Leistungen, Unternehmenswerten etc. keine strukturierte Antwort möglich

**Expected Behavior**:
- Agent kann auf Knowledge Base zugreifen
- Informationen über:
  - Filiale (Adresse, Öffnungszeiten, Besonderheiten)
  - Unternehmen (Geschichte, Werte, Philosophie)
  - Services (Detaillierte Beschreibungen, Empfehlungen)
  - FAQ (Häufige Kundenfragen)

**Current Status**: 🔍 Investigating
- Prüfung ob rudimentäres System existiert
- Wenn nicht: Design & Implementation erforderlich

**Technical Requirements**:
```
New Function: getKnowledgeBaseInfo()
Database: knowledge_base table (company_id, category, key, value, metadata)
Integration: RetellFunctionCallHandler.php
Cache: Redis (1hr TTL)
Admin UI: Filament Resource für Knowledge Base Management
```

---

## 📊 Issue Statistics

**Total Active Issues**: 2
- 🚨 Critical: 0
- ⚠️ High: 2
- 🔄 Medium: 0
- ✅ Resolved (Last 7 Days): 4

**Average Resolution Time**: 1-2 days
**Feature Request Backlog**: 2

---

## 🔄 Update Process

**How to Report New Issue**:
1. Describe problem clearly with examples
2. Provide reproduction steps if applicable
3. Add priority assessment
4. Claude updates this document automatically

**Status Definitions**:
- 🔍 **Investigating**: Issue confirmed, root cause analysis in progress
- 📝 **Planned**: Solution designed, implementation scheduled
- 🔄 **In Progress**: Currently being worked on
- ✅ **Resolved**: Fixed and deployed
- ❌ **Wont Fix**: Issue declined with reasoning

---

## 📞 Escalation Path

**High Priority Issues**: Immediate investigation
**Critical Issues**: Stop all work, fix immediately
**Feature Requests**: Prioritize by business impact

---

**Document Location**: `/var/www/api-gateway/docs/e2e/ACTIVE_ISSUES_TRACKING.md`
**HTML Version**: Will be generated after investigation complete
**Next Review**: After each test call or reported issue

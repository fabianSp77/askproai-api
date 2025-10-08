# Daily Update Template

## Format für 17:00 Updates

```markdown
=== DAILY UPDATE Tag X ===
Datum: YYYY-MM-DD 17:00

## ✅ Fertig (100%)
[Abgeschlossene Deliverables mit Beweisen]
- [Component Name]: [Details + Validierung]

## 🚧 In Progress (XX%)
[Aktuelle Arbeit + Fortschritt]
- [Component Name]: [Status + % complete]

## ⚠️ Blocker
[Probleme] OR "Keine"
- [Issue Description + Impact]

## 🔍 Review Status
[Review-Ergebnisse wenn durchgeführt]
- Review 15:00: [✅ APPROVED / ⚠️ NEEDS CHANGES / ⏳ PENDING]
- Findings: [Count] issues found
- Status: [All resolved / X pending]

## 🛡️ MySQL-Kompatibilität
[MySQL-specific validation results]
- New Queries/Migrations: [Count]
- MySQL-Syntax Validated: [✅/❌]
- SQLite vs MySQL Tested: [✅/❌/N/A]
- Forbidden Patterns Found: [None/List]

## ⏱️ Time Status
[On Track / Behind / Ahead]
- Planned vs Actual: [X hours planned, Y hours used]
- Buffer Used: [X hours from Day 16-17 buffer]

## 📅 Morgen (Tag X+1)
[Next day plan mit konkreten Tasks]
- Morning (08:00-12:00): [Tasks]
- Afternoon (13:00-17:00): [Tasks]
- Review scheduled: [Time if applicable]
- Checkpoint: [Yes/No + Criteria]

## 📝 Notes
[Wichtige Erkenntnisse, Decisions, Risks]
```

---

## Tag 3 Update (Heute 17:00)

```markdown
=== DAILY UPDATE Tag 3 ===
Datum: 2025-10-02 17:00

## ✅ Fertig
- PolicyConfigurationService: [✅/⏳/❌]
  - Methods: resolvePolicy(), resolveBatch(), warmCache(), clearCache()
  - Cache integration: 5min TTL, tested
  - Unit tests: [X/30+ passing]

- Test Suite: [✅/⏳/❌]
  - Integration tests: [X tests]
  - Edge cases: null configs, circular refs, cache invalidation
  - Performance tests: [avg Xms, p95 Yms]

## 🚧 In Progress
[If not finished by 17:00]
- [Component]: [Status + blocker]

## ⚠️ Blocker
Keine
[OR list issues]

## 🔍 Review Status
- Review 15:00 (PolicyConfigurationService): [✅/⚠️/❌]
  - Findings: [X] issues
  - Status: [All resolved/X pending]

- Review 16:30 (Test Suite): [✅/⚠️/❌]
  - Coverage: [X%]
  - Status: [APPROVED/CHANGES NEEDED]

## 🛡️ MySQL-Kompatibilität
- New Queries: [X in PolicyConfigurationService]
- MySQL-Syntax Validated: ✅
  - Checked against MYSQL_SYNTAX_GUIDELINES.md
  - No raw SQL with DB-specific functions
  - Laravel Query Builder only
- SQLite vs MySQL Tested: ✅/N/A
  - [If N/A: No new migrations today]
- Forbidden Patterns Found: None
  - No DB::statement("COMMENT ON...")
  - No PRAGMA statements
  - No SQLite-specific functions

## ⏱️ Time Status
On Track / [Behind by X hours] / [Ahead by X hours]
- Planned: 8 hours (PolicyConfigurationService + Tests)
- Actual: [X hours]
- Buffer Used: 0 hours

## 📅 Morgen (Tag 4)
**Goal**: Start AppointmentPolicyEngine - canCancel() + canReschedule()

Morning (08:00-12:00):
- Read existing appointment business logic
- Design PolicyEngine architecture
- Implement canCancel() with edge cases
- Unit tests for canCancel()

Afternoon (13:00-17:00):
- Implement canReschedule()
- Implement quota checking with materialized stats
- Integration tests
- 🔍 CRITICAL REVIEW at 16:00-17:00

Checkpoint: None (Day 5 checkpoint)

## 📝 Notes
- [Any important decisions made]
- [Risks identified]
- [Technical debt introduced]
```

---

## Template für CRITICAL Tags (4-5, 6-7, 8-9)

```markdown
=== DAILY UPDATE Tag X (CRITICAL COMPONENT) ===

## 🚨 Extra Review Status
[For Days 4-5, 6-7, 8-9 per PROJECT_CONSTRAINTS.md]

### Component: [PolicyEngine / Event System / Retell Integration]
**Review Type**: EXTRA DETAILED + [Security if applicable]

### Review Checklist Results
[From PROJECT_CONSTRAINTS.md Section 2 checklists]
- [ ] Logic Validation: [✅/❌ + details]
- [ ] Security: [✅/❌ + details]
- [ ] Testing: [✅/❌ + details]
- [ ] Performance: [✅/❌ + details]

### Critical Findings
[Any issues found during extra review]
1. [Finding 1 + Severity + Status]
2. [Finding 2 + Severity + Status]

### Approval Decision
[✅ APPROVED / ⚠️ CONDITIONAL / ❌ REJECTED]

### If REJECTED
**Rollback Initiated**: [Yes/No]
**Rollback To**: Tag X
**Recovery Plan**: [Summary]
**ETA Recovery**: [Estimate]

[Continue with standard template sections...]
```

---

## Checkpoint Day Updates (5, 10, 15)

```markdown
=== DAILY UPDATE Tag X (CHECKPOINT DAY) ===

## 🚨 CHECKPOINT STATUS
[MANDATORY section for checkpoint days]

### Checkpoint: [Name from PROJECT_CONSTRAINTS.md]
**Time**: [XX:00-XX:00]
**Type**: [Migrations+Models / PolicyEngine / Integration / Production-Ready]

### Checkpoint Criteria
[From original project plan]
- [ ] Criterion 1: [✅/❌ + evidence]
- [ ] Criterion 2: [✅/❌ + evidence]
- [ ] Criterion 3: [✅/❌ + evidence]

### Overall Result
[✅ PASSED / ❌ FAILED]

### If PASSED
**Next Phase**: Tag X+1 approved
**Risk Level**: [Low/Medium/High]
**Confidence**: [X%]

### If FAILED
**Failure Reason**: [Detailed technical reason]
**Impact Assessment**: [What's blocked]
**Response**: [Per PROJECT_CONSTRAINTS.md Section 3]
  - [ ] Analysis completed
  - [ ] Rollback decision: [Fix <1h / Rollback to Tag Y]
  - [ ] Rollback executed: [Yes/No]
  - [ ] Recovery plan: [Summary]

[Continue with standard template sections...]
```

---

**Version**: 1.0
**Last Updated**: 2025-10-02
**Next Use**: Heute 17:00 (Tag 3)

# Review Schedule - Tag 3-15

## 🎯 Purpose
Define exact review timing and responsibilities for critical code components.

---

## 📅 Tag 3 Review Schedule (Heute)

### Morning Implementation (08:00-14:30)
**Component**: PolicyConfigurationService
**Developer**: Primary Agent
**Activities**:
- Service layer implementation
- Cache integration
- Batch loading optimization
- Unit tests

### 🔍 Review Slot 1: 15:00-15:30 (30min)
**Reviewer**: Code-Reviewer Agent (Specialized)
**Component**: PolicyConfigurationService
**Review Type**: Code Quality + Logic Validation

**Review Checklist**:
```markdown
### Code Quality
- [ ] PHPDoc blocks complete
- [ ] Type hints on all methods
- [ ] Error handling present
- [ ] No hardcoded values
- [ ] Follows PSR-12 standards

### Logic Validation
- [ ] Hierarchy traversal correct (Staff → Service → Branch → Company)
- [ ] Cache key pattern consistent
- [ ] Cache TTL appropriate (5min)
- [ ] Null handling for missing configs
- [ ] Edge cases covered (no config at any level)

### Performance
- [ ] O(1) cache lookups
- [ ] Batch loading optimized
- [ ] No N+1 queries
- [ ] Cache warming strategy defined

### Security
- [ ] No cache key collisions possible
- [ ] Proper data sanitization
- [ ] No injection vulnerabilities

### Testing
- [ ] Unit tests cover all methods
- [ ] Edge cases tested
- [ ] Cache behavior validated
- [ ] Performance benchmarks present
```

**Review Output**:
```markdown
## Code Review Report - PolicyConfigurationService
**Date**: 2025-10-02 15:00
**Reviewer**: Code-Reviewer Agent
**Status**: ✅ APPROVED / ⚠️ NEEDS CHANGES / ❌ REJECTED

### Findings
1. [Issue or approval]
2. [Issue or approval]

### Action Items
- [ ] Fix X in file Y
- [ ] Add test for Z

### Approval
- [ ] Code quality ✅
- [ ] Logic correct ✅
- [ ] Tests passing ✅
- [ ] MySQL-compatible ✅
```

---

### Afternoon Implementation (15:30-16:30)
**Component**: Test Suite for 4-level Hierarchy
**Developer**: Primary Agent
**Activities**:
- Integration tests
- Edge case tests
- Performance tests

### 🔍 Review Slot 2: 16:30-17:00 (30min)
**Reviewer**: Code-Reviewer Agent (Specialized)
**Component**: Test Suite
**Review Type**: Test Coverage + Quality

**Review Checklist**:
```markdown
### Test Coverage
- [ ] All public methods tested
- [ ] Edge cases covered (null, empty, circular)
- [ ] Cache behavior validated
- [ ] Performance benchmarks present
- [ ] Integration tests pass

### Test Quality
- [ ] Test names descriptive
- [ ] Arrange-Act-Assert pattern
- [ ] No test interdependencies
- [ ] Proper assertions (not just "no error")
- [ ] Cleanup after tests (database, cache)

### Documentation
- [ ] Test purpose documented
- [ ] Expected behavior clear
- [ ] Edge cases explained
```

**Review Output**: Same format as Review Slot 1

---

## 📅 Tag 4-5 Review Schedule (PolicyEngine - CRITICAL)

### Day 4: Implementation
**Morning (08:00-12:00)**: canCancel() + canReschedule()
**Afternoon (13:00-17:00)**: calculateFee() + Integration

### 🔍 CRITICAL Review: Day 4 16:00-17:00 (1 hour)
**Reviewer**: Code-Reviewer Agent + Security Validation
**Component**: AppointmentPolicyEngine
**Review Type**: EXTRA DETAILED (per PROJECT_CONSTRAINTS.md)

**Review Checklist** (from PROJECT_CONSTRAINTS.md Section 2):
```markdown
### Logic Validation
- [ ] canCancel() - Frist-Checks präzise?
- [ ] canReschedule() - Quota-Checks korrekt?
- [ ] calculateFee() - Staffelung exakt?
- [ ] Edge Case: Appointment um 23:59, Check um 00:01
- [ ] Edge Case: DST-Umstellung während Frist
- [ ] Performance: O(1) durch materialized stats

### Security
- [ ] No injection vulnerabilities
- [ ] No race conditions in quota checks
- [ ] Proper locking if needed

### Testing
- [ ] 50+ test cases minimum
- [ ] All edge cases covered
- [ ] Performance benchmarks < 50ms p95
```

### Day 5: Finalization + Checkpoint
**Morning (08:00-12:00)**: Fix review findings + Additional tests
**12:00-13:00**: 🚨 **MANDATORY CHECKPOINT**

**Checkpoint Validation**:
```markdown
## Day 5 Checkpoint - PolicyEngine Complete
- [ ] All review findings resolved
- [ ] All tests green (100% pass rate)
- [ ] Performance benchmarks met
- [ ] MySQL compatibility verified
- [ ] Documentation complete

**Go/No-Go Decision**:
✅ GO → Continue to Day 6
❌ NO-GO → STOP, Execute Rollback (see PROJECT_CONSTRAINTS.md Section 3)
```

---

## 📅 Tag 6-7 Review Schedule (Event System - CRITICAL)

### 🔍 CRITICAL Review: Day 6 16:00-17:00
**Reviewer**: Code-Reviewer Agent
**Component**: Event System
**Review Type**: EXTRA DETAILED

**Review Checklist** (from PROJECT_CONSTRAINTS.md Section 2):
```markdown
### Event System
- [ ] AppointmentModified Event → Listener triggered
- [ ] Listener fails → Doesn't break main flow
- [ ] Multiple Listeners → Alle executed
- [ ] Queued events → Processed in order
- [ ] Event data → Immutable (keine side effects)

### Memory & Performance
- [ ] No memory leaks
- [ ] Event firing < 10ms overhead
- [ ] Queue processing validated
```

---

## 📅 Tag 8-9 Review Schedule (Retell Integration - CRITICAL)

### 🔍 CRITICAL Review: Day 8 16:00-17:00
**Reviewer**: Code-Reviewer Agent + Security Focus
**Component**: Retell Handlers
**Review Type**: EXTRA DETAILED + SECURITY

**Review Checklist** (from PROJECT_CONSTRAINTS.md Section 2):
```markdown
### Security (MANDATORY)
- [ ] Webhook signature verified BEFORE processing
- [ ] Duplicate call_id → idempotent (no double-booking)
- [ ] Cal.com timeout → graceful degradation
- [ ] Invalid payload → 400 response
- [ ] Valid payload → 200 response
- [ ] Partial failure → Logged but not 5xx

### Integration
- [ ] All 4 handlers implemented
- [ ] Router logic correct
- [ ] Error handling comprehensive
```

---

## 🔄 Review Process Flow

```
┌─────────────────────────────────────────────┐
│ 1. Implementation Complete                  │
│    Developer signals "Ready for Review"     │
└────────────────┬────────────────────────────┘
                 │
                 ▼
┌─────────────────────────────────────────────┐
│ 2. Launch Code-Reviewer Agent               │
│    Task tool with review checklist          │
└────────────────┬────────────────────────────┘
                 │
                 ▼
┌─────────────────────────────────────────────┐
│ 3. Agent Performs Review                    │
│    - Reads code                             │
│    - Runs tests                             │
│    - Validates checklist                    │
│    - Generates report                       │
└────────────────┬────────────────────────────┘
                 │
                 ▼
┌─────────────────────────────────────────────┐
│ 4. Review Decision                          │
├─────────────────────────────────────────────┤
│ ✅ APPROVED → Continue                      │
│ ⚠️ NEEDS CHANGES → Fix & Re-review         │
│ ❌ REJECTED → Rollback & Redesign           │
└─────────────────────────────────────────────┘
```

---

## 👤 Reviewer Responsibilities

### Code-Reviewer Agent (Primary)
**Expertise**: Code quality, logic validation, testing
**Review Focus**:
- Syntax and style
- Logic correctness
- Test coverage
- Documentation

**Launch Command**:
```bash
Task(
    subagent_type="code-reviewer",
    description="Review PolicyConfigurationService",
    prompt="Review app/Services/Policies/PolicyConfigurationService.php

    Checklist:
    - Code quality (PSR-12, type hints, PHPDoc)
    - Logic correctness (hierarchy traversal)
    - Cache implementation
    - Test coverage
    - MySQL compatibility

    Provide detailed report with approval/rejection decision."
)
```

### Security-Engineer Agent (For Critical Components)
**Expertise**: Security vulnerabilities, injection attacks, race conditions
**Review Focus**:
- Input validation
- SQL injection risks
- Authentication/Authorization
- Race conditions

**Launch Command** (For Retell Integration):
```bash
Task(
    subagent_type="security-engineer",
    description="Security review of Retell handlers",
    prompt="Review app/Services/Retell/Handlers/ for security vulnerabilities.

    Focus:
    - Webhook signature validation
    - Input sanitization
    - Idempotency
    - Error handling (no info leakage)

    Provide security assessment report."
)
```

---

## 📊 Review Metrics

### Target Metrics (All Reviews)
- **Review Duration**: 30min (standard), 60min (critical)
- **Response Time**: < 2 hours after "Ready for Review"
- **Approval Rate Target**: > 80% (first review)
- **Critical Findings**: 0 (security/logic errors)

### Tracking
```markdown
## Review Log
| Date | Component | Reviewer | Duration | Status | Findings |
|------|-----------|----------|----------|--------|----------|
| 2025-10-02 15:00 | PolicyConfigurationService | Code-Reviewer | 30min | ✅ | 2 minor |
| 2025-10-02 16:30 | Test Suite | Code-Reviewer | 20min | ✅ | 0 |
```

---

## 🚨 Escalation Path

### If Review Finds Critical Issues
1. **STOP implementation immediately**
2. **Document issue in detail**
3. **Assess impact** (can fix in <1h vs needs redesign)
4. **Execute appropriate response**:
   - Quick fix → Fix & re-review
   - Major issue → Rollback & redesign

### If Review Blocked (Agent Unavailable)
- **Self-review** using checklist (reduced confidence)
- **Document** as "Self-reviewed, pending peer review"
- **Schedule** proper review next day
- **DO NOT proceed to next component** until reviewed

---

**Version**: 1.0
**Last Updated**: 2025-10-02
**Next Review**: Tag 3 15:00 (PolicyConfigurationService)

# Root Cause Analysis: Backup System Failure (2025-11-07)

**Incident ID**: INC-20251107093001-UQ1lO5
**Severity**: 🚨 HIGH
**Status**: Root Cause Identified
**Date**: 2025-11-07 09:30 CET
**Analysis By**: Claude Code

---

## Executive Summary

**Problem**: Backup system has not created any backups since 2025-11-06 03:00 (30+ hours overdue)

**Root Cause**: Synology NAS (fs-cloud1977.synology.me) is unreachable, causing backup script to abort completely

**Impact**:
- ❌ 3 scheduled backups missed (2025-11-06 11:00, 19:00, 2025-11-07 03:00)
- ⚠️ No local backups created during this period
- ✅ System is otherwise healthy (DB, disk space, permissions all OK)

---

## Timeline of Events

### ✅ Normal Operation
```
2025-11-05 03:00  → Backup SUCCESS (1175 MB)
2025-11-05 11:00  → Backup SUCCESS (1175 MB)
2025-11-05 19:00  → Backup SUCCESS (1175 MB)
2025-11-06 03:00  → Backup SUCCESS (1197 MB) [LAST SUCCESSFUL]
```

### ❌ Failure Period
```
2025-11-06 11:00  → PRE-FLIGHT CHECK FAILED: "Cannot connect to Synology NAS"
2025-11-06 19:00  → PRE-FLIGHT CHECK FAILED: "Cannot connect to Synology NAS"
2025-11-07 03:00  → PRE-FLIGHT CHECK FAILED: "Cannot connect to Synology NAS"
```

### 📧 Alerts Generated
```
2025-11-07 09:00  → Health Check WARNING: Last backup 30h old
2025-11-07 09:30  → HIGH SEVERITY ALERT: Backup overdue (30h vs 8h expected)
```

---

## Root Cause Analysis

### Primary Cause: Network Connectivity Failure

**Synology NAS Target:**
- Hostname: `fs-cloud1977.synology.me`
- IP Address: `212.86.60.237` (DNS resolved)
- SSH Port: `50222`
- Status: **UNREACHABLE**

**Evidence:**
```bash
# SSH Connection Test
$ ssh -i /root/.ssh/synology_backup_key -p 50222 AskProAI@fs-cloud1977.synology.me
Result: Connection timed out

# Network Ping Test
$ ping -c 3 fs-cloud1977.synology.me
Result: 100% packet loss (0/3 packets received)
```

**Possible Root Causes for NAS Unreachability:**
1. 🔴 Synology NAS device is powered off or crashed
2. 🔴 Internet connectivity failure at NAS location
3. 🔴 Firewall rule change blocking port 50222
4. 🟡 SSH key revoked or changed on Synology
5. 🟡 DynDNS service failure (hostname → IP mapping issue)
6. 🟡 ISP or routing issue between server and NAS

---

## Secondary Issue: Overly Strict Backup Script

**Script Behavior** (`/var/www/api-gateway/scripts/backup-run.sh:100-112`):
```bash
# Pre-flight checks MUST pass or backup aborts
preflight_checks() {
    # ... disk space check: ✅
    # ... MariaDB check: ✅
    # ... Synology connectivity check: ❌ FAIL → exit 1
}
```

**Problem**: The backup script operates in "all-or-nothing" mode:
- If Synology NAS is unreachable → **entire backup aborted**
- No local backup created
- No degraded/fallback mode

**Impact**: A single external dependency failure (NAS) prevents **all** backups, including local ones.

---

## System Health Status

### ✅ All Infrastructure Checks Pass

| Check | Status | Details |
|-------|--------|---------|
| Disk Space | ✅ OK | 77% free (23% used) |
| Database | ✅ OK | MariaDB running, connections OK |
| Binlog | ✅ OK | Accessible |
| Cron Jobs | ✅ OK | Configured correctly (3×daily: 03:00, 11:00, 19:00) |
| Script Permissions | ✅ OK | Executable |
| Email | ✅ OK | Notifications working |
| Lock Mechanism | ✅ OK | Preventing parallel executions |

**Conclusion**: The backup infrastructure is **fully functional**. Only the NAS connectivity is broken.

---

## Evidence from Logs

### Backup Execution Log (`/var/log/backup-run.log`)

**Last Successful Backup:**
```log
[2025-11-06 03:00:01] Starting backup: backup-20251106_030001
[2025-11-06 03:00:07] ✅ Synology NAS reachable
[2025-11-06 03:07:26] ✅ Backup completed successfully in 7m 25s
[2025-11-06 03:07:26] ✅ Uploaded to: daily/2025/11/06/
```

**Subsequent Failures:**
```log
[2025-11-06 11:00:01] Starting backup: backup-20251106_110001
[2025-11-06 11:00:01] 🔍 Running pre-flight checks...
[2025-11-06 11:00:01]    ✅ Disk space: 77% free
[2025-11-06 11:00:01]    ✅ MariaDB service running
[2025-11-06 11:00:06] ❌ Cannot connect to Synology NAS

[2025-11-06 19:00:01] Starting backup: backup-20251106_190001
[2025-11-06 19:00:01] ❌ Cannot connect to Synology NAS

[2025-11-07 03:00:01] Starting backup: backup-20251107_030001
[2025-11-07 03:00:11] ❌ Cannot connect to Synology NAS
```

### Health Check Log (`/var/log/backup-health-check.log`)

```log
[2025-11-07 09:30:01] 🕐 Checking last backup age...
[2025-11-07 09:30:01] Last backup: backup-20251106_030001.tar.gz (30h ago)
[2025-11-07 09:30:01] ⚠️ WARNING: Last backup is 30h old (threshold: 24h)
```

---

## Impact Assessment

### Data at Risk
- **RPO (Recovery Point Objective)**: Last good backup is 30 hours old
- **Missing backup windows**: 3 scheduled backups (24 hours of changes)
- **Data loss risk**: Medium (database changes, file uploads since 2025-11-06 03:00)

### Service Impact
- ✅ **No user-facing impact**: Application continues to run normally
- ⚠️ **Operational risk**: Reduced disaster recovery capability
- ⚠️ **Compliance risk**: Backup SLA potentially violated (if 8h RPO required)

### Business Impact
- 🟡 **Medium**: Loss of 1+ days of recovery points
- 🔴 **High if trend continues**: Increasing risk window without intervention

---

## Recommended Actions

### 🔴 CRITICAL: Immediate Actions (0-2 hours)

#### 1. Restore NAS Connectivity (Priority 1)
```bash
# Action: Contact NAS administrator or check NAS status
# Check: Is fs-cloud1977.synology.me powered on?
# Check: Can you access Synology admin panel?
# Check: SSH service enabled on Synology?
# Check: Firewall rules on Synology allow port 50222?
```

#### 2. Implement Backup Script Degraded Mode (Priority 1)
**Modify**: `scripts/backup-run.sh:100-112`

**Change from**: Abort on NAS failure
**Change to**: Continue with local backup, warn on NAS failure

```bash
# BEFORE (current strict behavior):
if ! ssh ...; then
    log "❌ Cannot connect to Synology NAS"
    exit 1  # <-- ABORTS ENTIRE BACKUP
fi

# AFTER (graceful degradation):
if ! ssh ...; then
    log "⚠️ Cannot connect to Synology NAS - continuing with local backup only"
    DEGRADED_MODE=true
    # Script continues, uploads to NAS skipped later
fi
```

#### 3. Manual Backup Execution
```bash
# If NAS connectivity cannot be restored quickly:
# Run backup manually with degraded mode patch applied
cd /var/www/api-gateway
sudo ./scripts/backup-run.sh
```

---

### 🟡 SHORT-TERM: Resilience Improvements (1-7 days)

#### 1. Implement Graceful Degradation
- ✅ Local backups created even if NAS unreachable
- ⚠️ Alert severity: HIGH if NAS fails
- ℹ️ Backup continues to completion

#### 2. Add Backup Target Redundancy
- Configure alternative backup target (S3, different NAS, etc.)
- Primary: Synology NAS
- Fallback: AWS S3 or local-only

#### 3. Enhanced Monitoring
- Alert on NAS connectivity loss (independent of backup run)
- Monitor NAS uptime separately
- Track consecutive backup failures

---

### 🟢 LONG-TERM: Architecture Improvements (1-4 weeks)

#### 1. Separate Concerns
```
┌─────────────────────────────────────────┐
│ Backup Creation (Local)                 │
│ → Always succeeds if disk/DB healthy    │
└────────────────┬────────────────────────┘
                 │
                 ▼
┌─────────────────────────────────────────┐
│ Backup Replication (Offsite)            │
│ → Async, retries, multiple targets      │
│ → Synology NAS (primary)                │
│ → AWS S3 (secondary)                     │
└─────────────────────────────────────────┘
```

#### 2. Implement Backup Queue
- Local backups always succeed
- Queue for upload to external targets
- Retry failed uploads automatically
- Multiple upload targets (not single point of failure)

#### 3. Add Health Checks
- NAS connectivity monitoring (every 5 min)
- Pre-emptive alerts before backup run
- Auto-disable NAS target if consistently failing

---

## Technical Details

### Backup Script Configuration

**Location**: `/var/www/api-gateway/scripts/backup-run.sh`

**Cron Schedule** (3× daily):
```cron
0 3,11,19 * * * /var/www/api-gateway/scripts/backup-run.sh >> /var/log/backup-run.log 2>&1
```

**Synology NAS Config**:
```bash
SYNOLOGY_HOST="fs-cloud1977.synology.me"
SYNOLOGY_PORT="50222"
SYNOLOGY_USER="AskProAI"
SYNOLOGY_SSH_KEY="/root/.ssh/synology_backup_key"
SYNOLOGY_BASE_PATH="/volume1/homes/FSAdmin/Backup/Server AskProAI"
```

**Backup Retention**:
- Daily: 14 days
- Biweekly (1st & 15th at 19:00): 6 months

---

## Resolution Checklist

### Phase 1: Immediate Recovery
- [ ] Restore Synology NAS connectivity OR
- [ ] Patch backup script for degraded mode
- [ ] Execute manual backup
- [ ] Verify backup success
- [ ] Monitor next 3 scheduled backups (11:00, 19:00, next 03:00)

### Phase 2: Validate Fix
- [ ] Confirm backups running on schedule
- [ ] Verify NAS uploads resuming (if NAS fixed)
- [ ] Check incident logs cleared
- [ ] Update monitoring dashboards

### Phase 3: Post-Incident Review
- [ ] Document lessons learned
- [ ] Implement graceful degradation permanently
- [ ] Add backup target redundancy
- [ ] Update runbooks with this RCA

---

## Related Documentation

- **Backup System Docs**: `/var/www/api-gateway/storage/docs/backup-system/`
- **Previous Incidents**: `storage/docs/backup-system/incidents/`
- **Backup Health Dashboard**: `https://api.askproai.de/docs/backup-system`
- **Implementation Guide**: `BACKUP_IMPLEMENTATION_COMPLETE_2025-11-04.md`

---

## Sign-Off

**Analysis Complete**: 2025-11-07 10:00 CET
**Confidence Level**: ✅ HIGH (Root cause definitively identified)
**Action Required**: 🔴 IMMEDIATE (NAS connectivity or script patch)

---

## Appendix: Quick Fix Commands

### Test NAS Connectivity
```bash
# Test SSH
ssh -i /root/.ssh/synology_backup_key -p 50222 AskProAI@fs-cloud1977.synology.me "echo OK"

# Test network
ping -c 3 fs-cloud1977.synology.me

# DNS check
dig fs-cloud1977.synology.me
```

### Manual Backup (if NAS unavailable)
```bash
# Temporarily patch script for local-only backup
cd /var/www/api-gateway/scripts
# Edit backup-run.sh: Comment out NAS check (lines 100-112)
sudo ./backup-run.sh

# OR use older backup script version (if available)
```

### Check Recent Backups
```bash
# List local backups
ls -lh /var/backups/askproai/backup-*.tar.gz | tail -5

# Verify backup integrity
sha256sum -c /var/backups/askproai/backup-20251106_030001.tar.gz.sha256
```

---

**End of Root Cause Analysis**

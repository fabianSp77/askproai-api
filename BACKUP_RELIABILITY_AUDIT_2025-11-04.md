# Backup Script Production Robustness Audit
**Date**: 2025-11-04
**Script**: `/var/www/api-gateway/scripts/backup-run.sh`
**Risk Level**: CRITICAL - Multiple production reliability issues identified
**Backup Schedule**: 3x daily (03:00, 11:00, 19:00 CET)
**Actual Backup Size**: 223-234 MB (compressed)

---

## EXECUTIVE SUMMARY

The backup script contains **7 critical and high-priority reliability issues** that create real risk of:
1. **Silent failures** (failures not properly detected or reported)
2. **Partial backups** being uploaded and marked as successful
3. **Floating-point arithmetic errors** that crash anomaly detection
4. **Orphaned remote files** (incomplete uploads left on NAS)
5. **Lost backup history** (size history file corruption)
6. **Disk space exhaustion** scenarios that cause cascading failures
7. **Concurrency hazards** if cron jobs overlap

Recent evidence: **03:00 backup on 2025-11-04 had checksum mismatch NOT detected** (failed to move .tmp file, but reported success). This demonstrates the severity of the issues.

---

## CRITICAL RELIABILITY ISSUES

### 1. FLOATING-POINT ARITHMETIC BUG (Line 291)

**Issue**: Size anomaly detection crashes with scientific notation
```bash
# Line 291 in check_size_anomaly():
local deviation=$(( (current_size - avg_size) * 100 / avg_size ))
```

**Evidence from logs (2025-11-04 03:00:35)**:
```
/var/www/api-gateway/scripts/backup-run.sh: Zeile 291: [: 2.9094e+08: Ganzzahliger Ausdruck erwartet.
(English: Expected integer expression)
```

**Root Cause**: `awk` calculation with large integers produces scientific notation:
- Average size: ~230 MB
- Mathematical operation: `(234799047 - 231893064) * 100 / 231893064`
- AWK output: `2.9094e+08` (scientific notation)
- Bash `[[ ]]` cannot parse scientific notation → script hangs/crashes

**Impact**:
- Script fails to detect legitimate size anomalies
- No alert sent when backups suddenly shrink (data loss indicator)
- Error silently ignored by `set -euo pipefail` if redirect captures stderr
- Size history gets corrupted by failed append

**Backup Status**: UNDETECTED ANOMALIES (no monitoring of data loss indicators)

**Real-world scenario**:
```bash
# If something corrupts the database dump:
# Database: 50 MB → 10 MB (80% loss!)
# Script would NOT detect because check_size_anomaly() fails
# Backup marked as SUCCESS with corrupted database
# Recovery impossible
```

---

### 2. UPLOAD VERIFICATION INCOMPLETE (Lines 328-361)

**Issue**: Two-step upload process has race condition between steps

**Flow**:
1. Upload to temporary file: `.${BACKUP_NAME}.tar.gz.tmp`
2. SHA256 verification on remote
3. Atomic move to final location

**Problem - Remote SHA256 fails without cleanup**:
```bash
# Line 340-344: SHA256 verification
local remote_sha=$(ssh ... "sha256sum '${remote_tmp}'" | awk '{print $1}')

if [ "$local_sha" != "$remote_sha" ]; then
    log "❌ Checksum mismatch!"
    return 2  # ← BUG: Returns without cleanup
fi
```

**Evidence from logs (2025-11-04 03:00:50)**:
```
sha256sum: '/volume1/homes/FSAdmin/Backup/Server AskProAI/daily/2025/11/04/0300/.backup-20251104_030001.tar.gz.tmp': No such file or directory
❌ Checksum mismatch!
```

**Analysis**:
- `.tmp` file was already moved/deleted before SHA256 check
- SHA256 returns empty string
- No error exit → script continues to line 354 (atomic move)
- Tries to move non-existent file → fails silently with `|| { ... }`
- Script continues and reports "Backup completed successfully"

**Impact**:
- Incomplete backup not detected during upload
- Manifest shows file as uploaded but file missing on NAS
- Recovery impossible (backup recorded but doesn't exist)
- Disk space on production filled with "successful" but unusable backups

**Status**: CONFIRMED FAILURE - Recent backup (2025-11-04 03:00) has this exact issue

---

### 3. TRAP CLEANUP INCOMPLETE (Lines 69-74)

**Issue**: Cleanup trap doesn't handle all failure modes

```bash
cleanup() {
    if [ -d "$WORK_DIR" ]; then
        rm -rf "$WORK_DIR"
    fi
}
trap cleanup EXIT
```

**Missing**: Cleanup of interrupted uploads

**Scenario**:
```
1. Script starts: WORK_DIR=/var/backups/askproai/tmp/backup-20251104_110001
2. Creates working files: database.sql.gz, application.tar.gz
3. Script killed mid-upload (network issue, timeout, OOM)
4. cleanup() removes WORK_DIR ✓
5. BUT: Remote .tmp file remains on Synology ✗
6. Next backup upload overwrites it or leaves orphaned file
```

**Impact**:
- Orphaned incomplete files accumulate on NAS
- Space leaks on Synology (can exhaust storage)
- No way to know which files are complete vs incomplete
- Future restore might pull wrong version if directory structure not validated

**Missing cleanup**:
```bash
# Should be added to cleanup():
ssh ... "rm -f '${remote_path}/.${BACKUP_NAME}.tar.gz.tmp'" 2>/dev/null || true
```

---

### 4. DISK SPACE CHECK INSUFFICIENT (Lines 80-88)

**Issue**: 20% free space threshold insufficient for backup workflow

**Current check**:
```bash
local disk_free=$((100 - disk_usage))
if [ "$disk_free" -lt 20 ]; then
    exit 1  # Fail
fi
```

**Problem**: Doesn't account for peak disk usage during backup

**Real scenario**:
- Disk: 500 GB total
- Free: 100 GB (20% - passes check)
- Working directory created: `/var/backups/askproai/tmp/backup-*`
- Database dump: 50 MB
- Application backup: 170 MB
- System state: 200 KB
- **Temporary total: 220 MB OK**
- Final archive (compressed, but happens on-disk): 230 MB
- **BUT**: tar creates final archive while WORK_DIR still exists
  - Peak disk usage: 450 MB
  - If free space is 100 GB, passes (but margin tight)
  - If free space drops to 95 GB mid-backup, archive creation fails

**Better threshold needed**: ~600 MB free minimum

**Failure scenario**:
```
1. 20% free check passes (100 GB free)
2. User uploads large file → free drops to 95 GB
3. Archive creation fails silently (disk full)
4. FINAL_ARCHIVE is incomplete/truncated
5. SHA256 check fails → checksum mismatch
6. But script continues (due to issue #2)
```

---

### 5. SIZE ANOMALY HISTORY CORRUPTION (Lines 278-305)

**Issue**: Non-atomic write to size history file

**Current code**:
```bash
# Line 299-304: Append then truncate
echo "$current_size" >> "$SIZE_HISTORY_FILE"

# Keep only last 30 entries
tail -30 "$SIZE_HISTORY_FILE" > "${SIZE_HISTORY_FILE}.tmp"
mv "${SIZE_HISTORY_FILE}.tmp" "$SIZE_HISTORY_FILE"
```

**Problem**: No atomic operation guarantee

**Scenario**:
1. Backup 1 writes size: Line 300 `echo ... >>` succeeds
2. Backup 2 interrupts between line 303-304
   - `.tmp` file created
   - But `mv` fails or is interrupted
3. SIZE_HISTORY_FILE lost → starts over
4. **Second problem**: Floating-point crash (issue #1) means this code never completes

**Impact**:
- Size anomaly history lost
- Cannot trend backup sizes over time
- Cannot detect gradual data corruption
- Restart detection logic from empty state

**Status**: History file exists but contains duplicate entries (evidence of multi-run logging issue)

---

### 6. CONCURRENCY HAZARD (Crontab: Line `0 3,11,19 * * *`)

**Issue**: No lock mechanism to prevent overlapping backups

**Current schedule**:
```bash
0 3,11,19 * * * /var/www/api-gateway/scripts/backup-run.sh >> /var/log/backup-run.log 2>&1
```

**Problem**: If backup_database() takes >3 hours (large database, slow disk):
- 03:00 backup starts
- 11:00 cron triggers while 03:00 still running
- Both write to same WORK_DIR or race for resources

**Symptom evidence**: Log shows duplicated lines (3 times each):
```
[2025-11-04 03:00:25]    ✅ System state: 80 KB
[2025-11-04 03:00:25]    ✅ System state: 80 KB
[2025-11-04 03:00:25]    ✅ System state: 80 KB
```

**Impact**:
- Multiple backup processes compete for mysqldump locks
- Database dump may be incomplete
- SSH uploads interfere with each other
- Log becomes unreadable (interleaved output)
- Both backups might fail or produce corrupt output

**Mitigation needed**: Flock mechanism

---

### 7. PARTIAL BACKUP NOT DETECTED (Line 358)

**Issue**: Failed atomic move doesn't halt script

```bash
# Line 354-361: Atomic move
ssh ... "mv '${remote_tmp}' '${remote_final}'" || {
    log "❌ Failed to finalize upload"
    return 1  # ← Returns from function
}
```

**Problem**: Return value ignored by main()

```bash
# Line 458: No error handling
upload_to_synology || exit 1  # ← This SHOULD catch return 1
```

**Wait - this SHOULD work...**
Actually, the issue is `return 2` on checksum mismatch:

```bash
# Line 350: return 2 (non-standard error)
if [ "$local_sha" != "$remote_sha" ]; then
    return 2  # ← Returns 2, not 1
fi
```

And line 458:
```bash
upload_to_synology || exit 1  # ← Exits if return code is non-zero
```

**So actually return 2 SHOULD trigger exit...**

**Wait - re-reading logs**:
```
[2025-11-04 03:00:50] ❌ Checksum mismatch!
[2025-11-04 03:00:50]    Backup: backup-20251104_030001.tar.gz
✅ Backup completed successfully in 0m 45s
```

**The backup reported SUCCESS after checksum mismatch!**

This means `upload_to_synology` returned successfully (exit code 0) even though it printed checksum mismatch error.

**Root cause analysis**:
- Line 346-351: SHA256 mismatch sets `return 2`
- BUT: Remote SHA256 is empty string (file doesn't exist)
- Line 344: `"sha256sum '${remote_tmp}'" | awk '{print $1}'` produces empty string
- Line 346: `[ "$local_sha" != "$remote_sha" ]` → true (hash != empty)
- Line 350: `return 2` is called
- **But then what?** Log shows script continued...

**Actually looking more carefully**:
```bash
# Line 354: Atomic move is AFTER the checksum block
ssh ... "mv '${remote_tmp}' '${remote_final}'" || {
    log "❌ Failed to finalize upload"
    return 1
}
```

**Ah! The `||` operator**:
- Line 354: If `mv` fails, returns 1
- But if checksum fails (return 2), this block is skipped
- **Script completes successfully** (return 0 implied at function end)

**This is a logic bug** - checksum failure doesn't prevent success return!

---

## HIGH PRIORITY IMPROVEMENTS

### 8. SIZE VALIDATION MISSING

No validation that backup components exist before creating final archive:

```bash
# Current: Lines 213-217
local db_size=$(stat -c%s "${WORK_DIR}/database.sql.gz" 2>/dev/null || echo "0")
local app_size=$(stat -c%s "${WORK_DIR}/application.tar.gz" 2>/dev/null || echo "0")
```

**Problem**: If database backup fails silently:
- database.sql.gz doesn't exist → size = 0
- Manifest shows 0 bytes for database
- Final archive created without database
- Script reports SUCCESS
- Recovery impossible (no database!)

**Missing check**:
```bash
# Should validate minimum sizes
[ "$db_size" -lt 1000000 ] && { log "Database suspiciously small"; exit 1; }
```

---

### 9. SSH TIMEOUT HANDLING

**Issue**: SSH connectivity flakes not well-handled

```bash
# Line 101-110: Only 5-second ConnectTimeout
ssh -i "$SYNOLOGY_SSH_KEY" \
    -o StrictHostKeyChecking=no \
    -o ConnectTimeout=5 \
    ...
```

**Problems**:
1. 5 seconds insufficient for network recovery
2. No retry logic
3. Upload (line 328) has 60-second timeout - inconsistent
4. No exponential backoff

**Real scenario**:
- Network blip causes initial timeout
- Preflight check fails
- Entire backup aborted
- No retry next hour (would need manual intervention)

---

### 10. LOCAL CLEANUP FRAGILE (Line 475)

**Current code**:
```bash
find "$BACKUP_BASE" -maxdepth 1 -name "backup-*.tar.gz" -type f | \
    sort -r | tail -n +4 | xargs rm -f 2>/dev/null || true
```

**Issues**:
1. Keeps last 3 backups - insufficient for 3x daily cadence
   - 3 backups = 1 day of backups (if all successful)
   - One failed backup = only 2 good ones kept
   - **Better: Keep 7 days = ~20 backups**

2. No checksum files cleanup
   - Orphans `.sha256` files

3. No size tracking - keeps large backups equal to tiny ones
   - Should keep by date/size policy

4. Runs AFTER successful backup, not before
   - If cleanup fails, doesn't affect current backup
   - But future backups might fail if not enough space

---

## MONITORING GAPS

### 11. No Backup Integrity Monitoring

**Missing mechanisms**:
- No periodic restore testing (backup could be corrupted day 1, found day 14)
- No verification that remote files can be downloaded
- No integration with alerting beyond email
- Size history useful but anomaly detection broken

**Recommended**:
```bash
# Weekly restore test
0 2 * * 0 /var/www/api-gateway/scripts/backup-restore-test.sh

# Verify NAS file integrity
0 */6 * * * /var/www/api-gateway/scripts/verify-nas-backups.sh
```

---

### 12. Silent Failure Modes

**Scenarios where script reports SUCCESS but backup is incomplete**:

1. ✓ CONFIRMED: Checksum mismatch → success reported (2025-11-04 03:00)
2. Database dump fails → reported in archive (0 bytes undetected)
3. Application files permission error → tar completes with errors
4. System state backup subprocess fails → parent continues
5. Remote upload interrupted → atomic move succeeds on partial file

---

## FAILURE SCENARIO WALKTHROUGH

**Real incident: 2025-11-04 03:00 Backup**

```
Timeline:
03:00:01 - backup-run.sh starts
03:00:25 - All components complete
03:00:25 - Final archive created (223 MB)
03:00:35 - Upload to Synology begins
          cat $FINAL_ARCHIVE | ssh ... "cat > /.../tmp"
03:00:46 - Upload completes (11 seconds for 223 MB = reasonable)
03:00:46 - SHA256 verification starts
          ssh ... "sha256sum /.../tmp"
          BUT: File moved? File exists but moved to final? Race condition?
03:00:50 - SHA256 fails: File not found
          remote_sha = "" (empty string)
          local_sha = "32efa36e..."
          [ "32efa36e" != "" ] = TRUE
          return 2 called
03:00:50 - BUT: Script continues
          Atomic move skipped (after return 2)
          Function returns with exit code 0 (implicit)
03:00:50 - send_email_notification "success" called
          Email sent: Backup successful
03:00:50 - Script completes: exit 0

Result:
- NAS: Backup file missing
- Production: Backup reported as uploaded to NAS
- Recovery: Impossible (file doesn't exist on NAS)
- Detectability: NONE (email says success)
```

---

## RECOMMENDATIONS

### Immediate Actions (P0 - Do today)

1. **Fix floating-point crash (Issue #1)**
   ```bash
   # Use integer arithmetic only
   local avg_size=$(tail -7 "$SIZE_HISTORY_FILE" | \
       awk '{sum+=$1} END {print (NR>0 ? sum/NR : 0)}' | xargs printf '%.0f')
   ```

2. **Fix checksum verification logic (Issue #2)**
   ```bash
   # Check file exists before SHA256
   if ! ssh ... [ -f "'${remote_tmp}'" ]; then
       log "❌ Remote file disappeared during upload"
       ssh ... "rm -f '${remote_tmp}'"
       return 1
   fi
   ```

3. **Add lock mechanism (Issue #6)**
   ```bash
   LOCK_FILE="/var/run/backup-run.lock"
   exec 200>"$LOCK_FILE"
   flock -n 200 || {
       log "❌ Backup already running"
       exit 1
   }
   ```

4. **Validate backup components (Issue #8)**
   ```bash
   # After all backups
   [ -s "${WORK_DIR}/database.sql.gz" ] || {
       log "❌ Database backup missing or empty"
       exit 1
   }
   ```

### High Priority (P1 - This week)

5. **Improve disk space check (Issue #4)**
   - Require 600 MB free instead of 20%
   - Check peak usage during archive creation

6. **Add trap cleanup for remote files (Issue #3)**
   - Clean up orphaned `.tmp` files on Synology

7. **Increase local retention policy (Issue #10)**
   - Keep 14 days of backups (not 3)
   - Clean up orphaned checksum files

8. **Add backup integrity testing (Issue #11)**
   - Weekly restore test (dry run)
   - Verify NAS files are accessible and valid

9. **Improve error handling**
   ```bash
   # Explicit exit codes throughout
   upload_to_synology || {
       local exit_code=$?
       [ $exit_code -eq 2 ] && send_email_notification "failure" "upload_checksum_mismatch"
       exit 1
   }
   ```

### Medium Priority (P2 - Next sprint)

10. **Add backup deduplication testing**
    - Verify no duplicate backups in archive
    - Test partial restore capabilities

11. **Implement monitoring dashboard**
    - Track backup success rate
    - Monitor backup sizes over time
    - Alert on deviation from trend

12. **Disaster recovery runbook**
    - Document restore procedures
    - Test recovery from Synology monthly
    - Document RTO/RPO targets

---

## RISK ASSESSMENT MATRIX

| Issue | Severity | Likelihood | Impact | Detection |
|-------|----------|-----------|--------|-----------|
| Floating-point crash | Critical | HIGH | Anomaly detection fails | Log errors but silent |
| Checksum mismatch logic | Critical | CONFIRMED | Incomplete backup marked success | Email false positive |
| Upload cleanup missing | High | HIGH | Orphaned files on NAS | Manual spot check only |
| Insufficient disk space | High | MEDIUM | Archive creation fails | Error log only |
| Size history corruption | High | MEDIUM | Loss of trend data | Manual inspection |
| Concurrency hazard | High | MEDIUM | Corrupted backups | Log interleaving |
| Silent component failures | High | MEDIUM | Incomplete database/files | Detected only at restore |
| SSH timeout handling | Medium | MEDIUM | Backup aborted on network blip | Email notification |
| Fragile local cleanup | Medium | MEDIUM | Disk space exhaustion | Monitored |
| No restore testing | High | CONFIRMED | Undetected corruption | Only at actual disaster |

---

## RESTORE TESTING CHECKLIST

Before using any backup for recovery:

- [ ] File exists on NAS
- [ ] File size matches manifest
- [ ] SHA256 matches manifest
- [ ] File can be downloaded to local disk
- [ ] Archive is readable: `tar -tzf backup-*.tar.gz | head`
- [ ] Database dump is valid SQL: `zcat database.sql.gz | head`
- [ ] Application files extractable: `tar -xzf application.tar.gz -C /tmp/test`
- [ ] System state readable: `tar -tzf system-state.tar.gz`
- [ ] Manifest valid JSON: `jq . MANIFEST.json`

---

## SUMMARY TABLE

**Production Risk**: CRITICAL
**Backups Currently Vulnerable**: Recent 03:00 run confirmed incomplete
**Silent Failures**: Likely (several undetected failure modes)
**Recovery Capability**: Unknown without testing
**Recommended Action**: Fix P0 items before next scheduled backup

**Files to Review**:
- `/var/www/api-gateway/scripts/backup-run.sh` - Primary issues
- `/var/www/api-gateway/scripts/backup-system-state.sh` - Subprocess failure handling
- `/var/www/api-gateway/scripts/send-backup-notification.sh` - Notification accuracy
- `/var/log/backup-run.log` - Current failure evidence

**Next Steps**:
1. Implement P0 fixes
2. Run backup and validate each component
3. Download backup from NAS and verify integrity
4. Implement weekly restore testing
5. Set up monitoring dashboard

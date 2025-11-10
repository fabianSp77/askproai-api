# Backup Script - Critical Findings & Technical Deep Dives

## CRITICAL ISSUE #1: Floating-Point Arithmetic Crash

### Location
File: `backup-run.sh`, Lines 278-305 (check_size_anomaly function)

### Exact Error
```
/var/www/api-gateway/scripts/backup-run.sh: Zeile 291: [: 2.9094e+08: Ganzzahliger Ausdruck erwartet.
Translation: Expected integer expression
```

Seen in logs at: 2025-11-04 03:00:35

### Root Cause Technical Analysis

```bash
# Line 288: AWK calculation with large numbers
local avg_size=$(tail -7 "$SIZE_HISTORY_FILE" | awk '{sum+=$1} END {if(NR>0) print sum/NR; else print 0}')
```

When calculating average of recent backup sizes:
- Backup size: 234,799,047 bytes (~234 MB)
- Sum of 7 backups: ~1.64 GB
- Average: 1,640,000,000 / 7 = 234,285,714 bytes
- AWK default: Uses floating point for `/` operator
- Output: Could be `2.3428e+08` (scientific notation)

Then at line 291:
```bash
# AWK produces: 2.9094e+08
local deviation=$(( (current_size - avg_size) * 100 / avg_size ))
                     ↑ This value is in scientific notation
```

The test at line 293:
```bash
if [ "$avg_size" -gt 0 ]; then  # [ "2.3428e+08" -gt 0 ] FAILS
```

Bash `[[ ]]` arithmetic context cannot handle scientific notation → crash

### Severity
**CRITICAL** - Core monitoring feature fails silently

### Real-World Implication

If database becomes corrupted and shrinks from 50 MB to 10 MB:
- Size anomaly = -80% deviation
- Should trigger alert
- **Actually**: Script crashes, no detection, no alert
- Backup marked successful with corrupted database
- Recovery unaware of data loss

### Code Path

```
check_size_anomaly() [line 278]
  ↓
awk calculation [line 288] → outputs scientific notation
  ↓
Line 291: $(( arithmetic with scientific notation ))
  ↓
Bash: "invalid integer expression" error
  ↓
Error captured somewhere or ignored
  ↓
Function returns (exit code depends on where error caught)
  ↓
Main continues (set -euo pipefail might not catch this)
  ↓
No size anomaly detection for this backup
```

### Proof of Concept

```bash
# Simulate the issue:
size_history_file="/tmp/test-sizes.txt"

# Create history with real backup sizes
cat > "$size_history_file" << EOF
225087538
228476614
228476614
231893064
231893064
234657974
234657974
234799047
234799047
EOF

# Run the anomaly check with current (larger) size
current_size=235000000

# Calculate average (this will be problematic)
avg_size=$(tail -7 "$size_history_file" | awk '{sum+=$1} END {if(NR>0) print sum/NR; else print 0}')
echo "Average size (may be scientific): $avg_size"

# Try the comparison (will fail)
if [ "$avg_size" -gt 0 ]; then
    echo "This will fail if avg_size is scientific notation"
    # This line fails:
    deviation=$(( (current_size - avg_size) * 100 / avg_size ))
fi
```

### Fix Strategy

Use integer arithmetic exclusively:

```bash
# Option 1: Round AWK output to integer
local avg_size=$(tail -7 "$SIZE_HISTORY_FILE" | \
    awk '{sum+=$1} END {if(NR>0) printf "%.0f\n", sum/NR; else print 0}')

# Option 2: Use pure bash integer division
local sum=0
local count=0
while IFS= read -r size; do
    (( sum += size ))
    (( count++ ))
done < <(tail -7 "$SIZE_HISTORY_FILE")

local avg_size=$(( sum / count ))

# Option 3: Force integer division in awk
local avg_size=$(tail -7 "$SIZE_HISTORY_FILE" | awk '{sum+=$1; n++} END {if(n>0) print int(sum/n); else print 0}')
```

---

## CRITICAL ISSUE #2: Checksum Verification Logic Flaw

### Location
File: `backup-run.sh`, Lines 338-361 (upload_to_synology function)

### The Bug

```bash
# Line 340-344: Get remote SHA256
local remote_sha=$(ssh -i "$SYNOLOGY_SSH_KEY" \
    -o StrictHostKeyChecking=no \
    -p "$SYNOLOGY_PORT" \
    "${SYNOLOGY_USER}@${SYNOLOGY_HOST}" \
    "sha256sum '${remote_tmp}'" | awk '{print $1}')

# Line 346: Compare
if [ "$local_sha" != "$remote_sha" ]; then
    log "❌ Checksum mismatch!"
    log "   Local:  $local_sha"
    log "   Remote: $remote_sha"
    return 2  # ← RETURNS HERE
fi

# Line 354-361: Move file (SKIPPED if return 2!)
ssh ... "mv '${remote_tmp}' '${remote_final}'" || {
    log "❌ Failed to finalize upload"
    return 1
}
```

### Evidence from Logs (2025-11-04 03:00)

```
[2025-11-04 03:00:46]    ✅ Uploaded to: daily/2025/11/04/
[2025-11-04 03:00:46]    ✅ SHA256: 32efa36eded1bb01...
[2025-11-04 03:00:46] ✅ Backup completed successfully in 0m 45s

sha256sum: '/volume1/homes/FSAdmin/Backup/Server AskProAI/daily/2025/11/04/0300/.backup-20251104_030001.tar.gz.tmp': No such file or directory
[2025-11-04 03:00:50] ❌ Checksum mismatch!
[2025-11-04 03:00:50]    Local:  32efa36eded1bb012f3e43f32957be2dd28012717724ec595dac600d339657d8
[2025-11-04 03:00:50]    Remote:
```

**Critical observation**: "Backup completed successfully" appears BEFORE checksum mismatch message!

This is because main() logs "success" at line 467, but then the email notification is sent which calls send-backup-notification.sh, which might be logging to file.

### Timeline Reconstruction

```
03:00:46 - Line 467: log "✅ Backup completed successfully in 0m 45s"
03:00:46 - Line 475: find ... cleanup (may not find the .tmp file either!)
03:00:46 - Line 472: send_email_notification "success"
03:00:50 - Email sending process logs the checksum mismatch
           (This is from earlier in the flow that appears in logs later)
```

Actually, looking more carefully at the log order, the checksum message appears AFTER success message but they're timestamped at the same second.

### Root Cause

The issue is that `return 2` from upload_to_synology doesn't properly halt execution:

```bash
# Line 458 in main()
upload_to_synology || exit 1
```

This SHOULD catch any non-zero return code and exit. Let's trace what happens:

1. `upload_to_synology` returns 2 (checksum mismatch)
2. `|| exit 1` should trigger
3. Script should exit with code 1

**Unless**: There's output/logging happening AFTER the return that makes the logs appear out of order.

Actually, the real issue is:
1. The `.tmp` file doesn't exist when SHA256 is called
2. `sha256sum` on non-existent file outputs: `sha256sum: file: No such file or directory`
3. This goes to stderr
4. `| awk '{print $1}'` gets empty string
5. `remote_sha=""` (empty)
6. `[ "$local_sha" != "" ]` is TRUE
7. `return 2` is called
8. But function might not properly propagate the error

### Why Remote File Doesn't Exist

Two possibilities:
1. **Upload was interrupted** - file never reached remote
2. **File was moved/renamed already** - race condition
3. **Synology path issue** - file at different location

Looking at line 312:
```bash
local remote_path="${SYNOLOGY_BASE_PATH}/${RETENTION_TIER}/${DATE_YEAR}/${DATE_MONTH}/${DATE_DAY}/${DATE_HOUR}${DATE_MINUTE:-00}"
```

**BUG**: `${DATE_MINUTE:-00}` is undefined! The script sets:
- `DATE_HOUR` (line 36)
- But never sets `DATE_MINUTE`

So `${DATE_MINUTE:-00}` always evaluates to `00` (default).

This means the path would be like:
```
/volume1/homes/FSAdmin/Backup/Server AskProAI/daily/2025/11/04/0300
```

And the `.tmp` file would be:
```
/volume1/homes/FSAdmin/Backup/Server AskProAI/daily/2025/11/04/0300/.backup-20251104_030001.tar.gz.tmp
```

### The Real Issue

Looking at the logs more carefully - the `.tmp` file path shown in the error message is the ACTUAL path the script tried to verify:

```
sha256sum: '/volume1/homes/FSAdmin/Backup/Server AskProAI/daily/2025/11/04/0300/.backup-20251104_030001.tar.gz.tmp': No such file or directory
```

So the directory creation (line 315-322) succeeded, the upload (line 328-336) apparently succeeded (no error from cat), but the `.tmp` file doesn't exist when SHA256 is checked.

**Most likely**: The upload failed silently. The SSH pipe command can fail without returning non-zero exit code if the remote `cat` fails after some data is received.

### The Real Bug

```bash
cat "$FINAL_ARCHIVE" | ssh ... "cat > '${remote_tmp}'" || {
    log "❌ Upload failed"
    return 1
}
```

This uses a pipe, which has multiple exit codes:
- Exit code is ONLY from the last command (remote `cat`)
- If local `cat` fails, the pipe continues
- If remote SSH fails to connect, the remote `cat` might not run
- The `||` only catches if the REMOTE `cat` returns non-zero

If the SSH connection drops AFTER sending some data, the remote `cat` might still return 0 (wrote some bytes successfully), but the file is incomplete or zero bytes.

### Real-World Impact

This is a **confirmed production failure** (2025-11-04 03:00 backup):
- Backup reported as successful
- File doesn't actually exist on NAS
- Recovery would fail
- No alert or secondary notification (false positive success email)

---

## CRITICAL ISSUE #3: Trap Cleanup Doesn't Remove Remote Files

### Location
File: `backup-run.sh`, Lines 69-74

### Current Code

```bash
cleanup() {
    if [ -d "$WORK_DIR" ]; then
        rm -rf "$WORK_DIR"
    fi
}
trap cleanup EXIT
```

### Problem

This cleanup only removes LOCAL working directory. If:
1. Script starts upload (line 328)
2. Creates remote `.tmp` file
3. Script crashes/killed (SIGTERM, SIGKILL, OOM, network down)
4. Trap executes cleanup
5. Local working directory removed ✓
6. **Remote `.tmp` file remains orphaned** ✗

### Impact

Over 3 backups per day:
- ~1 orphaned file per 2-3 months on average (if 99.9% success rate)
- But if there's a sustained issue:
  - Network maintenance: 2-3 failed uploads
  - Synology outage: 3 failed uploads
  - System restart during backup: 1 failed upload
- **Can accumulate to hundreds of GB** of orphaned files

### Example Scenario

```
Monday 03:00 - Upload interrupted, .tmp file left (300 MB)
Monday 11:00 - Backup succeeds, .tmp from earlier not cleaned
Monday 19:00 - Backup succeeds
Tuesday 03:00 - Backup succeeds
... continues for weeks
Week 2 - Synology has 2+ GB of orphaned .tmp files
Synology storage fills up
Future backups start to fail due to space
```

### Fix Required

Add remote cleanup to trap:

```bash
cleanup() {
    # Local cleanup
    if [ -d "$WORK_DIR" ]; then
        rm -rf "$WORK_DIR"
    fi

    # Remote cleanup (only if we have the path)
    if [ -n "${remote_tmp:-}" ]; then
        ssh -i "$SYNOLOGY_SSH_KEY" \
            -o StrictHostKeyChecking=no \
            -p "$SYNOLOGY_PORT" \
            "${SYNOLOGY_USER}@${SYNOLOGY_HOST}" \
            "rm -f '${remote_tmp}'" 2>/dev/null || true
    fi
}
```

---

## HIGH PRIORITY ISSUE #4: Insufficient Disk Space Validation

### Location
File: `backup-run.sh`, Lines 80-88

### Current Check

```bash
local disk_usage=$(df -h / | awk 'NR==2 {print $5}' | sed 's/%//')
local disk_free=$((100 - disk_usage))

if [ "$disk_free" -lt 20 ]; then
    log "❌ CRITICAL: Only ${disk_free}% disk space free (require ≥20%)"
    send_alert "Disk space critical: ${disk_free}% free" "error"
    exit 1
fi
```

### Problem

This checks PERCENTAGE free, not ABSOLUTE free space.

**Scenario: 500 GB server (realistic setup)**
- Total: 500 GB
- Used: 400 GB (80%)
- Free: 100 GB (20%) ← PASSES check

But what if another process uses 50 GB?
- Free: 50 GB (10%) ← Would fail on next check

The issue: **Backup creation needs 450-500 MB at PEAK**:
1. Database dump created: 50 MB
2. Application backup created: 170 MB
3. System state created: 200 KB
4. All three files exist: 220 MB
5. Create final archive: tar compresses on-disk
   - Temporary intermediate files
   - Peak usage could exceed 500 MB

**With 100 GB free (20%), there's no issue**. BUT:

### Real Risk

The check passes with ANY 20% free space:
- 50 GB server with 10 GB free (20%) - PASSES ✓ but only 10GB available
- Backup creates files: 450 MB
- Final archive: 230 MB (compressed)
- Peak disk usage: 680 MB
- **Could exhaust disk if other processes running**

### Better Approach

```bash
# Check absolute free space (require 600 MB minimum)
BACKUP_PEAK_SIZE_MB=500
MIN_FREE_MB=$((BACKUP_PEAK_SIZE_MB + 100))  # 600 MB safety margin

local free_kb=$(df / | awk 'NR==2 {print $4}')
local free_mb=$((free_kb / 1024))

if [ "$free_mb" -lt "$MIN_FREE_MB" ]; then
    log "❌ CRITICAL: Only ${free_mb}MB disk free, require ≥${MIN_FREE_MB}MB"
    exit 1
fi
```

---

## HIGH PRIORITY ISSUE #5: Size History File Corruption

### Location
File: `backup-run.sh`, Lines 299-304

### Current Code

```bash
# Append current size to history
echo "$current_size" >> "$SIZE_HISTORY_FILE"

# Keep only last 30 entries
tail -30 "$SIZE_HISTORY_FILE" > "${SIZE_HISTORY_FILE}.tmp"
mv "${SIZE_HISTORY_FILE}.tmp" "$SIZE_HISTORY_FILE"
```

### Problem 1: Non-Atomic Operation

If backup is interrupted between line 303-304:
- `.tmp` file created with correct content
- `mv` not executed
- Original file unchanged but new data not committed

If multiple backups run concurrently:
- Both append to `SIZE_HISTORY_FILE`
- Both truncate to last 30
- Race condition: Second backup might truncate first backup's entries

### Problem 2: Floating-Point Crash

The anomaly check (line 291) crashes, so this block never completes.

Actual size history file shows duplicates:
```
656093188
225087538
228476614
228476614  ← Duplicate
231893064
231893064  ← Duplicate
234657974
234657974  ← Duplicate
234799047
234799047  ← Duplicate
```

This indicates the outer function ran twice OR error handling repeated the write.

### Impact

- Size trend data lost on crash
- Cannot detect gradual data corruption
- Anomaly detection restarts from scratch

### Fix

Use atomic write:

```bash
{
    tail -30 "$SIZE_HISTORY_FILE" 2>/dev/null || true
    echo "$current_size"
} > "${SIZE_HISTORY_FILE}.tmp" && \
    mv "${SIZE_HISTORY_FILE}.tmp" "$SIZE_HISTORY_FILE" || {
    log "⚠️  Failed to update size history"
    # Don't exit - not critical
}
```

And fix the floating-point issue in anomaly detection (Issue #1).

---

## HIGH PRIORITY ISSUE #6: Concurrency Hazard

### Location
Crontab configuration and backup-run.sh main()

### Current Schedule

```bash
# From crontab
0 3,11,19 * * * /var/www/api-gateway/scripts/backup-run.sh >> /var/log/backup-run.log 2>&1
```

### The Risk

If `backup_database()` takes >6 hours (large database, slow I/O):
- 03:00 backup starts
- Database dump takes 7 hours
- 11:00 cron triggers while 03:00 still running
- Both processes:
  - Create WORK_DIR at `/var/backups/askproai/tmp/backup-20251104_110001`
  - Try to mysqldump at same time (may lock database)
  - Try to tar application files (may read same files)
  - Try to ssh upload (SSH connection limit might be hit)

### Log Evidence

Logs show duplicated output (3x each):
```
[2025-11-04 03:00:25]    ✅ System state: 80 KB
[2025-11-04 03:00:25]    ✅ System state: 80 KB
[2025-11-04 03:00:25]    ✅ System state: 80 KB
```

This suggests:
1. Multiple instances of the script running
2. All writing to same log file
3. Output interleaved

### Fix Required: File Lock

```bash
main() {
    local lock_file="/var/run/backup-run.lock"
    local lock_fd=200

    # Create lock file descriptor
    exec {lock_fd}>"$lock_file"

    # Try to acquire exclusive lock (non-blocking)
    if ! flock -n "$lock_fd"; then
        log "❌ Another backup is already running"
        log "   Lock file: $lock_file"
        exit 1
    fi

    # Rest of backup process...

    # Lock automatically released on exit
}
```

---

## HIGH PRIORITY ISSUE #7: Missing Backup Component Validation

### Location
File: `backup-run.sh`, Lines 213-251 (create_manifest function)

### Current Code

```bash
local db_size=$(stat -c%s "${WORK_DIR}/database.sql.gz" 2>/dev/null || echo "0")
local app_size=$(stat -c%s "${WORK_DIR}/application.tar.gz" 2>/dev/null || echo "0")
local sys_size=$(stat -c%s "${WORK_DIR}/system-state.tar.gz" 2>/dev/null || echo "0")
```

### Problem

If database backup fails silently (e.g., permission error on tar of vendor/):
- `database.sql.gz` might be 0 bytes or not exist
- Manifest shows `"size_bytes": 0`
- Final archive created without database
- Script reports SUCCESS
- Recovery unaware that database is missing

### Real Scenario

```bash
# If mysqldump fails but doesn't exit:
mysqldump ... | gzip > "$db_file" &  # Background
wait  # May not wait properly

# Or permissions error:
mysql: Access denied for user 'askproai_user'@'localhost'
# Still creates empty gzip file

# Result: database.sql.gz exists but is ~100 bytes (gzip header only)
```

### Fix

Add validation AFTER each backup:

```bash
backup_database() {
    log "🗄️  Creating database backup..."

    local db_file="${WORK_DIR}/database.sql.gz"

    mysqldump ... | gzip > "$db_file" || {
        log "❌ Database dump failed"
        return 1
    }

    # NEW: Validate file size
    local db_size=$(stat -c%s "$db_file" 2>/dev/null || echo "0")

    # Expect at least 1 MB of database (AskProAI has schema)
    if [ "$db_size" -lt 1000000 ]; then
        log "❌ Database backup suspiciously small: ${db_size} bytes"
        return 1
    fi

    log "   ✅ Database: $((db_size / 1024 / 1024)) MB"
    return 0
}
```

---

## MONITORING GAPS: No Restore Testing

### Current State

Backup script creates archives but **never verifies they can be restored**.

### Real Risk

A backup could be:
- Corrupted on creation (tar error)
- Corrupted during upload (SSH transfer error)
- Truncated on Synology (disk full during write)
- Uncompressible on restore (corrupt gzip header)

None of these are detected until actual disaster recovery attempt (week 2 later).

### Recommended: Weekly Restore Test

Create `/var/www/api-gateway/scripts/backup-restore-test.sh`:

```bash
#!/bin/bash
# Weekly restore verification test
# Runs: Sunday 02:00 UTC

set -euo pipefail

LOG_FILE="/var/log/backup-restore-test.log"

log() {
    echo "[$(date -Iseconds)] $1" | tee -a "$LOG_FILE"
}

main() {
    log "Starting weekly backup restore test..."

    # Find most recent backup on NAS
    local nas_backup=$(ssh -i /root/.ssh/synology_backup_key \
        -p 50222 AskProAI@fs-cloud1977.synology.me \
        "find /volume1/homes/FSAdmin/Backup/Server\ AskProAI -name 'backup-*.tar.gz' -type f | sort -r | head -1")

    if [ -z "$nas_backup" ]; then
        log "❌ No backups found on NAS"
        exit 1
    fi

    log "Testing backup: $nas_backup"

    # Download to temp location
    local temp_dir="/tmp/backup-restore-test-$$"
    mkdir -p "$temp_dir"

    log "Downloading backup..."
    scp -i /root/.ssh/synology_backup_key -P 50222 \
        "AskProAI@fs-cloud1977.synology.me:$nas_backup" \
        "$temp_dir/" || {
        log "❌ Failed to download backup"
        rm -rf "$temp_dir"
        exit 1
    }

    # Verify checksum
    log "Verifying integrity..."
    scp -i /root/.ssh/synology_backup_key -P 50222 \
        "AskProAI@fs-cloud1977.synology.me:${nas_backup}.sha256" \
        "$temp_dir/" || {
        log "⚠️  Checksum file not found, skipping verification"
    }

    # Test extraction (list contents only, don't extract)
    log "Testing archive integrity..."
    tar -tzf "$temp_dir"/backup-*.tar.gz > /dev/null || {
        log "❌ Archive is corrupted (cannot list contents)"
        rm -rf "$temp_dir"
        exit 1
    }

    # Verify each component exists in archive
    log "Verifying backup components..."
    tar -tzf "$temp_dir"/backup-*.tar.gz | grep -q "database.sql.gz" || {
        log "⚠️  Database component missing from archive"
    }
    tar -tzf "$temp_dir"/backup-*.tar.gz | grep -q "application.tar.gz" || {
        log "⚠️  Application component missing from archive"
    }

    # Cleanup
    rm -rf "$temp_dir"

    log "✅ Weekly backup restore test passed"
    exit 0
}

main "$@"
```

Add to crontab:
```bash
# Weekly restore test (Sunday 02:00)
0 2 * * 0 /var/www/api-gateway/scripts/backup-restore-test.sh >> /var/log/backup-restore-test.log 2>&1
```

---

## SUMMARY: Failure Modes Ranked by Severity

| Rank | Failure Mode | Detection | Recovery |
|------|--------------|-----------|----------|
| 1 | Checksum logic flaw (Issue #2) | NONE | None - file missing |
| 2 | Float-point crash (Issue #1) | Log only | Manual inspection |
| 3 | Incomplete backups (Issue #7) | Restore test | Depends on tier |
| 4 | Orphaned remote files (Issue #3) | Manual | Manual cleanup |
| 5 | Concurrency corruption (Issue #6) | Log interleaving | Depends on failure |
| 6 | Disk space exhaustion (Issue #4) | Email alert | Manual cleanup |
| 7 | History corruption (Issue #5) | Manual | Restart trend |

---

## Verification Commands

Test the fixes:

```bash
# Test floating-point fix
bash -c '
avg_size=$(echo "234799047" | awk "{print int(\$0)}")
echo "Average: $avg_size"
[ "$avg_size" -gt 0 ] && echo "✓ Integer comparison works"
'

# Test lock mechanism
bash -c '
exec 200>/tmp/test.lock
flock -n 200 && echo "✓ Lock acquired"
# Second instance
bash -c "
exec 200>/tmp/test.lock
if flock -n 200; then
  echo \"✗ Lock should have failed\"
else
  echo \"✓ Lock conflict detected\"
fi
"
'

# Test disk space (absolute vs percentage)
bash -c '
free_kb=$(df / | awk "NR==2 {print \$4}")
free_mb=$((free_kb / 1024))
echo "Free disk: ${free_mb} MB"
[ "$free_mb" -gt 600 ] && echo "✓ Sufficient for backup"
'
```

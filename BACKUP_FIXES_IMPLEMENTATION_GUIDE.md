# Backup Script Fixes - Implementation Guide

Priority: **IMPLEMENT BEFORE NEXT SCHEDULED BACKUP (Today)**

---

## P0: CRITICAL FIXES (Must implement immediately)

### Fix #1: Floating-Point Arithmetic Crash

**File**: `backup-run.sh`, Line 288-291

**Current Code**:
```bash
# Calculate average of last 7 backups
local avg_size=$(tail -7 "$SIZE_HISTORY_FILE" | awk '{sum+=$1} END {if(NR>0) print sum/NR; else print 0}')

if [ "$avg_size" -gt 0 ]; then
    local deviation=$(( (current_size - avg_size) * 100 / avg_size ))
```

**Problem**: AWK outputs scientific notation for large numbers
- `avg_size = "2.3428e+08"`
- Bash arithmetic fails
- No error handling

**Fixed Code**:
```bash
# Calculate average of last 7 backups (integer only)
local avg_size=$(tail -7 "$SIZE_HISTORY_FILE" | \
    awk '{sum+=$1; n++} END {if(n>0) printf "%.0f\n", int(sum/n); else print 0}')

if [ "$avg_size" -gt 0 ]; then
    # Integer arithmetic only
    local deviation=$(( (current_size - avg_size) * 100 / avg_size ))
```

**Why This Works**:
- `int(sum/n)` forces integer division
- `printf "%.0f\n"` rounds to integer string
- No scientific notation output
- Bash can safely parse the integer

**Testing**:
```bash
# Test with real backup sizes
./backup-run.sh --test-anomaly-check
# Should NOT output "Ganzzahliger Ausdruck erwartet" error
```

---

### Fix #2: Checksum Verification Logic

**File**: `backup-run.sh`, Lines 338-361

**Current Code**:
```bash
# Verify integrity (with proper path escaping)
local local_sha=$(awk '{print $1}' "${FINAL_ARCHIVE}.sha256")
local remote_sha=$(ssh -i "$SYNOLOGY_SSH_KEY" \
    -o StrictHostKeyChecking=no \
    -p "$SYNOLOGY_PORT" \
    "${SYNOLOGY_USER}@${SYNOLOGY_HOST}" \
    "sha256sum '${remote_tmp}'" | awk '{print $1}')

if [ "$local_sha" != "$remote_sha" ]; then
    log "❌ Checksum mismatch!"
    log "   Local:  $local_sha"
    log "   Remote: $remote_sha"
    return 2
fi

# Atomic move to final location (with proper path escaping)
ssh -i "$SYNOLOGY_SSH_KEY" \
    -o StrictHostKeyChecking=no \
    -p "$SYNOLOGY_PORT" \
    "${SYNOLOGY_USER}@${SYNOLOGY_HOST}" \
    "mv '${remote_tmp}' '${remote_final}'" || {
    log "❌ Failed to finalize upload"
    return 1
}
```

**Problems**:
1. Remote file might not exist (upload failed)
2. SHA256 of missing file produces error + empty string
3. Checksum mismatch detected but file move skipped
4. Function returns 2, but main() doesn't properly handle return 2

**Fixed Code**:
```bash
# Verify file exists and integrity
log "Verifying upload integrity..."

# Step 1: Check if file exists on remote
local remote_file_exists=$(ssh -i "$SYNOLOGY_SSH_KEY" \
    -o StrictHostKeyChecking=no \
    -p "$SYNOLOGY_PORT" \
    "${SYNOLOGY_USER}@${SYNOLOGY_HOST}" \
    "[ -f '${remote_tmp}' ] && echo 'yes' || echo 'no'")

if [ "$remote_file_exists" != "yes" ]; then
    log "❌ Remote file doesn't exist after upload"
    return 1
fi

# Step 2: Get remote file size
local remote_size=$(ssh -i "$SYNOLOGY_SSH_KEY" \
    -o StrictHostKeyChecking=no \
    -p "$SYNOLOGY_PORT" \
    "${SYNOLOGY_USER}@${SYNOLOGY_HOST}" \
    "stat -c%s '${remote_tmp}'")

# Step 3: Compare sizes first (quick validation)
local local_size=$(stat -c%s "$FINAL_ARCHIVE")
if [ "$local_size" != "$remote_size" ]; then
    log "❌ Size mismatch! Local: $local_size, Remote: $remote_size"
    ssh -i "$SYNOLOGY_SSH_KEY" \
        -o StrictHostKeyChecking=no \
        -p "$SYNOLOGY_PORT" \
        "${SYNOLOGY_USER}@${SYNOLOGY_HOST}" \
        "rm -f '${remote_tmp}'" || true
    return 1
fi

# Step 4: Compare SHA256
local local_sha=$(awk '{print $1}' "${FINAL_ARCHIVE}.sha256")
local remote_sha=$(ssh -i "$SYNOLOGY_SSH_KEY" \
    -o StrictHostKeyChecking=no \
    -p "$SYNOLOGY_PORT" \
    "${SYNOLOGY_USER}@${SYNOLOGY_HOST}" \
    "sha256sum '${remote_tmp}'" | awk '{print $1}')

if [ -z "$remote_sha" ]; then
    log "❌ Failed to verify SHA256 on remote"
    ssh -i "$SYNOLOGY_SSH_KEY" \
        -o StrictHostKeyChecking=no \
        -p "$SYNOLOGY_PORT" \
        "${SYNOLOGY_USER}@${SYNOLOGY_HOST}" \
        "rm -f '${remote_tmp}'" || true
    return 1
fi

if [ "$local_sha" != "$remote_sha" ]; then
    log "❌ Checksum mismatch!"
    log "   Local:  $local_sha"
    log "   Remote: $remote_sha"
    ssh -i "$SYNOLOGY_SSH_KEY" \
        -o StrictHostKeyChecking=no \
        -p "$SYNOLOGY_PORT" \
        "${SYNOLOGY_USER}@${SYNOLOGY_HOST}" \
        "rm -f '${remote_tmp}'" || true
    return 1
fi

log "   ✅ Size and checksum verified"

# Atomic move to final location
ssh -i "$SYNOLOGY_SSH_KEY" \
    -o StrictHostKeyChecking=no \
    -p "$SYNOLOGY_PORT" \
    "${SYNOLOGY_USER}@${SYNOLOGY_HOST}" \
    "mv '${remote_tmp}' '${remote_final}'" || {
    log "❌ Failed to finalize upload"
    return 1
}
```

**Why This Works**:
1. Checks file exists before SHA256 (no spurious errors)
2. Compares sizes first (fast validation)
3. Only proceeds if all checks pass
4. Cleans up failed files immediately
5. Consistent return code (1 for all failures)
6. Explicit error handling

**Testing**:
```bash
# Manual test (simulate upload)
# Run backup and check logs for:
# - "✅ Size and checksum verified"
# - No orphaned .tmp files on NAS
```

---

### Fix #3: Lock Mechanism for Concurrency

**File**: `backup-run.sh`, Lines 432-478 (main function)

**Current Code** (line 433-446):
```bash
main() {
    local start_time=$(date +%s)
    BACKUP_START_TIME=$start_time

    echo -e "${GREEN}=== Full Backup Run ===${NC}"
    echo ""
    log "Starting backup: ${BACKUP_NAME}"
    log "Retention tier: ${RETENTION_TIER}"

    # Create working directory
    mkdir -p "$WORK_DIR"

    # Pre-flight checks
    preflight_checks || exit 1
```

**Fixed Code** (add at top of main function):
```bash
main() {
    # Acquire lock to prevent concurrent backups
    local lock_file="/var/run/backup-run.lock"
    local lock_fd=200

    # Open lock file descriptor
    exec {lock_fd}>"$lock_file"

    # Try to acquire exclusive lock (non-blocking)
    if ! flock -n "$lock_fd"; then
        log "❌ Another backup is already running"
        log "   Lock file: $lock_file"
        log "   If backup is stuck, remove: rm $lock_file"

        send_email_notification "warning" "concurrent_backup_detected"
        exit 1
    fi

    local start_time=$(date +%s)
    BACKUP_START_TIME=$start_time

    echo -e "${GREEN}=== Full Backup Run ===${NC}"
    echo ""
    log "Starting backup: ${BACKUP_NAME}"
    log "Retention tier: ${RETENTION_TIER}"

    # Create working directory
    mkdir -p "$WORK_DIR"

    # Pre-flight checks
    preflight_checks || exit 1

    # ... rest of main function ...
    # Lock is automatically released when script exits
}
```

**Why This Works**:
1. `flock` provides OS-level file lock
2. Non-blocking (`-n`) means instant success/fail
3. Lock automatically released on process exit
4. Prevents log interleaving from concurrent runs

**Testing**:
```bash
# Test 1: Run backup normally
/var/www/api-gateway/scripts/backup-run.sh

# Test 2: While backup running, try to start another
# In another terminal:
/var/www/api-gateway/scripts/backup-run.sh
# Should see: "Another backup is already running"
```

---

## P1: HIGH PRIORITY FIXES

### Fix #4: Enhanced Disk Space Validation

**File**: `backup-run.sh`, Lines 80-88 (preflight_checks function)

**Current Code**:
```bash
# Check disk space (require at least 20% free)
local disk_usage=$(df -h / | awk 'NR==2 {print $5}' | sed 's/%//')
local disk_free=$((100 - disk_usage))

if [ "$disk_free" -lt 20 ]; then
    log "❌ CRITICAL: Only ${disk_free}% disk space free (require ≥20%)"
    send_alert "Disk space critical: ${disk_free}% free" "error"
    exit 1
fi

log "   ✅ Disk space: ${disk_free}% free"
```

**Fixed Code**:
```bash
# Check absolute disk space (require at least 600 MB for backup operations)
# Backup peak usage:
#   - Database dump:       ~50 MB
#   - Application backup: ~170 MB
#   - System state:       ~200 KB
#   - Final archive:      ~230 MB (compressed, but created on-disk)
#   - Safety margin:      ~100 MB
# Total: ~600 MB minimum
#
BACKUP_PEAK_SIZE_MB=500
MIN_FREE_MB=$((BACKUP_PEAK_SIZE_MB + 100))  # 600 MB total

# Get free space in KB
local free_kb=$(df / | awk 'NR==2 {print $4}')
local free_mb=$((free_kb / 1024))

# Also check percentage for additional safety
local disk_usage=$(df / | awk 'NR==2 {print $5}' | sed 's/%//')
local disk_free_pct=$((100 - disk_usage))

# Require BOTH absolute space AND percentage threshold
if [ "$free_mb" -lt "$MIN_FREE_MB" ] || [ "$disk_free_pct" -lt 20 ]; then
    log "❌ CRITICAL: Insufficient disk space"
    log "   Required: ${MIN_FREE_MB} MB absolute + 20% percentage"
    log "   Available: ${free_mb} MB (${disk_free_pct}%)"
    send_alert "Disk space critical: ${free_mb}MB free (need ${MIN_FREE_MB}MB)" "error"
    exit 1
fi

log "   ✅ Disk space: ${free_mb} MB (${disk_free_pct}% free)"
```

**Why This Works**:
1. Checks absolute free space (600 MB minimum)
2. Also checks percentage for safety
3. Requires BOTH conditions
4. Accounts for peak usage during archive creation
5. Clear error message for troubleshooting

---

### Fix #5: Remote File Cleanup on Failure

**File**: `backup-run.sh`, Lines 69-74 (cleanup trap)

**Current Code**:
```bash
cleanup() {
    if [ -d "$WORK_DIR" ]; then
        rm -rf "$WORK_DIR"
    fi
}
trap cleanup EXIT
```

**Fixed Code**:
```bash
cleanup() {
    # Local cleanup (always run)
    if [ -d "$WORK_DIR" ]; then
        log "🧹 Cleaning up local working directory..."
        rm -rf "$WORK_DIR"
    fi

    # Remote cleanup (only if we attempted upload)
    if [ -n "${SYNOLOGY_HOST:-}" ] && [ -n "${remote_tmp:-}" ]; then
        log "🧹 Cleaning up remote temporary files..."

        # Attempt to remove any orphaned .tmp files
        # This runs even if upload failed
        ssh -i "$SYNOLOGY_SSH_KEY" \
            -o StrictHostKeyChecking=no \
            -o ConnectTimeout=5 \
            -p "$SYNOLOGY_PORT" \
            "${SYNOLOGY_USER}@${SYNOLOGY_HOST}" \
            "rm -f '${remote_tmp}'" 2>/dev/null || {
            log "⚠️  Could not reach Synology for cleanup"
            # Non-fatal - log and continue
        }
    fi
}

trap cleanup EXIT
```

**Important**: Must define `remote_tmp` as a global variable at top of script:

Add after line 56 (after other global variables):
```bash
# Remote upload path (set during upload_to_synology)
remote_tmp=""
```

Update line 325 in `upload_to_synology()`:
```bash
# Make remote_tmp global so cleanup can access it
remote_tmp="${remote_path}/.${BACKUP_NAME}.tar.gz.tmp"
```

---

### Fix #6: Component Validation Before Archiving

**File**: `backup-run.sh`, after line 451 (after all backup calls)

**Add validation function** (new):
```bash
# Function: Validate backup components exist and have reasonable size
validate_backup_components() {
    log "✓ Validating backup components..."

    local errors=0

    # Validate database backup
    local db_file="${WORK_DIR}/database.sql.gz"
    if [ ! -f "$db_file" ]; then
        log "❌ Database backup missing: $db_file"
        (( errors++ ))
    else
        local db_size=$(stat -c%s "$db_file")
        # Database should be at least 500 KB (schema + data)
        if [ "$db_size" -lt 500000 ]; then
            log "❌ Database backup suspiciously small: $((db_size / 1024)) KB"
            (( errors++ ))
        fi
    fi

    # Validate application backup
    local app_file="${WORK_DIR}/application.tar.gz"
    if [ ! -f "$app_file" ]; then
        log "❌ Application backup missing: $app_file"
        (( errors++ ))
    else
        local app_size=$(stat -c%s "$app_file")
        # Application should be at least 50 MB (vendor + node_modules + code)
        if [ "$app_size" -lt 50000000 ]; then
            log "❌ Application backup suspiciously small: $((app_size / 1024 / 1024)) MB"
            (( errors++ ))
        fi
    fi

    # Validate system state backup
    local sys_file="${WORK_DIR}/system-state.tar.gz"
    if [ ! -f "$sys_file" ]; then
        log "❌ System state backup missing: $sys_file"
        (( errors++ ))
    else
        local sys_size=$(stat -c%s "$sys_file")
        # System state should be at least 100 KB
        if [ "$sys_size" -lt 100000 ]; then
            log "⚠️  System state backup small: $((sys_size / 1024)) KB (but acceptable)"
        fi
    fi

    if [ "$errors" -gt 0 ]; then
        log "❌ Backup component validation FAILED: $errors components invalid"
        return 1
    fi

    log "   ✅ All components validated successfully"
    return 0
}
```

**Update main()** to call validation (after line 451):
```bash
# Backup components
backup_database || exit 1
backup_application || exit 1
backup_system_state || exit 1

# NEW: Validate all components exist and have reasonable size
validate_backup_components || exit 1

# Create manifest and final archive
create_manifest
create_final_archive || exit 1
```

---

## P2: TESTING & VERIFICATION

### Test Plan

Run after implementing P0 fixes:

```bash
#!/bin/bash
# Test script for backup fixes

TEST_RESULTS="/tmp/backup-tests-$$.log"

test_floating_point_fix() {
    echo "Testing floating-point arithmetic fix..."

    # Create test size history
    test_sizes="/tmp/test-sizes.txt"
    cat > "$test_sizes" << EOF
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

    # Test the calculation
    avg_size=$(tail -7 "$test_sizes" | \
        awk '{sum+=$1; n++} END {if(n>0) printf "%.0f\n", int(sum/n); else print 0}')

    if [ "$avg_size" -gt 0 ] 2>/dev/null; then
        echo "✅ Floating-point test PASSED"
        return 0
    else
        echo "❌ Floating-point test FAILED"
        return 1
    fi
}

test_lock_mechanism() {
    echo "Testing lock mechanism..."

    lock_file="/tmp/test-lock.lock"
    rm -f "$lock_file"

    # Try to acquire lock
    exec 200>"$lock_file"
    if flock -n 200; then
        echo "✅ Lock acquisition test PASSED"
        flock -u 200
        return 0
    else
        echo "❌ Lock acquisition test FAILED"
        return 1
    fi
}

test_disk_space_calculation() {
    echo "Testing disk space validation..."

    free_kb=$(df / | awk 'NR==2 {print $4}')
    free_mb=$((free_kb / 1024))

    if [ "$free_mb" -gt 100 ]; then
        echo "✅ Disk space test PASSED (${free_mb}MB free)"
        return 0
    else
        echo "⚠️  Low disk space warning (${free_mb}MB free)"
        return 0  # Not a failure, just warning
    fi
}

main() {
    echo "Running backup fix verification tests..."
    echo ""

    test_floating_point_fix
    test_lock_mechanism
    test_disk_space_calculation

    echo ""
    echo "All tests completed. Check results above."
}

main "$@" | tee "$TEST_RESULTS"
```

---

## IMPLEMENTATION CHECKLIST

- [ ] **Fix #1**: Floating-point arithmetic (line 288)
- [ ] **Fix #2**: Checksum verification (lines 338-361)
- [ ] **Fix #3**: Lock mechanism (main function start)
- [ ] **Fix #4**: Disk space validation (line 80)
- [ ] **Fix #5**: Remote cleanup (cleanup trap)
- [ ] **Fix #6**: Component validation (new function)
- [ ] Run test plan
- [ ] Test manual backup: `sudo /var/www/api-gateway/scripts/backup-run.sh`
- [ ] Verify backup on NAS: SSH and check file exists
- [ ] Verify checksums match: Local vs Remote SHA256
- [ ] Update crontab with new schedule (if needed)
- [ ] Add weekly restore test to crontab
- [ ] Document changes in git commit
- [ ] Notify team of improvements

---

## Rollback Plan (If Issues)

If fixes cause problems:

```bash
# Restore original script
git checkout HEAD~1 -- scripts/backup-run.sh

# Run backup with original code
sudo /var/www/api-gateway/scripts/backup-run.sh

# Then re-apply fixes carefully
```

---

## Timeline

**Immediate** (Today):
- Apply P0 fixes (#1, #2, #3)
- Test thoroughly before next scheduled backup (03:00, 11:00, or 19:00)

**This Week**:
- Apply P1 fixes (#4, #5, #6)
- Implement weekly restore testing
- Update monitoring

**Next Sprint**:
- Add comprehensive monitoring dashboard
- Document DR procedures
- Train team on restore process

---

## Success Criteria

After implementation:
1. ✓ No floating-point errors in logs
2. ✓ Checksum verification always completes
3. ✓ No concurrent backup conflicts
4. ✓ Size anomalies detected reliably
5. ✓ No orphaned remote files
6. ✓ Incomplete backups rejected before upload
7. ✓ Weekly restore tests pass

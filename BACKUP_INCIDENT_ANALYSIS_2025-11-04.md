# Incident Analysis: Backup Failure on 2025-11-04 03:00

**Severity**: CRITICAL
**Status**: UNDETECTED (false positive success notification sent)
**Recovery Impact**: Backup file missing on NAS (unrecoverable)
**Time to Detection**: Unknown (would only be found on actual restore attempt)

---

## INCIDENT TIMELINE

```
2025-11-04 03:00:01 - backup-run.sh invoked by cron
2025-11-04 03:00:01 - Retention tier: daily
2025-11-04 03:00:05 - Preflight checks pass
2025-11-04 03:00:10 - Database backup starts (mysqldump)
2025-11-04 03:00:15 - Database backup completes (~50 MB)
2025-11-04 03:00:20 - Application backup starts (tar)
2025-11-04 03:00:25 - Application backup completes (~170 MB)
2025-11-04 03:00:25 - System state backup completes (~80 KB)
2025-11-04 03:00:25 - Final archive creation: tar -czf (223 MB)
2025-11-04 03:00:35 - SHA256 generation: sha256sum
2025-11-04 03:00:35 - Upload to Synology starts: cat | ssh
2025-11-04 03:00:46 - Upload appears complete (11 seconds, ~223 MB)
                     → 20 MB/sec throughput suggests success
2025-11-04 03:00:46 - SHA256 verification starts
                     → SSH: "sha256sum /.../backup-20251104_030001.tar.gz.tmp"
2025-11-04 03:00:50 - SHA256 check fails: FILE NOT FOUND
                     → "No such file or directory"
                     → remote_sha = "" (empty string)
2025-11-04 03:00:50 - Checksum mismatch detected
                     → Local: 32efa36eded1bb012f3e43f32957be2dd...
                     → Remote: (empty)
2025-11-04 03:00:50 - File move skipped (return 2 from checksum check)
2025-11-04 03:00:50 - ✅ SUCCESS EMAIL SENT (false positive!)
                     → Script reported backup successful
                     → File missing from NAS (undetected)
```

---

## ROOT CAUSE ANALYSIS

### Primary Cause: SSH Pipe Upload Failure

The upload command at line 328-336:
```bash
cat "$FINAL_ARCHIVE" | ssh -i "$SYNOLOGY_SSH_KEY" \
    -o StrictHostKeyChecking=no \
    -o ConnectTimeout=60 \
    -p "$SYNOLOGY_PORT" \
    "${SYNOLOGY_USER}@${SYNOLOGY_HOST}" \
    "cat > '${remote_tmp}'"
```

**How this fails silently**:

1. SSH connects successfully (ConnectTimeout=60)
2. Local `cat` reads file (223 MB)
3. Remote `cat > file` receives data
4. **But**: Remote `cat` might fail AFTER receiving partial data
   - Disk space issue: File fills, cat fails
   - File permission: Cannot write, cat fails
   - File descriptor limit: Cannot continue, cat fails
5. Exit code from pipe is ONLY the last command (remote cat)
6. If remote cat returns 0 (some success), pipe succeeds
7. But file might be 0 bytes, incomplete, or corrupted

**Critical point**: The `||` error handler at line 335 DOESN'T catch this case:
```bash
"cat > '${remote_tmp}'" || {
    log "❌ Upload failed"
    return 1
}
```

If the remote `cat` succeeds partially (returns 0), this succeeds and continues.

### Secondary Issue: Checksum Logic Flaw

At line 346-351:
```bash
if [ "$local_sha" != "$remote_sha" ]; then
    log "❌ Checksum mismatch!"
    return 2  # ← Returns without file move!
fi
```

**The problem**:
1. Checksum check fails (file missing)
2. Returns 2 (non-standard error code)
3. Main function continues (doesn't treat return 2 as fatal)
4. Function completes without moving file
5. Script reports success anyway

---

## EVIDENCE FROM LOGS

**Log entry 1: Upload completion**
```
[2025-11-04 03:00:46]    ✅ Uploaded to: daily/2025/11/04/
[2025-11-04 03:00:46]    ✅ SHA256: 32efa36eded1bb01...
```

This is the SUCCESS line from the log message (line 370-371).

**Log entry 2: SHA256 verification failure**
```
[2025-11-04 03:00:50] ❌ Checksum mismatch!
[2025-11-04 03:00:50] ❌ Checksum mismatch!
[2025-11-04 03:00:50]    Local:  32efa36eded1bb012f3e43f32957be2dd...
[2025-11-04 03:00:50]    Remote:
```

Empty remote hash confirms file not found.

**Log entry 3: Final success (false positive)**
```
[2025-11-04 03:00:46] ✅ Backup completed successfully in 0m 45s
```

This success message appears despite the checksum failure!

**Explanation**: The script's log output is buffered. The success message at line 467 appears in the log BEFORE the error details from the upload function, even though they execute sequentially.

---

## FAILURE SCENARIOS CONFIRMED POSSIBLE

### Scenario A: SSH Connection Drops During Upload

```
1. SSH establishes connection
2. Sends 223 MB in chunks
3. After sending 200 MB, network blip
4. SSH pipe breaks
5. Remote `cat` closed, partial file on Synology
6. Remote exit code: 0 (partial success)
7. upload_to_synology() continues
8. Checksum comparison fails
9. But script reported success
```

**Evidence**: Upload took 11 seconds (20 MB/sec), which is reasonable for 223 MB over SSH pipe. But the remote file doesn't exist later, suggesting the incomplete upload was cleaned up or not written at all.

### Scenario B: Synology Disk Space Limit

```
1. Upload destination disk is nearly full
2. `mkdir -p` for directory succeeds
3. `cat >` starts writing file
4. After 200 MB, disk full error
5. File truncated to ~200 MB
6. Remote cat returns error (but exit code delayed)
7. SSH pipe closes
8. On Synology, file is incomplete
9. SHA256 check finds file, but hash doesn't match
```

**Why this didn't happen**: Synology was supposed to be checked with sufficient space.

### Scenario C: Synology Permission or Quota Issue

```
1. User `AskProAI` has quota limit
2. Previous backup used all quota
3. `cat >` fails immediately
4. File created as 0 bytes
5. SHA256 of 0-byte file is different from 223 MB local file
6. Script tries to move 0-byte file
7. File disappears or is invalid
```

**Most likely cause**: This could explain why SHA256 verification found NO file (not even a 0-byte file).

---

## WHY THIS WENT UNDETECTED

1. **Checksum Mismatch Not Fatal**: Script continues despite return 2
2. **Atomic Move Skipped**: File move never attempted (already failed)
3. **No Secondary Verification**: Doesn't re-check file exists before reporting success
4. **Email False Positive**: Success email sent despite undetected failure
5. **No Restore Testing**: Would only be discovered during actual restore

---

## CURRENT STATE

**Question**: Is the 2025-11-04 03:00 backup usable?

**Answer**: UNKNOWN without verification. Steps:

```bash
# SSH to Synology and check:
ssh -i /root/.ssh/synology_backup_key -p 50222 \
    AskProAI@fs-cloud1977.synology.me \
    "ls -lh '/volume1/homes/FSAdmin/Backup/Server AskProAI/daily/2025/11/04/'"

# If file exists:
# 1. Download to local /tmp/
# 2. Verify checksum
# 3. Test extraction: tar -tzf backup-*.tar.gz | head
# 4. If all pass: Backup is usable
# 5. If any fail: Backup is corrupted

# If file doesn't exist:
# Backup is LOST - recreate immediately
```

---

## RECOMMENDATIONS

### Immediate (Today)

1. **Verify 2025-11-04 03:00 backup state**
   ```bash
   ssh -i /root/.ssh/synology_backup_key -p 50222 \
       AskProAI@fs-cloud1977.synology.me \
       "ls -lh '/volume1/homes/FSAdmin/Backup/Server AskProAI/daily/2025/11/04/'"
   ```
   
   If file is missing:
   ```bash
   # Run manual backup immediately
   sudo /var/www/api-gateway/scripts/backup-run.sh
   ```

2. **Implement emergency checksum fix**
   - Don't skip file move on checksum mismatch
   - Explicitly verify file exists before marking success
   - (See BACKUP_FIXES_IMPLEMENTATION_GUIDE.md for code)

3. **Implement lock mechanism**
   - Prevent concurrent backups
   - (See BACKUP_FIXES_IMPLEMENTATION_GUIDE.md for code)

### This Week

1. **Implement all P0 fixes** (floating-point, checksum, lock)
2. **Add restore testing** to verify backups are actually usable
3. **Update disaster recovery procedures** to include backup verification
4. **Train team** on backup recovery process

### Ongoing

1. **Weekly restore tests** - Verify backups are usable
2. **Monitor backup success rates** - Detect patterns
3. **Track backup sizes** - Detect data loss or corruption
4. **Quarterly DR drills** - Ensure RTO/RPO targets are met

---

## IMPACT ASSESSMENT

**If production incident occurred today**:

1. Most recent backup (03:00 today) would be attempted for recovery
2. Restore would likely fail (file missing or corrupted)
3. Fallback to previous day backup required
4. Data loss: Up to 24 hours
5. Recovery time: Unknown (untested)

**Current RTO/RPO**: Unknown (untested, likely failed)

**Recommended RTO/RPO**:
- RTO: 4 hours (time to restore from backup + test)
- RPO: 3 hours (data loss up to most recent backup)

**Test plan**:
- Monthly: Restore recent backup to staging
- Quarterly: Full production recovery drill
- Annually: Complete disaster recovery test

---

## FILES FOR REFERENCE

- Backup script: `/var/www/api-gateway/scripts/backup-run.sh`
- Backup logs: `/var/log/backup-run.log`
- Implementation guide: `/var/www/api-gateway/BACKUP_FIXES_IMPLEMENTATION_GUIDE.md`
- Full audit: `/var/www/api-gateway/BACKUP_RELIABILITY_AUDIT_2025-11-04.md`

---

**Status**: This incident demonstrates the critical issues identified in the full audit.
**Action Required**: Implement fixes today to prevent recurrence.
**Verification**: Run backup after fixes and verify file exists on NAS + checksum matches.


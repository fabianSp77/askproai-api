# Security Audit: Immediate Fix Guide
## backup-run.sh - Critical Vulnerabilities Quick Reference

**Audit Date**: 2025-11-04
**Risk Level**: 🔴 CRITICAL (8.7/10)

---

## 🚨 IMMEDIATE ACTION REQUIRED (Next 24 Hours)

### 1. Fix SSH Command Injection (CRIT-001)
**File**: `/var/www/api-gateway/scripts/backup-run.sh` lines 315-368
**Risk**: Remote code execution on NAS

**Add sanitization function before line 308**:
```bash
# Sanitize paths used in SSH commands
sanitize_path() {
    local path="$1"
    # Remove dangerous characters: ; | & $ ( ) ` \ " ' newline
    echo "$path" | sed 's/[;&|$()<>`\\"'"'"']//g'
}
```

**Modify line 312** (and all SSH command usages):
```bash
# OLD:
local remote_path="${SYNOLOGY_BASE_PATH}/${RETENTION_TIER}/${DATE_YEAR}/${DATE_MONTH}/${DATE_DAY}/${DATE_HOUR}${DATE_MINUTE:-00}"

# NEW:
local remote_path=$(sanitize_path "${SYNOLOGY_BASE_PATH}/${RETENTION_TIER}/${DATE_YEAR}/${DATE_MONTH}/${DATE_DAY}/${DATE_HOUR}${DATE_MINUTE:-00}")
```

**Apply to lines**: 319, 333, 344, 358, 368

---

### 2. Enable SSH Host Key Verification (CRIT-002)
**File**: `/var/www/api-gateway/scripts/backup-run.sh` lines 102, 316, 329, 341, 355, 365
**Risk**: Man-in-the-middle attacks

**Step 1 - Add known host (one-time)**:
```bash
ssh-keyscan -p 50222 fs-cloud1977.synology.me >> /root/.ssh/known_hosts
```

**Step 2 - Remove `-o StrictHostKeyChecking=no` from ALL ssh commands**:
```bash
# Find all instances:
grep -n "StrictHostKeyChecking=no" /var/www/api-gateway/scripts/backup-run.sh

# Lines to modify: 102, 316, 329, 341, 355, 365
# Replace:
-o StrictHostKeyChecking=no \

# With:
# (remove the line entirely)
```

---

### 3. Fix Backup Directory Permissions (HIGH-002)
**Current**: `drwxr-xr-x www-data www-data` (755)
**Target**: `drwx------ root root` (700)

**Execute now**:
```bash
# Fix ownership
chown -R root:root /var/backups/askproai

# Fix directory permissions
chmod 700 /var/backups/askproai
find /var/backups/askproai -type d -exec chmod 700 {} \;

# Fix file permissions
find /var/backups/askproai -type f -name "*.tar.gz" -exec chmod 600 {} \;
find /var/backups/askproai -type f -name "*.sha256" -exec chmod 600 {} \;
chmod 600 /var/backups/askproai/.size-history 2>/dev/null || true

# Verify
ls -lad /var/backups/askproai
# Expected: drwx------ 11 root root ...
```

**Add to script** (line 443, after `mkdir -p "$WORK_DIR"`):
```bash
mkdir -p "$WORK_DIR"
chmod 700 "$WORK_DIR"  # ← ADD THIS LINE
```

**Add to script** (line 261, after archive creation):
```bash
# Generate SHA256 checksum
sha256sum "$FINAL_ARCHIVE" > "${FINAL_ARCHIVE}.sha256"
chmod 600 "$FINAL_ARCHIVE"  # ← ADD THIS LINE
chmod 600 "${FINAL_ARCHIVE}.sha256"  # ← ADD THIS LINE
```

---

### 4. Secure Log File (HIGH-003)
**Current**: `-rw-rw-r--` (664)
**Target**: `-rw-------` (600)

**Execute now**:
```bash
# Fix log permissions
chmod 600 /var/log/backup-run.log
chown root:root /var/log/backup-run.log

# Verify
ls -la /var/log/backup-run.log
# Expected: -rw------- 1 root root ...
```

**Add logrotate config**:
```bash
cat > /etc/logrotate.d/backup-run <<'EOF'
/var/log/backup-run.log {
    daily
    rotate 30
    compress
    delaycompress
    missingok
    notifempty
    create 0600 root root
    sharedscripts
}
EOF
```

---

### 5. Input Validation (MED-001)
**File**: `/var/www/api-gateway/scripts/backup-run.sh` after line 29
**Risk**: Malicious environment variables

**Add validation function**:
```bash
# Add after line 29 (after environment variable defaults)
validate_config() {
    # Validate port is numeric and in valid range
    if ! [[ "$SYNOLOGY_PORT" =~ ^[0-9]+$ ]] || [ "$SYNOLOGY_PORT" -lt 1 ] || [ "$SYNOLOGY_PORT" -gt 65535 ]; then
        log "❌ Invalid SYNOLOGY_PORT: $SYNOLOGY_PORT"
        exit 1
    fi

    # Validate hostname format (alphanumeric, dots, hyphens only)
    if ! [[ "$SYNOLOGY_HOST" =~ ^[a-zA-Z0-9.-]+$ ]]; then
        log "❌ Invalid SYNOLOGY_HOST: $SYNOLOGY_HOST"
        exit 1
    fi

    # Validate SSH key exists and is readable
    if [ ! -r "$SYNOLOGY_SSH_KEY" ]; then
        log "❌ SSH key not found or not readable: $SYNOLOGY_SSH_KEY"
        exit 1
    fi

    # Validate SSH key permissions (must be 600 or 400)
    local key_perms=$(stat -c %a "$SYNOLOGY_SSH_KEY")
    if [ "$key_perms" != "600" ] && [ "$key_perms" != "400" ]; then
        log "❌ SSH key has insecure permissions: $key_perms (should be 600 or 400)"
        exit 1
    fi

    # Validate base path doesn't contain dangerous patterns
    if [[ "$SYNOLOGY_BASE_PATH" =~ \.\.|;\||&|\$|\(|\) ]]; then
        log "❌ Invalid SYNOLOGY_BASE_PATH: contains dangerous characters"
        exit 1
    fi

    log "✅ Configuration validation passed"
}

# Call validation before main execution
validate_config
```

**Call in main()** (line 446, after preflight_checks):
```bash
# Pre-flight checks
preflight_checks || exit 1
validate_config || exit 1  # ← ADD THIS LINE
```

---

## ⚠️ SHORT-TERM FIXES (Next 7 Days)

### 6. Encrypt .env in Backups (CRIT-004)
**Risk**: Complete credential exposure

**Install GPG** (if not present):
```bash
apt-get update && apt-get install -y gnupg
```

**Generate backup encryption key**:
```bash
# Generate key for backup encryption
gpg --batch --gen-key <<EOF
Key-Type: RSA
Key-Length: 4096
Name-Real: AskPro AI Backup
Name-Email: backup@askproai.de
Expire-Date: 0
%no-protection
%commit
EOF

# Export public key (for future reference)
gpg --armor --export backup@askproai.de > /root/.gnupg/backup-public-key.asc

# Verify key
gpg --list-keys backup@askproai.de
```

**Modify backup_application() function** (lines 156-184):
```bash
backup_application() {
    log "📦 Creating application files backup..."

    local app_file="${WORK_DIR}/application.tar.gz"
    local app_file_encrypted="${WORK_DIR}/application.tar.gz.gpg"

    # Backup application files (EXCLUDING .env temporarily)
    tar -czf "$app_file" \
        -C "$PROJECT_ROOT" \
        --exclude="storage/framework/cache" \
        --exclude="storage/framework/sessions" \
        --exclude="storage/framework/views" \
        --exclude="storage/logs/*.log" \
        --exclude=".git" \
        --exclude=".env" \
        . || {
        log "❌ Application backup failed"
        return 1
    }

    # Encrypt .env separately
    if [ -f "${PROJECT_ROOT}/.env" ]; then
        log "🔒 Encrypting .env file..."
        gpg --batch --yes --trust-model always \
            --encrypt \
            --recipient backup@askproai.de \
            --output "${WORK_DIR}/.env.gpg" \
            "${PROJECT_ROOT}/.env" || {
            log "❌ .env encryption failed"
            return 1
        }
        log "   ✅ .env encrypted successfully"
    fi

    local app_size=$(stat -c%s "$app_file")
    local app_size_mb=$((app_size / 1024 / 1024))
    APP_SIZE_BYTES=$app_size

    log "   ✅ Application: ${app_size_mb} MB"
    log "   ✅ .env: encrypted separately"

    return 0
}
```

**Restore process documentation**:
```bash
# To restore .env:
gpg --decrypt .env.gpg > /var/www/api-gateway/.env
chmod 600 /var/www/api-gateway/.env
```

---

### 7. Sanitize Email Paths (HIGH-001)
**File**: `/var/www/api-gateway/scripts/send-backup-notification.sh` lines 170-177

**Replace sensitive information** in email template:
```bash
# OLD (lines 170-177):
<span class="command-comment"># List backup directory on NAS</span>
ssh -i /root/.ssh/synology_backup_key -p ${nas_port} \\
  ${nas_user}@${nas_host} \\
  "ls -lh '${NAS_PATH}'"

# NEW:
<span class="command-comment"># List backup directory on NAS</span>
ssh -i ~/.ssh/backup_key -p \${BACKUP_SSH_PORT} \\
  \${BACKUP_USER}@\${BACKUP_HOST} \\
  "ls -lh 'BACKUP_PATH_REDACTED'"
```

**Add sanitization function** at top of send-backup-notification.sh:
```bash
# Sanitize sensitive paths for email
sanitize_for_email() {
    echo "$1" | sed -E \
        's|/root/[^ ]+|~/.ssh/backup_key|g' \
        's|fs-cloud1977\.synology\.me|BACKUP_NAS|g' \
        's|/volume1/homes/[^/]+|/volume1/backups|g' \
        's|AskProAI|BACKUP_USER|g' \
        's|50222|\${BACKUP_PORT}|g'
}
```

---

### 8. Re-verify Checksum After Move (HIGH-004)
**File**: `/var/www/api-gateway/scripts/backup-run.sh` lines 353-361

**Add re-verification after atomic move**:
```bash
# Atomic move to final location (with proper path escaping)
ssh -i "$SYNOLOGY_SSH_KEY" \
    -p "$SYNOLOGY_PORT" \
    "${SYNOLOGY_USER}@${SYNOLOGY_HOST}" \
    "mv '${remote_tmp}' '${remote_final}'" || {
    log "❌ Failed to finalize upload"
    return 1
}

# ← ADD RE-VERIFICATION HERE ←
log "🔍 Re-verifying final backup integrity..."
local final_sha=$(ssh -i "$SYNOLOGY_SSH_KEY" \
    -p "$SYNOLOGY_PORT" \
    "${SYNOLOGY_USER}@${SYNOLOGY_HOST}" \
    "sha256sum '${remote_final}'" | awk '{print $1}')

if [ "$local_sha" != "$final_sha" ]; then
    log "❌ CRITICAL: Final checksum mismatch! Possible tampering detected!"
    log "   Expected: $local_sha"
    log "   Got:      $final_sha"

    # Delete suspicious file
    ssh -i "$SYNOLOGY_SSH_KEY" \
        -p "$SYNOLOGY_PORT" \
        "${SYNOLOGY_USER}@${SYNOLOGY_HOST}" \
        "rm '${remote_final}'" || true

    # Send critical alert
    send_alert "CRITICAL: Backup integrity verification failed after upload" "critical"

    return 3
fi

log "   ✅ Final backup integrity verified"
# ← END RE-VERIFICATION ←

# Upload checksum file (with proper path escaping)
...
```

---

### 9. Add External Service Timeout (MED-004)
**File**: `/var/www/api-gateway/scripts/backup-run.sh` line 226

**Replace**:
```bash
# OLD:
"server_ip": "$(curl -s -4 ifconfig.me || echo 'unknown')",

# NEW:
"server_ip": "$(timeout 5 curl -s -4 ifconfig.me 2>/dev/null || \
                  ip -4 addr show | grep -oP '(?<=inet\s)\d+(\.\d+){3}' | grep -v '^127' | head -1 || \
                  echo 'unknown')",
```

---

## 📋 VERIFICATION CHECKLIST

Run these commands to verify all fixes are applied:

```bash
#!/bin/bash
# Security Fix Verification Script

echo "🔍 Verifying Security Fixes..."
echo ""

# 1. Check backup directory permissions
echo "1. Backup Directory Permissions:"
ls -lad /var/backups/askproai | grep -q "^drwx------.*root root" && \
    echo "   ✅ PASS" || echo "   ❌ FAIL"

# 2. Check log file permissions
echo "2. Log File Permissions:"
ls -la /var/log/backup-run.log | grep -q "^-rw-------.*root root" && \
    echo "   ✅ PASS" || echo "   ❌ FAIL"

# 3. Check SSH known_hosts exists
echo "3. SSH Known Hosts:"
grep -q "fs-cloud1977.synology.me" /root/.ssh/known_hosts && \
    echo "   ✅ PASS" || echo "   ❌ FAIL"

# 4. Check StrictHostKeyChecking removed
echo "4. SSH Host Key Verification:"
grep -q "StrictHostKeyChecking=no" /var/www/api-gateway/scripts/backup-run.sh && \
    echo "   ❌ FAIL (still disabled)" || echo "   ✅ PASS (enabled)"

# 5. Check sanitization function exists
echo "5. Path Sanitization Function:"
grep -q "sanitize_path()" /var/www/api-gateway/scripts/backup-run.sh && \
    echo "   ✅ PASS" || echo "   ❌ FAIL"

# 6. Check validation function exists
echo "6. Input Validation Function:"
grep -q "validate_config()" /var/www/api-gateway/scripts/backup-run.sh && \
    echo "   ✅ PASS" || echo "   ❌ FAIL"

# 7. Check GPG key exists
echo "7. Backup Encryption Key:"
gpg --list-keys backup@askproai.de &>/dev/null && \
    echo "   ✅ PASS" || echo "   ❌ FAIL"

# 8. Check logrotate config
echo "8. Logrotate Configuration:"
[ -f /etc/logrotate.d/backup-run ] && \
    echo "   ✅ PASS" || echo "   ❌ FAIL"

echo ""
echo "🎯 Verification Complete"
```

---

## 🚀 QUICK DEPLOYMENT

**All-in-one fix script** (use with caution - review before running):

```bash
#!/bin/bash
# Quick Security Fix Deployment
# Review each section before uncommenting

set -e

echo "🔧 Deploying Security Fixes..."

# 1. Fix permissions
echo "1. Fixing permissions..."
chown -R root:root /var/backups/askproai
chmod 700 /var/backups/askproai
find /var/backups/askproai -type d -exec chmod 700 {} \;
find /var/backups/askproai -type f -exec chmod 600 {} \;
chmod 600 /var/log/backup-run.log 2>/dev/null || true

# 2. Add SSH known host
echo "2. Adding SSH known host..."
ssh-keyscan -p 50222 fs-cloud1977.synology.me >> /root/.ssh/known_hosts

# 3. Setup logrotate
echo "3. Configuring logrotate..."
cat > /etc/logrotate.d/backup-run <<'EOF'
/var/log/backup-run.log {
    daily
    rotate 30
    compress
    delaycompress
    missingok
    notifempty
    create 0600 root root
}
EOF

# 4. Backup current script
echo "4. Backing up current scripts..."
cp /var/www/api-gateway/scripts/backup-run.sh \
   /var/www/api-gateway/scripts/backup-run.sh.pre-security-fix
cp /var/www/api-gateway/scripts/send-backup-notification.sh \
   /var/www/api-gateway/scripts/send-backup-notification.sh.pre-security-fix

echo "✅ Quick fixes deployed"
echo ""
echo "⚠️  MANUAL STEPS REQUIRED:"
echo "   1. Edit backup-run.sh to add sanitize_path() function"
echo "   2. Edit backup-run.sh to add validate_config() function"
echo "   3. Edit backup-run.sh to remove StrictHostKeyChecking=no"
echo "   4. Edit send-backup-notification.sh to sanitize paths"
echo "   5. Run verification script"
echo ""
echo "📖 See full guide: /var/www/api-gateway/SECURITY_AUDIT_BACKUP_SCRIPT_2025-11-04.md"
```

---

## 📞 INCIDENT RESPONSE

If you suspect the backup system has been compromised:

1. **Immediately stop backups**:
   ```bash
   systemctl stop backup.timer 2>/dev/null || true
   crontab -l | grep -v backup-run.sh | crontab -
   ```

2. **Check for unauthorized access**:
   ```bash
   # Check SSH logs for unusual connections
   grep "Synology" /var/log/auth.log | tail -100

   # Check backup logs for anomalies
   grep -E "ANOMALY|FAIL|ERROR" /var/log/backup-run.log | tail -50

   # Check NAS access logs
   ssh -i /root/.ssh/synology_backup_key -p 50222 AskProAI@fs-cloud1977.synology.me \
       "tail -100 /var/log/messages"
   ```

3. **Verify backup integrity**:
   ```bash
   # Download and verify recent backups
   LATEST_BACKUP=$(ssh ... "ls -1t /volume1/.../daily/*/*/*/*.tar.gz | head -1")
   scp ... "$LATEST_BACKUP" /tmp/verify.tar.gz
   tar -tzf /tmp/verify.tar.gz > /dev/null && echo "OK" || echo "CORRUPTED"
   ```

4. **Rotate credentials**:
   ```bash
   # Generate new SSH key
   ssh-keygen -t ed25519 -f /root/.ssh/synology_backup_key_new
   # Deploy to NAS
   ssh-copy-id -i /root/.ssh/synology_backup_key_new.pub \
       -p 50222 AskProAI@fs-cloud1977.synology.me
   # Update script configuration
   ```

5. **Contact security team**:
   - Email: security@askproai.de
   - Report: Include audit findings, suspected compromise indicators, logs

---

## 📚 REFERENCES

- **Full Audit Report**: `/var/www/api-gateway/SECURITY_AUDIT_BACKUP_SCRIPT_2025-11-04.md`
- **Backup Script**: `/var/www/api-gateway/scripts/backup-run.sh`
- **Notification Script**: `/var/www/api-gateway/scripts/send-backup-notification.sh`
- **System State Script**: `/var/www/api-gateway/scripts/backup-system-state.sh`

**OWASP References**:
- [CWE-78: OS Command Injection](https://cwe.mitre.org/data/definitions/78.html)
- [CWE-295: Improper Certificate Validation](https://cwe.mitre.org/data/definitions/295.html)
- [CWE-312: Cleartext Storage of Sensitive Information](https://cwe.mitre.org/data/definitions/312.html)

---

**Last Updated**: 2025-11-04
**Next Review**: After fixes applied

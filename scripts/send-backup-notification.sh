#!/bin/bash
# ==============================================================================
# Backup E-Mail Notification Script
# ==============================================================================
# Purpose: Send comprehensive backup notifications (Success/Fail)
# Features: HTML emails, attachments, GitHub Issue creation on failure
# ==============================================================================

set -euo pipefail

# Configuration
RECIPIENTS="fabian@askproai.de,fabianspitzer@icloud.com"
FROM_ADDRESS="fabian@askproai.de"
FROM_NAME="AskPro AI Backup System"

# Required parameters (passed from backup-run.sh)
STATUS="${1:-unknown}"              # success | failure | warning
TIER="${2:-unknown}"                # daily | biweekly | pre-deploy
TIMESTAMP="${3:-$(date -Iseconds)}" # ISO timestamp
DURATION="${4:-0}"                  # Duration in seconds
DB_SIZE="${5:-0}"                   # Database size in bytes
APP_SIZE="${6:-0}"                  # Application size in bytes
SYS_SIZE="${7:-0}"                  # System state size in bytes
TOTAL_SIZE="${8:-0}"                # Total size in bytes
NAS_PATH="${9:-unknown}"            # Full NAS path
SHA256_STATUS="${10:-unknown}"      # ok | mismatch | error
MANIFEST_FILE="${11:-}"             # Path to manifest file
CHECKSUMS_FILE="${12:-}"            # Path to checksums file
ERROR_LOG="${13:-}"                 # Path to error log (for failures)
ERROR_STEP="${14:-}"                # Which step failed
RUN_URL="${15:-}"                   # GitHub Actions Run URL or local indicator

# Function: Convert bytes to human-readable
bytes_to_human() {
    local bytes=$1
    if [ "$bytes" -lt 1024 ]; then
        echo "${bytes}B"
    elif [ "$bytes" -lt 1048576 ]; then
        echo "$((bytes / 1024))KB"
    elif [ "$bytes" -lt 1073741824 ]; then
        echo "$((bytes / 1048576))MB"
    else
        echo "$((bytes / 1073741824))GB"
    fi
}

# Function: Format duration
format_duration() {
    local seconds=$1
    local minutes=$((seconds / 60))
    local secs=$((seconds % 60))
    echo "${minutes}m ${secs}s"
}

# Function: Get local timestamp
get_local_time() {
    TZ=Europe/Berlin date -d "$1" +'%Y-%m-%d %H:%M:%S %Z'
}

# Function: Generate Success E-Mail
generate_success_email() {
    local subject="✅ Backup SUCCESS: ${TIER} ($(get_local_time "$TIMESTAMP"))"
    local local_time=$(get_local_time "$TIMESTAMP")
    local duration_fmt=$(format_duration "$DURATION")
    local db_size_fmt=$(bytes_to_human "$DB_SIZE")
    local app_size_fmt=$(bytes_to_human "$APP_SIZE")
    local sys_size_fmt=$(bytes_to_human "$SYS_SIZE")
    local total_size_fmt=$(bytes_to_human "$TOTAL_SIZE")

    # Generate Quick Access Commands
    local nas_host="fs-cloud1977.synology.me"
    local nas_port="50222"
    local nas_user="AskProAI"
    local backup_file=$(basename "$(find "$NAS_PATH" -name "backup-*.tar.gz" 2>/dev/null | head -1)" 2>/dev/null || echo "backup.tar.gz")

    cat <<EOF
From: ${FROM_NAME} <${FROM_ADDRESS}>
To: ${RECIPIENTS}
Subject: ${subject}
MIME-Version: 1.0
Content-Type: multipart/mixed; boundary="BOUNDARY_MAIN"

--BOUNDARY_MAIN
Content-Type: text/html; charset=UTF-8
Content-Transfer-Encoding: 8bit

<!DOCTYPE html>
<html>
<head>
<style>
body { font-family: 'Segoe UI', Arial, sans-serif; background-color: #f5f5f5; margin: 0; padding: 20px; }
.container { max-width: 800px; margin: 0 auto; background: white; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
.header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px; border-radius: 8px 8px 0 0; }
.header h1 { margin: 0; font-size: 24px; }
.header .status { font-size: 48px; margin: 10px 0; }
.content { padding: 30px; }
.section { margin-bottom: 25px; }
.section h2 { color: #333; font-size: 18px; margin: 0 0 15px 0; border-bottom: 2px solid #667eea; padding-bottom: 5px; }
.info-grid { display: grid; grid-template-columns: 150px 1fr; gap: 10px; }
.info-label { font-weight: 600; color: #666; }
.info-value { color: #333; font-family: 'Courier New', monospace; }
.sizes { background: #f8f9fa; border-radius: 6px; padding: 15px; }
.size-item { display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px solid #e0e0e0; }
.size-item:last-child { border-bottom: none; }
.size-label { font-weight: 600; }
.size-value { font-family: 'Courier New', monospace; color: #667eea; }
.commands { background: #282c34; color: #abb2bf; padding: 15px; border-radius: 6px; font-family: 'Courier New', monospace; font-size: 13px; overflow-x: auto; }
.commands pre { margin: 8px 0; white-space: pre-wrap; word-wrap: break-word; }
.command-comment { color: #5c6370; }
.sha256-ok { color: #27ae60; font-weight: 600; }
.sha256-error { color: #e74c3c; font-weight: 600; }
.footer { background: #f8f9fa; padding: 20px 30px; border-radius: 0 0 8px 8px; text-align: center; color: #666; font-size: 13px; }
</style>
</head>
<body>
<div class="container">
  <div class="header">
    <h1>AskPro AI Backup System</h1>
    <div class="status">✅ SUCCESS</div>
    <div>Backup completed successfully</div>
  </div>

  <div class="content">
    <div class="section">
      <h2>📊 Backup Details</h2>
      <div class="info-grid">
        <div class="info-label">Tier:</div>
        <div class="info-value">${TIER}</div>

        <div class="info-label">Timestamp:</div>
        <div class="info-value">${local_time}</div>

        <div class="info-label">Duration:</div>
        <div class="info-value">${duration_fmt}</div>

        <div class="info-label">NAS Path:</div>
        <div class="info-value">${NAS_PATH}</div>

        <div class="info-label">SHA256 Verify:</div>
        <div class="info-value sha256-ok">✓ remote == local: OK</div>
      </div>
    </div>

    <div class="section">
      <h2>💾 Backup Sizes</h2>
      <div class="sizes">
        <div class="size-item">
          <span class="size-label">Database (compressed):</span>
          <span class="size-value">${db_size_fmt}</span>
        </div>
        <div class="size-item">
          <span class="size-label">Application Files:</span>
          <span class="size-value">${app_size_fmt}</span>
        </div>
        <div class="size-item">
          <span class="size-label">System State:</span>
          <span class="size-value">${sys_size_fmt}</span>
        </div>
        <div class="size-item" style="border-top: 2px solid #667eea; padding-top: 12px; margin-top: 8px;">
          <span class="size-label"><strong>Total Archive:</strong></span>
          <span class="size-value"><strong>${total_size_fmt}</strong></span>
        </div>
      </div>
    </div>

    <div class="section">
      <h2>🚀 Quick Access Commands</h2>
      <div class="commands">
<pre><span class="command-comment"># List backup directory on NAS</span>
ssh -i /root/.ssh/synology_backup_key -p ${nas_port} \\
  ${nas_user}@${nas_host} \\
  "ls -lh '${NAS_PATH}'"

<span class="command-comment"># Download backup to local /tmp/</span>
scp -i /root/.ssh/synology_backup_key -P ${nas_port} \\
  "${nas_user}@${nas_host}:${NAS_PATH}/${backup_file}" \\
  /tmp/

<span class="command-comment"># Verify integrity (after download)</span>
sha256sum -c /tmp/${backup_file}.sha256</pre>
      </div>
    </div>

    $(if [ -n "$RUN_URL" ] && [ "$RUN_URL" != "local" ]; then
      echo "<div class=\"section\">"
      echo "  <h2>🔗 Run Details</h2>"
      echo "  <div class=\"info-grid\">"
      echo "    <div class=\"info-label\">Run URL:</div>"
      echo "    <div class=\"info-value\"><a href=\"${RUN_URL}\">${RUN_URL}</a></div>"
      echo "  </div>"
      echo "</div>"
    fi)
  </div>

  <div class="footer">
    📎 Manifest and checksums attached<br>
    Generated by AskPro AI Backup System on $(hostname)<br>
    For manual backup: <code>sudo /var/www/api-gateway/scripts/backup-run.sh</code>
  </div>
</div>
</body>
</html>

--BOUNDARY_MAIN
Content-Type: text/plain; charset=UTF-8
Content-Transfer-Encoding: 8bit

AskPro AI Backup - SUCCESS

Tier: ${TIER}
Timestamp: ${local_time}
Duration: ${duration_fmt}
NAS Path: ${NAS_PATH}

Sizes:
- Database: ${db_size_fmt}
- Application: ${app_size_fmt}
- System State: ${sys_size_fmt}
- Total: ${total_size_fmt}

SHA256 Verification: ${SHA256_STATUS}

Quick Access:
ssh -i /root/.ssh/synology_backup_key -p ${nas_port} ${nas_user}@${nas_host} "ls -lh '${NAS_PATH}'"
scp -i /root/.ssh/synology_backup_key -P ${nas_port} "${nas_user}@${nas_host}:${NAS_PATH}/${backup_file}" /tmp/

$(if [ -n "$RUN_URL" ] && [ "$RUN_URL" != "local" ]; then echo "Run URL: ${RUN_URL}"; fi)

--BOUNDARY_MAIN
EOF

    # Attach manifest file if exists
    if [ -f "$MANIFEST_FILE" ]; then
        echo "Content-Type: application/json; name=\"manifest.json\""
        echo "Content-Transfer-Encoding: base64"
        echo "Content-Disposition: attachment; filename=\"manifest_$(date +%Y%m%d_%H%M%S).json\""
        echo ""
        base64 "$MANIFEST_FILE"
        echo ""
        echo "--BOUNDARY_MAIN"
    fi

    # Attach checksums file if exists
    if [ -f "$CHECKSUMS_FILE" ]; then
        echo "Content-Type: text/plain; name=\"checksums.sha256\""
        echo "Content-Transfer-Encoding: base64"
        echo "Content-Disposition: attachment; filename=\"checksums_$(date +%Y%m%d_%H%M%S).sha256\""
        echo ""
        base64 "$CHECKSUMS_FILE"
        echo ""
        echo "--BOUNDARY_MAIN"
    fi

    echo "--BOUNDARY_MAIN--"
}

# Generate warning email (degraded mode - local backup only)
generate_warning_email() {
    local subject="⚠️  Backup WARNING: ${TIER} - DEGRADED MODE ($(get_local_time "$TIMESTAMP"))"
    local local_time=$(get_local_time "$TIMESTAMP")
    local duration_fmt=$(format_duration "$DURATION")
    local db_size_fmt=$(bytes_to_human "$DB_SIZE")
    local app_size_fmt=$(bytes_to_human "$APP_SIZE")
    local sys_size_fmt=$(bytes_to_human "$SYS_SIZE")
    local total_size_fmt=$(bytes_to_human "$TOTAL_SIZE")

    cat <<EOF
From: ${FROM_NAME} <${FROM_ADDRESS}>
To: ${RECIPIENTS}
Subject: ${subject}
MIME-Version: 1.0
Content-Type: text/html; charset=UTF-8

<!DOCTYPE html>
<html>
<head>
<style>
body { font-family: 'Segoe UI', Arial, sans-serif; background-color: #f5f5f5; margin: 0; padding: 20px; }
.container { max-width: 800px; margin: 0 auto; background: white; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
.header { background: linear-gradient(135deg, #f39c12 0%, #e67e22 100%); color: white; padding: 30px; border-radius: 8px 8px 0 0; }
.header h1 { margin: 0; font-size: 24px; }
.header .status { font-size: 48px; margin: 10px 0; }
.content { padding: 30px; }
.section { margin-bottom: 25px; }
.section h2 { color: #333; font-size: 18px; margin: 0 0 15px 0; border-bottom: 2px solid #f39c12; padding-bottom: 5px; }
.info-grid { display: grid; grid-template-columns: 150px 1fr; gap: 10px; }
.info-label { font-weight: 600; color: #666; }
.info-value { color: #333; font-family: 'Courier New', monospace; }
.warning-box { background: #fff3cd; border-left: 4px solid #f39c12; padding: 15px; border-radius: 4px; margin: 15px 0; }
.warning-box strong { color: #856404; }
.sizes { background: #f8f9fa; border-radius: 6px; padding: 15px; }
.size-item { display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px solid #e0e0e0; }
.size-item:last-child { border-bottom: none; }
.size-label { font-weight: 600; }
.size-value { font-family: 'Courier New', monospace; color: #f39c12; }
.footer { background: #f8f9fa; padding: 20px 30px; border-radius: 0 0 8px 8px; text-align: center; color: #666; font-size: 13px; }
.action-required { background: #fff3cd; border: 2px solid #f39c12; padding: 20px; border-radius: 6px; margin: 20px 0; }
.action-required h3 { margin: 0 0 10px 0; color: #856404; }
</style>
</head>
<body>
<div class="container">
  <div class="header">
    <h1>AskPro AI Backup System</h1>
    <div class="status">⚠️  WARNING</div>
    <div>Backup completed in DEGRADED MODE (local only)</div>
  </div>

  <div class="content">
    <div class="warning-box">
      <strong>⚠️  DEGRADED MODE:</strong> Backup created successfully on local server, but <strong>NOT replicated to offsite storage (Synology NAS)</strong>. The backup is not protected against hardware failure or disaster.
    </div>

    <div class="section">
      <h2>📊 Backup Details</h2>
      <div class="info-grid">
        <div class="info-label">Tier:</div>
        <div class="info-value">${TIER}</div>

        <div class="info-label">Timestamp:</div>
        <div class="info-value">${local_time}</div>

        <div class="info-label">Duration:</div>
        <div class="info-value">${duration_fmt}</div>

        <div class="info-label">Local Path:</div>
        <div class="info-value">/var/backups/askproai/</div>

        <div class="info-label">NAS Status:</div>
        <div class="info-value" style="color: #e67e22; font-weight: 600;">❌ Unreachable - upload skipped</div>
      </div>
    </div>

    <div class="section">
      <h2>💾 Backup Sizes</h2>
      <div class="sizes">
        <div class="size-item">
          <span class="size-label">Database:</span>
          <span class="size-value">${db_size_fmt}</span>
        </div>
        <div class="size-item">
          <span class="size-label">Application:</span>
          <span class="size-value">${app_size_fmt}</span>
        </div>
        <div class="size-item">
          <span class="size-label">System State:</span>
          <span class="size-value">${sys_size_fmt}</span>
        </div>
        <div class="size-item">
          <span class="size-label"><strong>Total:</strong></span>
          <span class="size-value"><strong>${total_size_fmt}</strong></span>
        </div>
      </div>
    </div>

    <div class="action-required">
      <h3>🔧 ACTION REQUIRED</h3>
      <p><strong>Root Cause:</strong> Synology NAS (fs-cloud1977.synology.me:50222) is unreachable.</p>
      <p><strong>Impact:</strong> Backups are stored locally only. Not protected against server failure.</p>
      <p><strong>Next Steps:</strong></p>
      <ol>
        <li>Check Synology NAS status (powered on, network connectivity)</li>
        <li>Verify SSH service running on port 50222</li>
        <li>Check firewall rules and DynDNS configuration</li>
        <li>Once NAS is restored, next scheduled backup will resume offsite replication</li>
      </ol>
      <p><strong>Documentation:</strong> See <code>BACKUP_FAILURE_RCA_2025-11-07.md</code> for detailed analysis</p>
    </div>
  </div>

  <div class="footer">
    <p>AskPro AI Backup System - Automated Backup Monitoring</p>
    <p>Server: $(hostname) | Generated: $(date -Iseconds)</p>
  </div>
</div>
</body>
</html>
EOF
}

# Function: Generate Failure E-Mail
generate_failure_email() {
    local subject="❌ Backup FAILED: ${TIER} - ${ERROR_STEP} ($(get_local_time "$TIMESTAMP"))"
    local local_time=$(get_local_time "$TIMESTAMP")
    local duration_fmt=$(format_duration "$DURATION")

    # Get last 200 lines of error log
    local error_tail=""
    if [ -f "$ERROR_LOG" ]; then
        error_tail=$(tail -200 "$ERROR_LOG" | sed 's/&/\&amp;/g; s/</\&lt;/g; s/>/\&gt;/g')
    fi

    cat <<EOF
From: ${FROM_NAME} <${FROM_ADDRESS}>
To: ${RECIPIENTS}
Subject: ${subject}
MIME-Version: 1.0
Content-Type: multipart/alternative; boundary="BOUNDARY_FAIL"

--BOUNDARY_FAIL
Content-Type: text/html; charset=UTF-8
Content-Transfer-Encoding: 8bit

<!DOCTYPE html>
<html>
<head>
<style>
body { font-family: 'Segoe UI', Arial, sans-serif; background-color: #f5f5f5; margin: 0; padding: 20px; }
.container { max-width: 800px; margin: 0 auto; background: white; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
.header { background: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%); color: white; padding: 30px; border-radius: 8px 8px 0 0; }
.header h1 { margin: 0; font-size: 24px; }
.header .status { font-size: 48px; margin: 10px 0; }
.content { padding: 30px; }
.section { margin-bottom: 25px; }
.section h2 { color: #333; font-size: 18px; margin: 0 0 15px 0; border-bottom: 2px solid #e74c3c; padding-bottom: 5px; }
.info-grid { display: grid; grid-template-columns: 150px 1fr; gap: 10px; }
.info-label { font-weight: 600; color: #666; }
.info-value { color: #333; font-family: 'Courier New', monospace; }
.error-box { background: #fff5f5; border-left: 4px solid #e74c3c; padding: 15px; border-radius: 4px; }
.error-step { font-weight: 600; color: #e74c3c; font-size: 16px; margin-bottom: 10px; }
.log-tail { background: #282c34; color: #abb2bf; padding: 15px; border-radius: 6px; font-family: 'Courier New', monospace; font-size: 12px; max-height: 400px; overflow-y: auto; white-space: pre-wrap; word-wrap: break-word; }
.actions { background: #e8f4fd; border-left: 4px solid #3498db; padding: 15px; border-radius: 4px; }
.actions h3 { margin: 0 0 10px 0; color: #2c3e50; font-size: 16px; }
.actions ul { margin: 0; padding-left: 20px; }
.actions li { margin: 5px 0; color: #34495e; }
.footer { background: #f8f9fa; padding: 20px 30px; border-radius: 0 0 8px 8px; text-align: center; color: #666; font-size: 13px; }
</style>
</head>
<body>
<div class="container">
  <div class="header">
    <h1>AskPro AI Backup System</h1>
    <div class="status">❌ FAILURE</div>
    <div>Backup failed - immediate attention required</div>
  </div>

  <div class="content">
    <div class="section">
      <h2>⚠️ Failure Details</h2>
      <div class="info-grid">
        <div class="info-label">Tier:</div>
        <div class="info-value">${TIER}</div>

        <div class="info-label">Timestamp:</div>
        <div class="info-value">${local_time}</div>

        <div class="info-label">Duration:</div>
        <div class="info-value">${duration_fmt}</div>

        <div class="info-label">Failed Step:</div>
        <div class="info-value" style="color: #e74c3c; font-weight: 600;">${ERROR_STEP}</div>
      </div>
    </div>

    <div class="section">
      <h2>📋 Recommended Actions</h2>
      <div class="actions">
        <ul>
          $(generate_recommended_actions "$ERROR_STEP")
        </ul>
      </div>
    </div>

    <div class="section">
      <h2>📜 Log Tail (Last 200 Lines)</h2>
      <div class="log-tail">${error_tail}</div>
    </div>

    $(if [ -n "$RUN_URL" ] && [ "$RUN_URL" != "local" ]; then
      echo "<div class=\"section\">"
      echo "  <h2>🔗 Run Details</h2>"
      echo "  <div class=\"info-grid\">"
      echo "    <div class=\"info-label\">Run URL:</div>"
      echo "    <div class=\"info-value\"><a href=\"${RUN_URL}\">${RUN_URL}</a></div>"
      echo "  </div>"
      echo "</div>"
    fi)
  </div>

  <div class="footer">
    🚨 A GitHub Issue has been automatically created<br>
    Generated by AskPro AI Backup System on $(hostname)<br>
    For manual retry: <code>sudo /var/www/api-gateway/scripts/backup-run.sh</code>
  </div>
</div>
</body>
</html>

--BOUNDARY_FAIL
Content-Type: text/plain; charset=UTF-8
Content-Transfer-Encoding: 8bit

AskPro AI Backup - FAILURE

Tier: ${TIER}
Timestamp: ${local_time}
Duration: ${duration_fmt}
Failed Step: ${ERROR_STEP}

Recommended Actions:
$(generate_recommended_actions "$ERROR_STEP" | sed 's/<li>//g; s/<\/li>//g')

Log Tail (Last 200 Lines):
$(if [ -f "$ERROR_LOG" ]; then tail -200 "$ERROR_LOG"; else echo "No log file available"; fi)

$(if [ -n "$RUN_URL" ] && [ "$RUN_URL" != "local" ]; then echo "Run URL: ${RUN_URL}"; fi)

--BOUNDARY_FAIL--
EOF
}

# Function: Generate recommended actions based on failure step
generate_recommended_actions() {
    local step="$1"
    case "$step" in
        "preflight_disk_space")
            echo "<li>Check disk usage: <code>df -h /</code></li>"
            echo "<li>Clean up old backups: <code>sudo /var/www/api-gateway/scripts/retention-cleanup.sh</code></li>"
            echo "<li>Remove temp files: <code>rm -rf /var/backups/askproai/tmp/*</code></li>"
            ;;
        "preflight_synology")
            echo "<li>Test SSH connection: <code>ssh -i /root/.ssh/synology_backup_key -p 50222 AskProAI@fs-cloud1977.synology.me echo OK</code></li>"
            echo "<li>Check Synology DSM is running and accessible</li>"
            echo "<li>Verify IP not auto-blocked (Check Synology Security settings)</li>"
            ;;
        "database_backup")
            echo "<li>Check MariaDB service: <code>systemctl status mariadb</code></li>"
            echo "<li>Verify MySQL credentials in <code>/root/.my.cnf</code></li>"
            echo "<li>Check database permissions: <code>mysql -e \"SHOW GRANTS FOR 'askproai_user'@'localhost';\"</code></li>"
            ;;
        "application_backup")
            echo "<li>Check file permissions in <code>/var/www/api-gateway</code></li>"
            echo "<li>Verify disk space for temporary archive creation</li>"
            echo "<li>Check for locked files: <code>lsof +D /var/www/api-gateway</code></li>"
            ;;
        "system_state_backup")
            echo "<li>Run system-state backup manually: <code>sudo /var/www/api-gateway/scripts/backup-system-state.sh</code></li>"
            echo "<li>Check permissions on /etc/ directories</li>"
            ;;
        "upload_synology")
            echo "<li>Test upload manually: <code>echo test | ssh -i /root/.ssh/synology_backup_key -p 50222 AskProAI@fs-cloud1977.synology.me \"cat > /tmp/test.txt\"</code></li>"
            echo "<li>Check network connectivity to Synology</li>"
            echo "<li>Verify Synology disk space: <code>ssh -i /root/.ssh/synology_backup_key -p 50222 AskProAI@fs-cloud1977.synology.me \"df -h /volume1\"</code></li>"
            ;;
        "checksum_mismatch")
            echo "<li>Re-run backup immediately (corruption likely in transit)</li>"
            echo "<li>Check network stability</li>"
            echo "<li>Verify Synology disk health in DSM (Storage Manager)</li>"
            ;;
        *)
            echo "<li>Check full logs: <code>tail -200 /var/log/backup-run.log</code></li>"
            echo "<li>Re-run backup manually: <code>sudo /var/www/api-gateway/scripts/backup-run.sh</code></li>"
            echo "<li>Contact system administrator if issue persists</li>"
            ;;
    esac
}

# Function: Create GitHub Issue on failure
create_github_issue() {
    if ! command -v gh &> /dev/null; then
        echo "Warning: gh CLI not available, skipping GitHub Issue creation" >> /var/log/backup-alerts.log
        return 1
    fi

    local issue_title="🚨 Backup Failure: ${TIER} - ${ERROR_STEP}"
    local issue_body=$(cat <<EOF
## Backup Failure Report

**Tier**: ${TIER}
**Timestamp**: $(get_local_time "$TIMESTAMP")
**Duration**: $(format_duration "$DURATION")
**Failed Step**: ${ERROR_STEP}

## Recommended Actions

$(generate_recommended_actions "$ERROR_STEP" | sed 's/<li>/- /g; s/<\/li>//g; s/<code>/`/g; s/<\/code>/`/g')

## Log Tail (Last 50 Lines)

\`\`\`
$(if [ -f "$ERROR_LOG" ]; then tail -50 "$ERROR_LOG"; else echo "No log file available"; fi)
\`\`\`

$(if [ -n "$RUN_URL" ] && [ "$RUN_URL" != "local" ]; then echo "**Run URL**: ${RUN_URL}"; fi)

---
*Automatically generated by AskPro AI Backup System*
EOF
)

    gh issue create \
        --title "$issue_title" \
        --body "$issue_body" \
        --label "backup-failure,critical" \
        --assignee fabian 2>&1 | tee -a /var/log/backup-alerts.log
}

# Main execution
main() {
    local email_file="/tmp/backup-notification-$$.eml"

    # Generate appropriate email
    if [ "$STATUS" = "success" ]; then
        generate_success_email > "$email_file"
    elif [ "$STATUS" = "failure" ]; then
        generate_failure_email > "$email_file"
        create_github_issue
    elif [ "$STATUS" = "warning" ]; then
        generate_warning_email > "$email_file"
    else
        echo "Error: Unknown status: $STATUS" >&2
        exit 1
    fi

    # Send email via msmtp
    if msmtp -t < "$email_file"; then
        echo "[$(date -Iseconds)] Email sent successfully to: $RECIPIENTS" >> /var/log/backup-alerts.log
    else
        echo "[$(date -Iseconds)] Failed to send email to: $RECIPIENTS" >> /var/log/backup-alerts.log
        exit 1
    fi

    # Cleanup
    rm -f "$email_file"

    exit 0
}

# Run if executed directly
if [ "${BASH_SOURCE[0]}" = "${0}" ]; then
    main "$@"
fi

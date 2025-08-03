# CLAUDE.md Optimierung - Migration Plan

## 🎯 Ziel: State-of-the-Art Dokumentation unter 40k Zeichen

### 1. **Neue Struktur**

```
CLAUDE.md (< 10k chars)          # Hauptdatei - nur essentials
├── docs/
│   ├── QUICK_START.md          # Onboarding & erste Schritte
│   ├── COMMANDS.md             # Alle Commands zentral
│   ├── TROUBLESHOOTING.md      # Aktuelle Issues & Lösungen
│   │
│   ├── integrations/           # Integration-spezifisch
│   │   ├── RETELL.md
│   │   ├── CALCOM.md
│   │   ├── MCP_SERVERS.md
│   │   └── SUBAGENTS.md
│   │
│   ├── architecture/           # System-Architektur
│   │   ├── OVERVIEW.md
│   │   ├── MULTI_TENANCY.md
│   │   └── SERVICES.md
│   │
│   └── archive/               # Historische Daten
│       ├── RESOLVED_ISSUES_2025.md
│       ├── LEGACY_BLOCKERS.md
│       └── OLD_WORKFLOWS.md
```

### 2. **Ultrathink Prinzipien**

#### A. Progressive Disclosure
```markdown
# Statt langer Erklärungen:
## Database
Access: `mysql -u askproai_user -p'lkZ57Dju9EDjrMxn' askproai_db`
[Details →](./docs/DATABASE.md)

# Kurz, mit Link für mehr
```

#### B. Smart Folding
```markdown
<details>
<summary>🔧 Daily Commands (click to expand)</summary>

Command list here...

</details>
```

#### C. Context-Aware Sections
```yaml
# YAML für schnelles Scannen
Current Sprint:
  Focus: Admin Panel Navigation Fix
  Issues: [#479, #480]
  Deadline: 2025-08-05
  
Tech Stack:
  Backend: Laravel 10
  Admin: Filament 3
  AI: Retell.ai
  Calendar: Cal.com
```

### 3. **Migration Steps**

```bash
# 1. Backup current
cp CLAUDE.md CLAUDE_BACKUP_$(date +%Y%m%d).md

# 2. Extract sections
mkdir -p docs/{integrations,architecture,archive}

# 3. Move content
# - Alte Blocker → archive/
# - MCP Details → integrations/
# - Commands → COMMANDS.md

# 4. Implement new CLAUDE.md
mv CLAUDE_OPTIMIZED.md CLAUDE.md

# 5. Create index files
echo "# Documentation Index" > docs/README.md
```

### 4. **Automatische Optimierung**

```bash
# Check size script
cat > check-claude-size.sh << 'EOF'
#!/bin/bash
SIZE=$(wc -c < CLAUDE.md)
LIMIT=40000

if [ $SIZE -gt $LIMIT ]; then
    echo "⚠️  CLAUDE.md is too large: $SIZE chars (limit: $LIMIT)"
    echo "Consider moving sections to docs/"
else
    echo "✅ CLAUDE.md size OK: $SIZE chars"
fi
EOF

chmod +x check-claude-size.sh
```

### 5. **Best Practices**

#### DO:
- ✅ Use collapsible sections
- ✅ Link to detailed docs
- ✅ Keep daily essentials at top
- ✅ Use YAML for structured data
- ✅ Archive resolved issues

#### DON'T:
- ❌ Duplicate information
- ❌ Keep outdated blockers
- ❌ Long explanations in main file
- ❌ Historical data in CLAUDE.md

### 6. **Template für neue Issues**

```markdown
## 🚨 Active Issue: [Title]
**Issue**: #[number]
**Status**: 🔴 Blocking
**Quick Fix**: `command here`
[Full Details →](./docs/issues/ISSUE_[number].md)
```

### 7. **Maintenance Workflow**

```yaml
Weekly:
  - Archive resolved issues
  - Update command shortcuts
  - Check file size
  
Monthly:
  - Review architecture docs
  - Clean up archive
  - Update integration docs
```

## 🚀 Implementation

```bash
# Start migration
./migrate-claude-docs.sh

# Verify
./check-claude-size.sh
```

---

**Result**: Clean, fast, under 40k chars, state-of-the-art documentation!
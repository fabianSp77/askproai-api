#!/bin/bash

# CLAUDE.md Migration Script - Ultrathink Optimization
# Reduces CLAUDE.md from 50k to under 40k chars

echo "🚀 Starting CLAUDE.md Ultrathink Optimization..."

# 1. Create backup
BACKUP_FILE="CLAUDE_BACKUP_$(date +%Y%m%d_%H%M%S).md"
cp CLAUDE.md "$BACKUP_FILE"
echo "✅ Backup created: $BACKUP_FILE"

# 2. Create directory structure
echo "📁 Creating optimized directory structure..."
mkdir -p docs/{quick-refs,integrations,architecture,workflows,archive/2025}

# 3. Extract sections to separate files
echo "📄 Extracting sections..."

# Extract old blockers (2025-06 and 2025-07)
sed -n '/### 🆘 ALTE BLOCKER/,/### 🚨 KRITISCH: Retell.ai Integration Status/p' CLAUDE.md > docs/archive/2025/OLD_BLOCKERS.md

# Extract MCP server details
sed -n '/## MCP-Server Übersicht/,/## Essential Commands/p' CLAUDE.md > docs/integrations/MCP_SERVERS.md

# Extract subagents section
sed -n '/## 🧠 Subagenten Framework/,/## Project Overview/p' CLAUDE.md > docs/integrations/SUBAGENTS.md

# Extract architecture details
sed -n '/## Architecture Overview/,/## Critical Implementation Details/p' CLAUDE.md > docs/architecture/SYSTEM_ARCHITECTURE.md

# Extract troubleshooting
sed -n '/## Debugging & Troubleshooting/,/## Common Development Tasks/p' CLAUDE.md > docs/TROUBLESHOOTING_GUIDE.md

# Extract performance section
sed -n '/## Performance Considerations/,/## Business Logic/p' CLAUDE.md > docs/architecture/PERFORMANCE.md

# Extract historical information
sed -n '/## 📋 Archiv & Historie/,$p' CLAUDE.md > docs/archive/2025/HISTORICAL_INFO.md

# 4. Create quick reference files
echo "📝 Creating quick reference files..."

cat > docs/quick-refs/ESSENTIAL_COMMANDS.md << 'EOF'
# Essential Commands Quick Reference

## Daily Commands
```bash
# Database
mysql -u askproai_user -p'lkZ57Dju9EDjrMxn' askproai_db

# Cache Management
php artisan optimize:clear
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

# Build & Deploy
npm run build
php artisan filament:clear-cached-components

# Queue & Monitoring
php artisan horizon
php artisan horizon:status
tail -f storage/logs/laravel.log
```

## Testing
```bash
php artisan test
php artisan test --filter=FeatureName
php artisan test --parallel
```

## MCP Discovery
```bash
php artisan mcp:discover "task description"
php artisan mcp:health
```
EOF

cat > docs/quick-refs/CURRENT_ISSUES.md << 'EOF'
# Current Active Issues

## 🔴 Admin Panel Navigation (#479)
- **Problem**: Only emergency menu clickable
- **Test**: Check console for pointer-events issues
- **Fix**: In progress

## 🔴 TestSprite Integration (#480)
- **Problem**: MCP not configured
- **Action**: Add to claude_desktop_config.json
- **API Key**: sk-user-uCnLDtTopQf3D_bd_S-vQhpUATq067TWS2HWydNlrU8Fzf9L3Nf91AnxispsF9vw_rg1LU7U7BhYfFiln7GEdEVSmO5h0EEOiRzzKEQHHBFhndIiospHfL2mlDw-iiVAhUc
EOF

# 5. Create the new optimized CLAUDE.md
echo "✨ Creating optimized CLAUDE.md..."
mv CLAUDE_OPTIMIZED.md CLAUDE.md

# 6. Create documentation index
cat > docs/README.md << 'EOF'
# AskProAI Documentation Index

## 📚 Quick Access
- [Essential Commands](./quick-refs/ESSENTIAL_COMMANDS.md)
- [Current Issues](./quick-refs/CURRENT_ISSUES.md)
- [Troubleshooting](./TROUBLESHOOTING_GUIDE.md)

## 🏗️ Architecture
- [System Overview](./architecture/SYSTEM_ARCHITECTURE.md)
- [Performance Guide](./architecture/PERFORMANCE.md)

## 🔌 Integrations
- [MCP Servers](./integrations/MCP_SERVERS.md)
- [Subagents](./integrations/SUBAGENTS.md)

## 📦 Archive
- [2025 Historical](./archive/2025/)
EOF

# 7. Check file sizes
echo -e "\n📊 File Size Report:"
echo "Original CLAUDE.md: $(wc -c < "$BACKUP_FILE") chars"
echo "New CLAUDE.md: $(wc -c < CLAUDE.md) chars"
echo "Reduction: $(( $(wc -c < "$BACKUP_FILE") - $(wc -c < CLAUDE.md) )) chars"

# 8. Verify optimization
if [ $(wc -c < CLAUDE.md) -lt 40000 ]; then
    echo -e "\n✅ SUCCESS: CLAUDE.md is now under 40k chars!"
else
    echo -e "\n⚠️  WARNING: CLAUDE.md is still over 40k chars. Further optimization needed."
fi

echo -e "\n🎉 Migration complete! Old file backed up as: $BACKUP_FILE"
echo "📚 Extended docs available in: ./docs/"

# 9. Create size check script
cat > check-claude-size.sh << 'EOF'
#!/bin/bash
SIZE=$(wc -c < CLAUDE.md 2>/dev/null || echo 0)
LIMIT=40000

echo "📏 CLAUDE.md Size Check"
echo "Current: $SIZE chars"
echo "Limit: $LIMIT chars"

if [ $SIZE -gt $LIMIT ]; then
    echo "⚠️  OVER LIMIT by $(($SIZE - $LIMIT)) chars"
    exit 1
else
    echo "✅ OK - $(($LIMIT - $SIZE)) chars remaining"
fi
EOF

chmod +x check-claude-size.sh

echo -e "\n💡 Tip: Run './check-claude-size.sh' to monitor CLAUDE.md size"
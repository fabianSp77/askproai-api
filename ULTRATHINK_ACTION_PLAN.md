# ULTRATHINK ACTION PLAN 🚀

## Priority 1: Database Optimization (Week 1)
```bash
# Analyze table usage
mysql -u askproai_user -p'lkZ57Dju9EDjrMxn' askproai_db -e "
SELECT table_name, table_rows, data_length/1024/1024 AS data_mb 
FROM information_schema.tables 
WHERE table_schema='askproai_db' 
ORDER BY data_length DESC LIMIT 20;"

# Target: 119 tables → 25 tables
```

### Consolidation Targets:
- [ ] Merge 10 MCP-specific tables → `mcp_operations`
- [ ] Combine 8 webhook tables → `webhook_events`
- [ ] Unify 6 import tables → `data_imports`
- [ ] Merge duplicate user/company tables

## Priority 2: Storage Cleanup (Immediate)
```bash
# Find large files
find /var/www/api-gateway -type f -size +10M -exec ls -lh {} \; | sort -k5 -hr

# Clean old logs
find storage/logs -name "*.log" -mtime +7 -delete
find storage/debugbar -type f -mtime +1 -delete

# Remove test artifacts
rm -rf storage/archived-*
rm -rf app.old resources.old database.old routes.old
```

## Priority 3: Performance Quick Wins
```bash
# Add missing indexes
mysql -u askproai_user -p'lkZ57Dju9EDjrMxn' askproai_db << 'EOF'
ALTER TABLE calls ADD INDEX idx_company_created (company_id, created_at);
ALTER TABLE appointments ADD INDEX idx_branch_date (branch_id, appointment_date);
ALTER TABLE webhook_calls ADD INDEX idx_status_created (status, created_at);
EOF

# Enable query cache
echo "query_cache_type = 1" >> /etc/mysql/conf.d/performance.cnf
echo "query_cache_size = 128M" >> /etc/mysql/conf.d/performance.cnf
service mysql restart
```

## Priority 4: Frontend Standardization
```bash
# Audit current usage
grep -r "import.*React" resources/js --include="*.jsx" --include="*.js" | wc -l
grep -r "Vue\." resources/js --include="*.js" --include="*.vue" | wc -l

# Decision: Migrate to React (based on team expertise)
npm install @vitejs/plugin-react
```

## Priority 5: Security Hardening
```bash
# Rotate all API keys
php artisan key:generate
./rotate-api-keys.sh

# Update dependencies
COMPOSER_ALLOW_SUPERUSER=1 composer update --no-dev
npm audit fix

# Remove debug routes
rm -f routes/debug-*.php routes/test-*.php
```

## Execution Timeline:
- **Today**: Storage cleanup + Performance indexes
- **Week 1**: Database consolidation
- **Week 2**: Frontend decision + Security audit
- **Week 3**: Monitoring setup
- **Week 4**: Documentation + Training

## Success Metrics:
- Database queries: <50ms average
- Storage usage: <2GB (from 12GB)
- Page load: <1s
- Zero security vulnerabilities

---
*Execute with precision. Measure everything. Ship fast.*
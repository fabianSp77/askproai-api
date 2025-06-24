# Database Schema Analysis Report

## Summary

Date: 2025-06-24
Database: askproai_db
Total Tables: 40
Total Pending Migrations: 63

## Key Findings

### 1. Pending Migrations
- **63 migrations are pending** - This is a significant number and indicates the database schema is out of sync with the codebase
- Several migrations are failing due to:
  - Tables already existing (circuit_breaker_metrics, webhook_logs, logs, phone_numbers, security_logs)
  - Index name length exceeding MySQL limits (64 characters)
  - Missing referenced tables (tenants table referenced by users)

### 2. Missing Critical Tables
The following important tables are missing and need to be created:
- agents (for Retell agent management)
- master_services (for service templates)
- branch_service_overrides (for branch-specific service customizations)
- critical_errors (for error tracking)
- unified_event_types (for unified calendar management)
- gdpr_requests (for GDPR compliance)
- callback_requests (for callback functionality)
- validation_results (for data validation)
- notifications (for user notifications)
- knowledge_documents/knowledge_categories (for knowledge base)

### 3. Missing Indexes
Several critical performance indexes are missing:
- Phone number lookup indexes (idx_phone_branch_lookup)
- Company-based filtering indexes for multi-tenancy
- Call history and status indexes
- Customer phone/email lookup indexes
- Service availability indexes

### 4. Foreign Key Issues
- **Missing Table Reference**: users.tenant_id references non-existent tenants table
- This needs to be fixed or the foreign key should be removed

### 5. Schema Inconsistencies
- Multiple columns storing the same type of data (e.g., multiple duration fields in calls table)
- Redundant indexes on the same columns
- Mixed naming conventions for indexes

### 6. Performance Concerns
- No slow query log enabled
- Large tables without proper indexing:
  - webhook_events (1.63 MB, 64 rows) - needs better indexing
  - calls table has too many JSON columns which could impact performance
- Multiple duplicate indexes wasting storage

### 7. Duplicate Indexes
Several columns have redundant indexes:
- api_call_logs.service (3 indexes)
- appointments.company_id (3 indexes)
- branch_event_types.branch_id (3 indexes)
- And many more...

## Recommendations

### Immediate Actions Required

1. **Fix Migration Issues**
   - Skip migrations for already existing tables
   - Fix index names that are too long
   - Remove or fix the tenants foreign key reference

2. **Run Critical Migrations**
   ```bash
   # Mark problematic migrations as run
   php fix-migrations.php
   
   # Run remaining migrations with shorter index names
   php artisan migrate --force
   ```

3. **Add Missing Performance Indexes**
   - Run the pending performance optimization migrations
   - These will significantly improve query performance

4. **Clean Up Duplicate Indexes**
   - Review and remove redundant indexes to save storage
   - Keep only the most comprehensive indexes

### Medium-term Improvements

1. **Enable Slow Query Log**
   ```sql
   SET GLOBAL slow_query_log = 'ON';
   SET GLOBAL long_query_time = 1;
   ```

2. **Optimize Table Structure**
   - Consider moving JSON columns to separate tables for better performance
   - Normalize redundant data fields

3. **Add Missing Foreign Keys**
   - Ensure all relationships have proper foreign key constraints
   - This will improve data integrity

### Long-term Considerations

1. **Schema Refactoring**
   - Consolidate similar tables (e.g., various event type tables)
   - Implement proper naming conventions
   - Consider partitioning large tables

2. **Performance Monitoring**
   - Set up regular ANALYZE TABLE operations
   - Monitor index usage and query patterns
   - Implement query caching where appropriate

## Database Health Score: 6/10

The database is functional but needs significant maintenance:
- ✅ Core tables exist and have basic indexes
- ✅ Foreign key constraints are mostly in place
- ⚠️ Many pending migrations indicate schema drift
- ⚠️ Missing critical performance indexes
- ❌ Duplicate indexes wasting resources
- ❌ No performance monitoring enabled

## Action Items

1. **Critical (Do Today)**
   - Fix and run pending migrations
   - Add missing performance indexes
   - Fix the tenants table reference issue

2. **Important (This Week)**
   - Remove duplicate indexes
   - Enable slow query logging
   - Add missing foreign keys

3. **Nice to Have (This Month)**
   - Refactor JSON columns
   - Implement monitoring
   - Clean up naming conventions
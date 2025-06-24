# Troubleshooting Guide

Generated on: 2025-06-23 16:14:17

## Common Issues & Solutions

### 🔴 Phone Number Not Recognized

**Symptoms**:
- Customer calls but system doesn't recognize the phone number
- Error: "Phone number not found"

**Solution**:
```sql
-- Check phone number assignment
SELECT pn.*, b.name as branch_name 
FROM phone_numbers pn
LEFT JOIN branches b ON pn.branch_id = b.id
WHERE pn.phone_number = '+49 30 837 93 369';
```

### 🔴 Booking Creation Fails

**Symptoms**:
- Webhook received but appointment not created
- Error in logs: "Booking failed"

**Diagnostic Steps**:
```bash
# Check webhook logs
tail -f storage/logs/laravel.log | grep -i webhook

# Check Horizon queue
php artisan horizon:status

# Check failed jobs
php artisan queue:failed
```

### 🔴 Slow API Response Times

**Symptoms**:
- API calls taking > 1 second
- Timeout errors

**Quick Fixes**:
```bash
# Clear all caches
php artisan optimize:clear

# Restart queue workers
php artisan horizon:terminate
php artisan horizon

# Check slow queries
mysql -u root -p -e "SELECT * FROM mysql.slow_log ORDER BY query_time DESC LIMIT 10;"
```


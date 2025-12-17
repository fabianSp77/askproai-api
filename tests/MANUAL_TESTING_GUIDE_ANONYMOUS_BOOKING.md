# Manual Testing Guide: Anonymous Booking

**Quick Reference**: Curl commands for manual API testing

---

## Prerequisites

```bash
# Set base URL
export API_BASE="https://api.askpro.ai"

# Or for local testing
export API_BASE="http://localhost:8000"
```

---

## Test Scenarios

### 1. Anonymous Booking - No Email (H1)

**Expected**: Customer created with `email = NULL`

```bash
curl -X POST "${API_BASE}/api/retell/v17/book-appointment" \
  -H "Content-Type: application/json" \
  -d '{
    "call_id": "test_anonymous_no_email_001",
    "name": "Hans Müller",
    "email": null,
    "service_name": "Herrenhaarschnitt",
    "date": "morgen",
    "time": "10:00"
  }' | jq .
```

**Verification**:
```sql
SELECT id, name, email, phone, source
FROM customers
WHERE name = 'Hans Müller'
ORDER BY created_at DESC
LIMIT 1;
```

---

### 2. Anonymous Booking - With Email (H3)

**Expected**: Customer created with email stored

```bash
curl -X POST "${API_BASE}/api/retell/v17/book-appointment" \
  -H "Content-Type: application/json" \
  -d '{
    "call_id": "test_anonymous_with_email_001",
    "name": "Anna Weber",
    "email": "anna.weber@test.de",
    "service_name": "Damenhaarschnitt",
    "date": "heute",
    "time": "14:30"
  }' | jq .
```

---

### 3. Regular Booking - No Email (H4)

**Expected**: Customer created with real phone, `email = NULL`

```bash
curl -X POST "${API_BASE}/api/retell/v17/book-appointment" \
  -H "Content-Type: application/json" \
  -d '{
    "call_id": "test_regular_no_email_001",
    "from_number": "+4915112345678",
    "name": "Petra Klein",
    "email": null,
    "service_name": "Bartpflege",
    "date": "2025-11-20",
    "time": "16:00"
  }' | jq .
```

---

### 4. Duplicate Anonymous Name Test (E1)

**Expected**: Two SEPARATE customers created (security rule)

```bash
# First call
curl -X POST "${API_BASE}/api/retell/v17/book-appointment" \
  -H "Content-Type: application/json" \
  -d '{
    "call_id": "test_duplicate_001",
    "name": "Max Müller",
    "email": null,
    "service_name": "Herrenhaarschnitt",
    "date": "morgen",
    "time": "09:00"
  }' | jq .

# Second call (same name)
curl -X POST "${API_BASE}/api/retell/v17/book-appointment" \
  -H "Content-Type: application/json" \
  -d '{
    "call_id": "test_duplicate_002",
    "name": "Max Müller",
    "email": null,
    "service_name": "Herrenhaarschnitt",
    "date": "morgen",
    "time": "10:00"
  }' | jq .
```

**Verification**:
```sql
-- Should return 2 rows (2 different customers with same name)
SELECT id, name, email, phone, source, created_at
FROM customers
WHERE name = 'Max Müller'
ORDER BY created_at DESC;
```

---

### 5. Empty String Email Test (E2)

**Expected**: Empty string converted to NULL

```bash
curl -X POST "${API_BASE}/api/retell/v17/book-appointment" \
  -H "Content-Type: application/json" \
  -d '{
    "call_id": "test_empty_email_001",
    "name": "Test Empty Email",
    "email": "",
    "service_name": "Herrenhaarschnitt",
    "date": "morgen",
    "time": "11:00"
  }' | jq .
```

---

### 6. Special Characters in Name (E7)

**Expected**: UTF-8 characters stored correctly

```bash
curl -X POST "${API_BASE}/api/retell/v17/book-appointment" \
  -H "Content-Type: application/json" \
  -d '{
    "call_id": "test_special_chars_001",
    "name": "O'\''Brien-Müller",
    "email": null,
    "service_name": "Herrenhaarschnitt",
    "date": "morgen",
    "time": "12:00"
  }' | jq .
```

---

### 7. Service Not Found Error (ER1)

**Expected**: Error response with `service_not_found`

```bash
curl -X POST "${API_BASE}/api/retell/v17/book-appointment" \
  -H "Content-Type: application/json" \
  -d '{
    "call_id": "test_error_service_001",
    "name": "Test User",
    "email": null,
    "service_name": "NonExistentService",
    "date": "morgen",
    "time": "10:00"
  }' | jq .
```

**Expected Response**:
```json
{
  "success": false,
  "error": "service_not_found",
  "message": "Dieser Service ist leider nicht verfügbar"
}
```

---

### 8. Missing Required Fields (ER4)

**Expected**: Validation error

```bash
curl -X POST "${API_BASE}/api/retell/v17/book-appointment" \
  -H "Content-Type: application/json" \
  -d '{
    "call_id": "test_missing_fields_001"
  }' | jq .
```

---

## Stress Test: Multiple Anonymous Bookings (E8)

**Test**: 10 concurrent anonymous bookings with NULL email

```bash
#!/bin/bash
# Save as test_concurrent_anonymous.sh

API_BASE="${API_BASE:-http://localhost:8000}"

echo "Starting concurrent anonymous booking test..."

for i in {1..10}; do
  {
    curl -X POST "${API_BASE}/api/retell/v17/book-appointment" \
      -H "Content-Type: application/json" \
      -d "{
        \"call_id\": \"test_concurrent_$(date +%s%N)_${i}\",
        \"name\": \"Concurrent User ${i}\",
        \"email\": null,
        \"service_name\": \"Herrenhaarschnitt\",
        \"date\": \"morgen\",
        \"time\": \"10:00\"
      }" &
  }
done

wait
echo "All requests completed!"
```

**Verification**:
```sql
-- Should return 10 rows (no UNIQUE constraint violations)
SELECT COUNT(*) as concurrent_null_emails
FROM customers
WHERE email IS NULL
  AND name LIKE 'Concurrent User%'
  AND created_at >= DATE_SUB(NOW(), INTERVAL 1 MINUTE);
```

---

## Database Verification Queries

### Check Anonymous Customers
```sql
SELECT
    id,
    name,
    email,
    phone,
    source,
    company_id,
    created_at
FROM customers
WHERE phone LIKE 'anonymous_%'
ORDER BY created_at DESC
LIMIT 20;
```

### Check NULL Email Count
```sql
-- Should return multiple rows (proves UNIQUE constraint allows multiple NULLs)
SELECT COUNT(*) as null_email_count
FROM customers
WHERE email IS NULL;
```

### Check for UNIQUE Constraint Violations
```sql
-- This query should work (no error)
SELECT email, COUNT(*) as count
FROM customers
WHERE email IS NULL
GROUP BY email;
```

### Check Appointments from Anonymous Customers
```sql
SELECT
    a.id as appointment_id,
    c.id as customer_id,
    c.name as customer_name,
    c.email as customer_email,
    c.phone as customer_phone,
    s.name as service_name,
    a.starts_at,
    a.status,
    a.created_at
FROM appointments a
JOIN customers c ON a.customer_id = c.id
JOIN services s ON a.service_id = s.id
WHERE c.phone LIKE 'anonymous_%'
ORDER BY a.created_at DESC
LIMIT 20;
```

### Check for Duplicate Anonymous Names (Security Test)
```sql
-- Should show multiple customers with same name but different IDs (expected behavior)
SELECT
    name,
    COUNT(*) as count,
    GROUP_CONCAT(id ORDER BY id) as customer_ids
FROM customers
WHERE phone LIKE 'anonymous_%'
GROUP BY name
HAVING COUNT(*) > 1;
```

---

## Success Criteria Checklist

### Functional Tests
- [ ] Anonymous booking without email succeeds (H1)
- [ ] Anonymous booking with email succeeds (H3)
- [ ] Regular booking without email succeeds (H4)
- [ ] Duplicate anonymous names create SEPARATE customers (E1)
- [ ] Empty string email converts to NULL (E2)
- [ ] Special characters in names handled correctly (E7)

### Error Handling
- [ ] Service not found returns proper error (ER1)
- [ ] Missing required fields return validation error (ER4)

### Database Validation
- [ ] `email` column stores NULL (not empty string)
- [ ] UNIQUE constraint allows multiple NULL values
- [ ] Placeholder phone follows pattern: `anonymous_[timestamp]_[hash]`
- [ ] `company_id` isolation maintained

### Security Tests
- [ ] Anonymous callers are isolated by name (S1)
- [ ] Cross-tenant isolation works (S4)
- [ ] Placeholder phones are unique (S5)

---

## Troubleshooting

### Issue: UNIQUE constraint violation on email

**Symptom**:
```
SQLSTATE[23000]: Integrity constraint violation: 1062 Duplicate entry '' for key 'customers_email_unique'
```

**Fix**:
```bash
# Run migration
php artisan migrate

# Or manually:
php artisan db:seed --class=FixCustomersEmailConstraintSeeder
```

### Issue: Customer not created (no error)

**Check**:
1. Call record exists?
   ```sql
   SELECT * FROM calls WHERE retell_call_id = 'test_call_id';
   ```

2. Company/branch context set?
   ```sql
   SELECT company_id, branch_id FROM calls WHERE retell_call_id = 'test_call_id';
   ```

3. Check logs:
   ```bash
   tail -f storage/logs/laravel.log | grep "anonymous"
   ```

---

## Related Documentation

- **Test Strategy**: `/tests/E2E_TEST_STRATEGY_ANONYMOUS_BOOKING.md`
- **PHPUnit Tests**: `/tests/Feature/AnonymousBookingTest.php`
- **Migration**: `/database/migrations/2025_11_11_231608_fix_customers_email_unique_constraint.php`
- **Resolver**: `/app/Services/Retell/AppointmentCustomerResolver.php`

---

**Last Updated**: 2025-11-18
**Test Environment**: Development / Staging

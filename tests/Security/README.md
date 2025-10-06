# PHASE B - Security Penetration Test Suite

Comprehensive security validation for PHASE A vulnerability fixes.

## Quick Start

### Run All Tests (Recommended)

```bash
cd /var/www/api-gateway/tests/Security
./run-all-security-tests.sh
```

This will:
- Execute all 10 attack scenarios
- Generate HTML and text reports
- Show pass/fail summary
- Create timestamped reports in `reports/` directory

### Run Individual Test Suites

**Shell-based HTTP/API Tests:**
```bash
./phase-b-penetration-tests.sh
```

**Tinker-based Model Layer Tests:**
```bash
php ../../artisan tinker < phase-b-tinker-attacks.php
```

## What Gets Tested

| # | Attack Scenario | CVSS | Category |
|---|----------------|------|----------|
| 1 | Cross-Tenant Data Access | 9.8 CRITICAL | Authorization Bypass |
| 2 | Admin Privilege Escalation | 8.8 HIGH | Privilege Escalation |
| 3 | Webhook Forgery | 9.3 CRITICAL | Authentication Bypass |
| 4 | User Enumeration | 5.3 MEDIUM | Information Disclosure |
| 5 | Cross-Company Booking | 8.1 HIGH | Authorization Bypass |
| 6 | SQL Injection | 9.8 CRITICAL | Injection |
| 7 | XSS Injection | 6.1 MEDIUM | Cross-Site Scripting |
| 8 | Policy Bypass | 8.8 HIGH | Authorization |
| 9 | CompanyScope Bypass | 9.1 CRITICAL | Authorization Bypass |
| 10 | Monitor Endpoint Access | 7.5 HIGH | Authentication Bypass |

## Files

- **phase-b-penetration-tests.sh**: Shell-based HTTP/API penetration tests
- **phase-b-tinker-attacks.php**: PHP model-layer security tests
- **run-all-security-tests.sh**: Automated test runner with reporting
- **PHASE-B-SECURITY-TEST-DOCUMENTATION.md**: Complete documentation with remediation guidance

## Security Controls Validated

✓ Multi-tenant data isolation (CompanyScope)
✓ Role-based access control (Spatie Permissions)
✓ Webhook signature verification
✓ Authorization policies (Laravel Gates)
✓ Mass assignment protection
✓ Input validation and sanitization
✓ SQL injection prevention
✓ XSS protection mechanisms
✓ Authentication middleware
✓ Observer-based business logic security

## Success Criteria

**All Tests Must Pass:**
- ✓ 0 CRITICAL vulnerabilities
- ✓ 0 HIGH severity issues
- ✓ Pass rate ≥ 90%

**Exit Codes:**
- `0`: All tests passed, system is secure
- `1`: Vulnerabilities detected, review required

## Reports

After running tests, view reports at:
```
tests/Security/reports/YYYYMMDD_HHMMSS/
├── security_test_report.txt   # Plain text report
└── security_test_report.html  # Interactive HTML report
```

## Documentation

For detailed information about each attack scenario, expected behaviors, and remediation guidance, see:

**[PHASE-B-SECURITY-TEST-DOCUMENTATION.md](PHASE-B-SECURITY-TEST-DOCUMENTATION.md)**

## Safety

All tests are designed to be safe for production environments:
- Uses test database (not production)
- No data destruction
- Automatic cleanup after execution
- Clear output showing PASS/FAIL for each scenario
- Detailed logging of all actions

## Troubleshooting

**Tests fail with database error:**
```bash
# Create test database
php artisan db:create testing
php artisan migrate --database=testing
```

**Permission denied:**
```bash
chmod +x *.sh
```

**View detailed logs:**
```bash
tail -f tests/Security/reports/*/security_test_report.txt
```

## Contact

Security Team: security@api-gateway.local
Documentation: /docs/security/

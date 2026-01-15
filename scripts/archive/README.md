# Archived Scripts

Scripts in this directory have been archived because they contain **hardcoded production database credentials** or other security risks.

## Why Scripts Are Archived

These scripts were one-time utilities that directly connected to the production database without using Laravel's environment configuration. This is dangerous because:

1. **No environment isolation** - Could accidentally run against production
2. **No safeguards** - Bypasses TestCase.php protection layers
3. **Hardcoded credentials** - Security risk if committed to version control

## Archived Scripts

### deduplicate-customers.php.archived
- **Original purpose**: One-time deduplication of customer email records
- **Risk**: Line 7 had hardcoded `askproai_db` connection
- **Archived**: 2026-01-15
- **Status**: Task completed, script no longer needed

## Running Similar Tasks Safely

If you need to run database maintenance tasks:

1. **Use Laravel Artisan commands** - They respect environment configuration
2. **Use Laravel Tinker** - `php artisan tinker` for one-off queries
3. **Create a proper migration** - For schema changes
4. **Create a proper seeder/command** - For data manipulation

Example safe pattern:
```php
// Use Laravel's DB facade - respects .env configuration
DB::table('customers')->where('email', $email)->first();
```

## TestCase.php Protection

The test suite has 3-layer protection against production database access:
1. Config cache check
2. Environment check
3. Database name blocklist (`askproai_db`, `askproai_staging`)

Scripts in this archive bypassed all these protections.

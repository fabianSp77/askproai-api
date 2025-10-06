# MySQL Syntax Guidelines - Development Standards

## 🎯 Purpose
Ensure all database queries and migrations are MySQL-compatible for production deployment.

---

## ✅ MySQL-Compatible Patterns (APPROVED)

### Laravel Query Builder (Always Safe)
```php
// ✅ SAFE - Laravel abstracts DB differences
DB::table('users')->where('active', true)->get();
Model::where('status', 'active')->first();
$query->join('table', 'table.id', '=', 'other.table_id');
```

### Migrations (Safe Patterns)
```php
// ✅ SAFE - Standard Laravel Schema
Schema::create('table_name', function (Blueprint $table) {
    $table->id();
    $table->string('name', 255);
    $table->text('description')->nullable();
    $table->json('metadata')->nullable();
    $table->enum('status', ['pending', 'active', 'completed']);
    $table->timestamp('created_at')->nullable();
    $table->timestamps();
    $table->softDeletes();

    // Foreign keys
    $table->foreignId('user_id')
          ->constrained('users')
          ->cascadeOnDelete();

    // Indexes
    $table->index(['status', 'created_at']);
    $table->unique(['email', 'deleted_at']);
});
```

### JSON Operations (Safe)
```php
// ✅ SAFE - Works on both MySQL and SQLite
$table->json('config')->nullable();

// Query JSON in code (Laravel handles syntax differences)
Model::whereJsonContains('config->features', 'notifications')->get();
Model::whereJsonLength('tags', '>', 0)->get();
```

### Date/Time Operations (Safe)
```php
// ✅ SAFE - Laravel Carbon abstracts differences
$table->timestamp('expires_at')->nullable();
$table->date('period_start');

// In queries
Model::whereDate('created_at', '2025-10-02')->get();
Model::whereBetween('created_at', [$start, $end])->get();
```

---

## ❌ SQLite-Specific Patterns (FORBIDDEN)

### Database Comments
```php
// ❌ FORBIDDEN - SQLite doesn't support, causes errors
DB::statement("COMMENT ON TABLE users IS 'User accounts';");
DB::statement("COMMENT ON COLUMN users.email IS 'User email';");

// ✅ ALTERNATIVE - Use PHPDoc in migration
/**
 * Table: users
 * Purpose: User account management
 * Notes: Email must be unique per tenant
 */
Schema::create('users', function (Blueprint $table) {
    // ... columns with inline ->comment()
    $table->string('email')->comment('User email address');
});
```

### Auto-increment Control
```php
// ❌ FORBIDDEN - SQLite specific
$table->id()->startingValue(1000);
$table->autoIncrement = false;

// ✅ ALTERNATIVE - Let DB handle auto-increment
$table->id(); // Standard Laravel, works everywhere
```

### PRAGMA Statements
```php
// ❌ FORBIDDEN - SQLite only
DB::statement('PRAGMA foreign_keys = ON');
DB::statement('PRAGMA journal_mode = WAL');

// ✅ ALTERNATIVE - Foreign keys enabled by default in MySQL
// No action needed for production
```

### Boolean Storage
```php
// ⚠️ CAUTION - Different storage
// SQLite: Stores as 0/1 integer
// MySQL: Has native TINYINT(1) for boolean

// ✅ SAFE - Laravel casts handle this
$table->boolean('is_active')->default(true);
// Eloquent model:
protected $casts = ['is_active' => 'boolean'];
```

---

## 🔍 MySQL-Specific Features (ALLOWED)

### Full-Text Search
```php
// ✅ ALLOWED - MySQL-specific, but optional
$table->fullText(['title', 'description']);

// Query with full-text
Model::whereRaw('MATCH(title, description) AGAINST(? IN BOOLEAN MODE)', ['search term'])->get();

// ⚠️ NOTE: Must have fallback for testing (SQLite doesn't support)
if (DB::connection()->getDriverName() === 'mysql') {
    // Full-text search
} else {
    // LIKE-based search for testing
}
```

### Spatial Data Types (If Needed)
```php
// ✅ ALLOWED - MySQL supports, SQLite doesn't
$table->point('location')->nullable();
$table->geometry('area')->nullable();

// ⚠️ NOTE: Requires mysql driver, document limitations
```

### JSON Path Expressions
```php
// ✅ ALLOWED - MySQL 5.7+
Model::whereRaw("JSON_EXTRACT(config, '$.enabled') = true")->get();

// ⚠️ BETTER - Use Laravel's JSON operators (cross-DB)
Model::where('config->enabled', true)->get();
```

---

## 🧪 Validation Checklist (Before Commit)

### For Every Migration
```bash
# 1. Syntax check
php -l database/migrations/YYYY_MM_DD_*.php

# 2. SQLite test (development)
DB_CONNECTION=sqlite DB_DATABASE=testing.sqlite php artisan migrate:fresh --path=database/migrations/YYYY_MM_DD_*.php --force

# 3. Check for forbidden patterns
grep -r "COMMENT ON" database/migrations/
grep -r "PRAGMA" database/migrations/
grep -r "autoIncrement.*false" database/migrations/

# 4. MySQL pretend (show SQL without executing)
php artisan migrate --pretend --path=database/migrations/YYYY_MM_DD_*.php

# 5. Document any MySQL-specific features used
echo "Migration: YYYY_MM_DD_*.php" >> docs/MYSQL_FEATURES_USED.md
echo "Feature: [full-text/spatial/etc]" >> docs/MYSQL_FEATURES_USED.md
```

### For Every Service/Model Query
```bash
# 1. Check for raw SQL
grep -r "DB::raw\|whereRaw\|selectRaw" app/Services/
grep -r "DB::statement" app/Services/

# 2. Review raw SQL for MySQL compatibility
# - Ensure no SQLite functions (like `julianday`)
# - Ensure no PostgreSQL-specific syntax
# - Test with MySQL pretend mode if possible
```

---

## 📊 MySQL Version Requirements

### Production Target
- **MySQL Version**: 8.0+
- **Character Set**: utf8mb4
- **Collation**: utf8mb4_unicode_ci
- **Engine**: InnoDB (default)

### Feature Support Matrix
| Feature | MySQL 8.0 | MySQL 5.7 | SQLite 3 |
|---------|-----------|-----------|----------|
| JSON    | ✅ Full   | ✅ Basic  | ✅ Text  |
| Full-Text | ✅ Yes  | ✅ Yes    | ❌ No    |
| Foreign Keys | ✅ Yes | ✅ Yes   | ✅ Yes*  |
| ENUM    | ✅ Native | ✅ Native | ⚠️ Check |
| Spatial | ✅ Full   | ✅ Basic  | ❌ No    |

*SQLite requires `PRAGMA foreign_keys = ON`

---

## 🔧 Development Workflow

### Step 1: Write Migration (MySQL-first)
```php
// Always write for MySQL production
Schema::create('table', function (Blueprint $table) {
    // Use standard Laravel methods
    // Avoid DB::statement() unless necessary
    // Document any MySQL-specific features
});
```

### Step 2: Test on SQLite (Development)
```bash
# Quick local test
DB_CONNECTION=sqlite php artisan migrate:fresh --force
```

### Step 3: Validate MySQL Compatibility
```bash
# Check for forbidden patterns
./scripts/check-mysql-compatibility.sh database/migrations/NEW_MIGRATION.php
```

### Step 4: Document Exceptions
```markdown
# If using MySQL-specific features:
## Migration: 2025_10_03_create_search_index.php
- Feature: Full-text search on posts.title, posts.body
- Reason: Performance for search functionality
- Fallback: LIKE queries for SQLite testing
- Impact: Testing has reduced search performance, production optimal
```

---

## 🚨 Red Flags (Review Required)

### Immediate Review Needed
- ❌ Any `DB::statement()` without documentation
- ❌ Any raw SQL with DB-specific functions
- ❌ Any migration that fails on SQLite
- ❌ Any comment about "only works on MySQL"

### Security Review Needed
- 🔐 Raw SQL with user input (SQL injection risk)
- 🔐 Dynamic table/column names in queries
- 🔐 Concatenated SQL strings

---

## 📝 Documentation Requirements

### For Each MySQL-Specific Feature
```markdown
## File: [migration or service file]
**Feature**: [Full-text search / Spatial data / etc]
**MySQL Version**: [Minimum version required]
**Reason**: [Why this feature is necessary]
**Testing Strategy**: [How to test on SQLite or workaround]
**Production Impact**: [Performance implications]
**Rollback Plan**: [How to remove if needed]
```

---

## ✅ Quick Reference

### Safe to Use Everywhere
- `Schema::create()`, `Schema::table()`
- All `$table->` methods (id, string, text, json, timestamps, etc.)
- Laravel Query Builder (where, join, orderBy, etc.)
- Eloquent ORM methods
- `->comment()` on columns (works on both)

### Requires Documentation
- `DB::raw()`, `whereRaw()`, `selectRaw()`
- MySQL-specific functions (MATCH, AGAINST, JSON_EXTRACT)
- Full-text indexes
- Spatial data types

### Forbidden
- `DB::statement("COMMENT ON TABLE ...")`
- `PRAGMA` statements
- SQLite-specific functions (julianday, date modifiers)
- Auto-increment manipulation

---

**Version**: 1.0
**Last Updated**: 2025-10-02
**Review Frequency**: Before each migration commit

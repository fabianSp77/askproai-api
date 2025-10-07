# 🚀 MCP Server Installation Guide - Database & Redis

**Datum:** 2025-10-07
**Status:** ✅ Installation abgeschlossen
**Projekt:** API Gateway - Database Communication

---

## 📦 INSTALLIERTE MCP SERVERS

### 1. executeautomation/database-server ✅
```bash
✅ INSTALLED: @executeautomation/database-server
Version: Latest (npm global)
Purpose: MySQL, PostgreSQL, SQLite, SQL Server access
```

### 2. @gongrzhe/server-redis-mcp ✅
```bash
✅ INSTALLED: @gongrzhe/server-redis-mcp
Version: 1.0.0 (npm global)
Purpose: Redis cache operations
```

---

## ⚙️ CLAUDE DESKTOP KONFIGURATION

### Konfigurationsdatei Pfad

**macOS:**
```bash
~/Library/Application Support/Claude/claude_desktop_config.json
```

**Windows:**
```bash
%APPDATA%\Claude\claude_desktop_config.json
```

**Linux:**
```bash
~/.config/Claude/claude_desktop_config.json
```

### Vollständige Konfiguration

Kopiere folgende Konfiguration in `claude_desktop_config.json`:

```json
{
  "mcpServers": {
    "mysql-database": {
      "command": "npx",
      "args": [
        "-y",
        "@executeautomation/database-server",
        "--mysql",
        "--host", "127.0.0.1",
        "--database", "askproai_db",
        "--port", "3306",
        "--user", "askproai_user",
        "--password", "askproai_secure_pass_2024"
      ]
    },
    "redis-cache": {
      "command": "npx",
      "args": [
        "-y",
        "@gongrzhe/server-redis-mcp",
        "--host", "127.0.0.1",
        "--port", "6379"
      ]
    }
  }
}
```

**⚠️ SICHERHEITSHINWEIS:**
- Passwort ist in Klartext in der Konfiguration
- Alternative: Umgebungsvariablen verwenden
- Datei sollte nicht in git committet werden

---

## 🔧 ALTERNATIVE: SICHERE KONFIGURATION MIT ENV VARS

### Schritt 1: Environment Variables setzen

```bash
# In ~/.bashrc oder ~/.zshrc
export ASKPRO_DB_HOST="127.0.0.1"
export ASKPRO_DB_DATABASE="askproai_db"
export ASKPRO_DB_USER="askproai_user"
export ASKPRO_DB_PASSWORD="askproai_secure_pass_2024"
export ASKPRO_REDIS_HOST="127.0.0.1"
export ASKPRO_REDIS_PORT="6379"
```

### Schritt 2: Claude Desktop Config anpassen

```json
{
  "mcpServers": {
    "mysql-database": {
      "command": "npx",
      "args": [
        "-y",
        "@executeautomation/database-server",
        "--mysql",
        "--host", "${ASKPRO_DB_HOST}",
        "--database", "${ASKPRO_DB_DATABASE}",
        "--port", "3306",
        "--user", "${ASKPRO_DB_USER}",
        "--password", "${ASKPRO_DB_PASSWORD}"
      ]
    },
    "redis-cache": {
      "command": "npx",
      "args": [
        "-y",
        "@gongrzhe/server-redis-mcp",
        "--host", "${ASKPRO_REDIS_HOST}",
        "--port", "${ASKPRO_REDIS_PORT}"
      ]
    }
  }
}
```

**⚠️ HINWEIS:** Claude Desktop unterstützt möglicherweise KEINE Umgebungsvariablen-Interpolation in der Config. In diesem Fall muss die Konfiguration mit echten Werten verwendet werden.

---

## 🎯 VERFÜGBARE TOOLS

### MySQL Database Server Tools

```typescript
// Schema Operations
list_tables()              // Liste aller Tabellen
describe_table(table_name) // Spalten-Info einer Tabelle

// Query Operations
read_query(query)          // SELECT Abfragen
write_query(query)         // INSERT/UPDATE/DELETE
export_query(query, format) // Export als CSV/JSON

// DDL Operations
create_table(query)        // CREATE TABLE
alter_table(query)         // ALTER TABLE
drop_table(table_name)     // DROP TABLE (mit Safety-Flag)

// Business Intelligence
append_insight(insight)    // Business-Insight hinzufügen
list_insights()            // Alle Insights anzeigen
```

### Redis Cache Server Tools

```typescript
// Basic Operations
GET(key)                   // Wert abrufen
SET(key, value)            // Wert setzen
DEL(key)                   // Key löschen

// List Operations
LPUSH(key, value)          // Links einfügen
RPUSH(key, value)          // Rechts einfügen
LPOP(key)                  // Links entfernen
RPOP(key)                  // Rechts entfernen

// Hash Operations
HGET(key, field)           // Hash-Feld abrufen
HSET(key, field, value)    // Hash-Feld setzen
HGETALL(key)               // Alle Hash-Felder

// Set Operations
SADD(key, member)          // Set-Member hinzufügen
SMEMBERS(key)              // Alle Set-Members
SREM(key, member)          // Set-Member entfernen
```

---

## 📝 USAGE EXAMPLES

### MySQL Database Queries

```typescript
// Natural Language -> SQL
"Show me all tables in the database"
→ list_tables()

"What columns does the users table have?"
→ describe_table("users")

"Get all users created in the last 7 days"
→ read_query("SELECT * FROM users WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)")

"Export all active customers to CSV"
→ export_query("SELECT * FROM customers WHERE status='active'", "csv")

"Add a new product to the products table"
→ write_query("INSERT INTO products (name, price) VALUES ('AI PC', 1299.99)")
```

### Redis Cache Operations

```typescript
// Natural Language -> Redis Commands
"Get the cached value for session_user_123"
→ GET("session_user_123")

"Cache this user data with key user:456"
→ SET("user:456", "{\"name\":\"John\",\"email\":\"john@example.com\"}")

"Add item to the shopping cart queue"
→ LPUSH("cart:789", "product_id:123")

"Get all members of the active_users set"
→ SMEMBERS("active_users")

"Store customer data in hash"
→ HSET("customer:101", "name", "Alice")
```

---

## ✅ VALIDIERUNGS-CHECKLISTE

Nach der Installation und Konfiguration:

### MySQL Server Validation
- [ ] Claude Desktop neu starten
- [ ] MCP Server erscheint in Claude Settings
- [ ] `list_tables` funktioniert
- [ ] `describe_table` für eine Tabelle testen
- [ ] `read_query` auf bestehende Daten ausführen
- [ ] Fehlerbehandlung bei ungültigen Queries testen

### Redis Server Validation
- [ ] Claude Desktop neu starten
- [ ] Redis MCP Server erscheint in Settings
- [ ] `GET` auf existierenden Key testen
- [ ] `SET` einen Test-Key setzen
- [ ] `DEL` Test-Key löschen
- [ ] Connection timeout testen

---

## 🔍 TROUBLESHOOTING

### MCP Server erscheint nicht

```bash
# 1. Prüfe ob Packages installiert sind
npm list -g @executeautomation/database-server
npm list -g @gongrzhe/server-redis-mcp

# 2. Prüfe Claude Desktop Logs (macOS)
tail -f ~/Library/Logs/Claude/mcp-server-mysql-database.log
tail -f ~/Library/Logs/Claude/mcp-server-redis-cache.log

# 3. Validiere JSON Syntax
cat ~/Library/Application\ Support/Claude/claude_desktop_config.json | jq .
```

### Connection Errors

**MySQL Connection Failed:**
```bash
# Test MySQL connection
mysql -h 127.0.0.1 -P 3306 -u askproai_user -paskproai_secure_pass_2024 askproai_db

# Check if MySQL is running
sudo systemctl status mysql
```

**Redis Connection Failed:**
```bash
# Test Redis connection
redis-cli -h 127.0.0.1 -p 6379 PING

# Check if Redis is running
sudo systemctl status redis
```

### Permission Errors

```bash
# Check database user permissions
SHOW GRANTS FOR 'askproai_user'@'localhost';

# Check Redis ACL (if configured)
redis-cli ACL LIST
```

---

## 🔐 SICHERHEITS-BEST-PRACTICES

### 1. Read-Only User für Analysen

```sql
-- MySQL: Create read-only user
CREATE USER 'askproai_readonly'@'localhost' IDENTIFIED BY 'secure_password';
GRANT SELECT ON askproai_db.* TO 'askproai_readonly'@'localhost';
FLUSH PRIVILEGES;
```

Konfiguration anpassen:
```json
"--user", "askproai_readonly",
"--password", "secure_password"
```

### 2. Redis ACL für MCP Access

```bash
# Redis: Create restricted user
127.0.0.1:6379> ACL SETUSER mcp_user on >mcp_password ~* +@read +@write -@dangerous
```

### 3. Connection Timeout setzen

```json
"args": [
  "--mysql",
  "--host", "127.0.0.1",
  "--connection-timeout", "10000"
]
```

---

## 📊 MONITORING & LOGGING

### Claude Desktop Logs überwachen

```bash
# macOS
tail -f ~/Library/Logs/Claude/mcp*.log

# Windows (PowerShell)
Get-Content "$env:APPDATA\Claude\Logs\mcp*.log" -Wait

# Linux
tail -f ~/.config/Claude/Logs/mcp*.log
```

### Database Query Logging aktivieren

```bash
# MySQL slow query log
SET GLOBAL slow_query_log = 'ON';
SET GLOBAL long_query_time = 1;

# Redis monitor (Performance-Impact!)
redis-cli MONITOR
```

---

## 🎓 ADVANCED CONFIGURATION

### AWS RDS MySQL mit IAM Auth

```json
{
  "mysql-rds": {
    "command": "npx",
    "args": [
      "-y",
      "@executeautomation/database-server",
      "--mysql",
      "--aws-iam-auth",
      "--host", "askproai-db.abc123.eu-central-1.rds.amazonaws.com",
      "--database", "askproai_db",
      "--user", "iam_user",
      "--aws-region", "eu-central-1"
    ]
  }
}
```

### Redis Cluster Mode

```json
{
  "redis-cluster": {
    "command": "npx",
    "args": [
      "-y",
      "@gongrzhe/server-redis-mcp",
      "--cluster-mode",
      "--nodes", "redis1:6379,redis2:6379,redis3:6379"
    ]
  }
}
```

---

## 📚 WEITERE RESSOURCEN

### Offizielle Dokumentation

- **executeautomation/database-server:**
  https://github.com/executeautomation/mcp-database-server

- **@gongrzhe/server-redis-mcp:**
  https://www.npmjs.com/package/@gongrzhe/server-redis-mcp

- **Offizieller Redis MCP (Python-basiert):**
  https://github.com/redis/mcp-redis

### Alternative Redis MCP Server

Für erweiterte Redis-Features (JSON, Streams, Vector Search):

```bash
# Redis Official MCP (Python)
pip install redis-mcp-server

# Oder mit uvx
uvx --from git+https://github.com/redis/mcp-redis.git redis-mcp-server \
  --url redis://localhost:6379/0
```

---

## 🎯 NÄCHSTE SCHRITTE

1. **Validierung durchführen:**
   - [ ] Claude Desktop neu starten
   - [ ] Beide MCP Server testen
   - [ ] Beispiel-Queries ausführen

2. **Sicherheit optimieren:**
   - [ ] Read-Only User für Analysen erstellen
   - [ ] Redis ACL konfigurieren
   - [ ] Credentials aus git ausschließen

3. **Monitoring einrichten:**
   - [ ] Log-Rotation konfigurieren
   - [ ] Query-Performance überwachen
   - [ ] Alert bei Connection-Fehlern

---

## 📞 SUPPORT

Bei Problemen:
1. Claude Desktop Logs prüfen
2. MCP Server GitHub Issues checken
3. Database Connection validieren
4. JSON Config Syntax validieren

**Erfolgreich installiert und konfiguriert! 🎉**

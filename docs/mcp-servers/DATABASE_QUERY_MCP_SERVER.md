# Database Query MCP Server Documentation

## Overview

The Database Query MCP Server (adapted from PostgreSQL MCP) enables natural language database queries for AskProAI's MySQL/MariaDB database. It simplifies data operations by translating plain English into SQL queries, making database interactions accessible to developers regardless of SQL expertise.

## Installation Status

✅ **INSTALLED** - The Database Query MCP Server has been successfully installed and configured for MySQL/MariaDB.

### Installation Summary
- **Date**: 2025-07-09
- **Type**: Adapted from PostgreSQL MCP to work with MySQL/MariaDB
- **Integration**: Fully integrated with Laravel MCP architecture
- **Testing**: All tests passing successfully

## Key Features

- **Natural Language Queries**: Translates plain English into SQL queries
- **Safe Query Execution**: Only allows SELECT queries for data safety
- **Schema Exploration**: Browse tables and column structures
- **Query Analysis**: Optimize queries with execution plan analysis
- **Database Statistics**: Get insights into table sizes and performance
- **Auto-completion Support**: Understands common table names and patterns

## Why It's Essential

1. **Productivity Boost**: No need to remember exact SQL syntax
2. **Data Accessibility**: Query data using natural language
3. **Safety First**: Prevents accidental data modifications
4. **Learning Tool**: See how natural language translates to SQL

## Available Tools

### 1. query_natural
Query database using natural language.

```php
$result = $db->executeTool('query_natural', [
    'query' => 'get all users created last month',
    'limit' => 100
]);
```

**Supported patterns:**
- `get all [table]` → `SELECT * FROM table`
- `count [table]` → `SELECT COUNT(*) FROM table`
- `get [table] where [column] = [value]` → `SELECT * FROM table WHERE column = value`
- `get last [N] [table]` → `SELECT * FROM table ORDER BY created_at DESC LIMIT N`
- `sum [column] from [table]` → `SELECT SUM(column) FROM table`
- `average [column] from [table]` → `SELECT AVG(column) FROM table`

### 2. execute_sql
Execute raw SQL queries (SELECT only for safety).

```php
$result = $db->executeTool('execute_sql', [
    'sql' => 'SELECT u.name, c.name as company FROM users u JOIN companies c ON u.company_id = c.id',
    'bindings' => []
]);
```

### 3. describe_table
Get detailed table schema information.

```php
$result = $db->executeTool('describe_table', [
    'table' => 'appointments'
]);
```

### 4. list_tables
List all tables in the database.

```php
$result = $db->executeTool('list_tables', [
    'pattern' => 'user'  // optional filter
]);
```

### 5. analyze_query
Analyze query execution plan for optimization.

```php
$result = $db->executeTool('analyze_query', [
    'sql' => 'SELECT * FROM appointments WHERE customer_id = 123'
]);
```

### 6. get_statistics
Get database or table statistics.

```php
$result = $db->executeTool('get_statistics', [
    'table' => 'calls',  // optional, omit for database-wide stats
    'type' => 'all'      // rows, size, indexes, all
]);
```

## Real-World Use Cases

### Use Case 1: Business Intelligence Queries

```php
$db = app(DatabaseQueryMCPServer::class);

// Monthly sales summary
$result = $db->executeTool('query_natural', [
    'query' => 'count appointments created last month'
]);

echo "Appointments last month: " . $result['data']['results'][0]->count;

// Customer insights
$result = $db->executeTool('query_natural', [
    'query' => 'get customers created last week'
]);

foreach ($result['data']['results'] as $customer) {
    echo "New customer: {$customer->name} ({$customer->email})\n";
}
```

### Use Case 2: Performance Analysis

```php
// Find large tables
$stats = $db->executeTool('get_statistics', []);
echo "Database size: {$stats['data']['total_size']->total_size_mb} MB\n";

foreach ($stats['data']['largest_tables'] as $table) {
    if ($table->size_mb > 10) {
        echo "Large table alert: {$table->table_name} is {$table->size_mb} MB\n";
        
        // Analyze queries on large tables
        $analysis = $db->executeTool('analyze_query', [
            'sql' => "SELECT * FROM {$table->table_name} WHERE created_at > DATE_SUB(NOW(), INTERVAL 1 DAY)"
        ]);
        
        if (!empty($analysis['data']['analysis']['warnings'])) {
            echo "Performance warnings for {$table->table_name}:\n";
            foreach ($analysis['data']['analysis']['warnings'] as $warning) {
                echo "  - {$warning}\n";
            }
        }
    }
}
```

### Use Case 3: Data Exploration

```php
// Explore table structure
$tables = $db->executeTool('list_tables', ['pattern' => 'appointment']);

foreach ($tables['data']['tables'] as $table) {
    echo "\nTable: {$table['name']} ({$table['row_count']} rows)\n";
    
    // Get column details
    $schema = $db->executeTool('describe_table', ['table' => $table['name']]);
    
    echo "Columns:\n";
    foreach ($schema['data']['columns'] as $column) {
        echo "  - {$column['name']} ({$column['type']})\n";
    }
}
```

### Use Case 4: Automated Reporting

```php
// Daily operations report
$queries = [
    'New users today' => 'count users created today',
    'Active appointments' => 'count appointments where status = "scheduled"',
    'Calls last hour' => 'get calls created last hour',
    'Top customer' => 'get customer with most appointments'
];

$report = [];

foreach ($queries as $metric => $query) {
    $result = $db->executeTool('query_natural', ['query' => $query]);
    
    if ($result['success']) {
        $report[$metric] = $result['data'];
        echo "{$metric}: ";
        
        if (isset($result['data']['results'][0]->count)) {
            echo $result['data']['results'][0]->count . "\n";
        } else {
            echo $result['data']['count'] . " records\n";
        }
    }
}
```

### Use Case 5: Development Helper

```php
// Quick data checks during development
class DevelopmentHelper
{
    protected $db;
    
    public function __construct()
    {
        $this->db = app(DatabaseQueryMCPServer::class);
    }
    
    public function checkUserData($email)
    {
        // Natural language is easier than remembering joins
        $result = $this->db->executeTool('query_natural', [
            'query' => "get users where email = {$email}"
        ]);
        
        if ($result['success'] && $result['data']['count'] > 0) {
            $user = $result['data']['results'][0];
            
            // Get related data
            $appointments = $this->db->executeTool('query_natural', [
                'query' => "get appointments where customer_id = {$user->id}"
            ]);
            
            return [
                'user' => $user,
                'appointments' => $appointments['data']['results'] ?? []
            ];
        }
        
        return null;
    }
    
    public function getDatabaseHealth()
    {
        $stats = $this->db->executeTool('get_statistics', []);
        
        return [
            'total_size' => $stats['data']['total_size']->total_size_mb,
            'table_count' => $stats['data']['table_count'],
            'largest_table' => $stats['data']['largest_tables'][0]
        ];
    }
}
```

## Natural Language Examples

### Basic Queries
- `get all users` → SELECT * FROM users
- `count companies` → SELECT COUNT(*) FROM companies
- `show last 10 calls` → SELECT * FROM calls ORDER BY created_at DESC LIMIT 10

### Filtered Queries
- `get appointments where status = "completed"` → SELECT * FROM appointments WHERE status = 'completed'
- `get users created yesterday` → SELECT * FROM users WHERE DATE(created_at) = DATE_SUB(CURDATE(), INTERVAL 1 DAY)

### Aggregate Queries
- `sum amount from payments` → SELECT SUM(amount) FROM payments
- `average duration_sec from calls` → SELECT AVG(duration_sec) FROM calls
- `maximum price from services` → SELECT MAX(price) FROM services

### Date-based Queries
- `get orders created today` → SELECT * FROM orders WHERE DATE(created_at) = CURDATE()
- `get customers created last week` → SELECT * FROM customers WHERE created_at >= DATE_SUB(NOW(), INTERVAL 1 WEEK)
- `get appointments created last month` → SELECT * FROM appointments WHERE created_at >= DATE_SUB(NOW(), INTERVAL 1 MONTH)

## Integration with Other MCP Servers

```php
// Combine with Sequential Thinking for analysis
$thinking = app(SequentialThinkingMCPServer::class);
$db = app(DatabaseQueryMCPServer::class);

// 1. Plan the analysis
$plan = $thinking->executeTool('analyze_problem', [
    'problem' => 'Identify customer churn patterns in our database'
]);

// 2. Execute queries based on the plan
$queries = [
    'Customers with no recent activity' => 'get customers where last_appointment < 30 days ago',
    'Cancelled appointment rate' => 'count appointments where status = "cancelled"',
    'Average customer lifetime' => 'average days between first and last appointment per customer'
];

foreach ($queries as $analysis => $query) {
    $result = $db->executeTool('query_natural', ['query' => $query]);
    // Process results...
}
```

## Performance Tips

1. **Use Limits**: Always specify limits for large tables
2. **Be Specific**: More specific queries generate better SQL
3. **Check Execution Plans**: Use `analyze_query` for slow queries
4. **Monitor Table Sizes**: Regular statistics checks help identify growth

## Security Considerations

- Only SELECT queries are allowed (no data modification)
- SQL injection protection through parameterized queries
- Natural language parsing validates input
- Database credentials secured through Laravel's config

## Troubleshooting

### Query Not Understood
If natural language isn't recognized:
1. Use simpler patterns from the examples
2. Check table names match your database
3. Use `execute_sql` with raw SQL as fallback

### Performance Issues
1. Use `analyze_query` to check execution plans
2. Add indexes based on warnings
3. Limit result sets with the `limit` parameter

### Connection Errors
1. Verify database credentials in `.env`
2. Check MySQL/MariaDB service is running
3. Ensure database user has SELECT permissions

## Testing

Run the test script to verify functionality:

```bash
php test-database-query-mcp.php
```

## Future Enhancements

- Join query natural language support
- Query result caching
- Query history and favorites
- Export to CSV/Excel
- Visual query builder integration
- Advanced aggregation patterns
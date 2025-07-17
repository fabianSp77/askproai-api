<?php

namespace App\Services\MCP;

use App\Services\MCP\Contracts\ExternalMCPProvider;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\QueryException;

class DatabaseQueryMCPServer implements ExternalMCPProvider
{
    protected string $name = 'database_query';
    protected string $version = '1.0.0';
    protected array $capabilities = [
        'natural_language_queries',
        'sql_generation',
        'data_exploration',
        'schema_inspection',
        'query_optimization',
        'data_analysis'
    ];

    // Common natural language patterns to SQL mappings
    protected array $nlpPatterns = [
        '/^(get|fetch|show|list|find) all (.+)$/i' => 'SELECT * FROM :table',
        '/^count (.+)$/i' => 'SELECT COUNT(*) as count FROM :table',
        '/^(get|fetch|show) (.+) where (.+) (?:is|equals|=) (.+)$/i' => 'SELECT * FROM :table WHERE :column = :value',
        '/^(get|fetch|show) last (\d+) (.+)$/i' => 'SELECT * FROM :table ORDER BY created_at DESC LIMIT :limit',
        '/^(get|fetch|show) first (\d+) (.+)$/i' => 'SELECT * FROM :table ORDER BY created_at ASC LIMIT :limit',
        '/^(sum|total) (.+) from (.+)$/i' => 'SELECT SUM(:column) as total FROM :table',
        '/^average (.+) from (.+)$/i' => 'SELECT AVG(:column) as average FROM :table',
        '/^(max|maximum) (.+) from (.+)$/i' => 'SELECT MAX(:column) as maximum FROM :table',
        '/^(min|minimum) (.+) from (.+)$/i' => 'SELECT MIN(:column) as minimum FROM :table',
    ];

    public function getName(): string
    {
        return $this->name;
    }

    public function getVersion(): string
    {
        return $this->version;
    }

    public function getCapabilities(): array
    {
        return $this->capabilities;
    }

    /**
     * Get tool definitions for database operations
     */
    public function getTools(): array
    {
        return [
            [
                'name' => 'query_natural',
                'description' => 'Query database using natural language',
                'category' => 'query',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'query' => [
                            'type' => 'string',
                            'description' => 'Natural language query (e.g., "get all users created last month")'
                        ],
                        'limit' => [
                            'type' => 'integer',
                            'description' => 'Maximum number of results',
                            'default' => 100
                        ]
                    ],
                    'required' => ['query']
                ]
            ],
            [
                'name' => 'execute_sql',
                'description' => 'Execute raw SQL query (SELECT only for safety)',
                'category' => 'query',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'sql' => [
                            'type' => 'string',
                            'description' => 'SQL query to execute'
                        ],
                        'bindings' => [
                            'type' => 'array',
                            'items' => ['type' => 'string'],
                            'description' => 'Query parameter bindings'
                        ]
                    ],
                    'required' => ['sql']
                ]
            ],
            [
                'name' => 'describe_table',
                'description' => 'Get table schema information',
                'category' => 'schema',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'table' => [
                            'type' => 'string',
                            'description' => 'Table name to describe'
                        ]
                    ],
                    'required' => ['table']
                ]
            ],
            [
                'name' => 'list_tables',
                'description' => 'List all tables in the database',
                'category' => 'schema',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'pattern' => [
                            'type' => 'string',
                            'description' => 'Optional pattern to filter tables'
                        ]
                    ]
                ]
            ],
            [
                'name' => 'analyze_query',
                'description' => 'Analyze and explain a query execution plan',
                'category' => 'optimization',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'sql' => [
                            'type' => 'string',
                            'description' => 'SQL query to analyze'
                        ]
                    ],
                    'required' => ['sql']
                ]
            ],
            [
                'name' => 'get_statistics',
                'description' => 'Get statistics about a table or database',
                'category' => 'analysis',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'table' => [
                            'type' => 'string',
                            'description' => 'Table name (optional, for database-wide stats)'
                        ],
                        'type' => [
                            'type' => 'string',
                            'enum' => ['rows', 'size', 'indexes', 'all'],
                            'default' => 'all'
                        ]
                    ]
                ]
            ]
        ];
    }

    /**
     * Execute a database tool
     */
    public function executeTool(string $tool, array $arguments): array
    {
        Log::debug("Executing Database Query tool: {$tool}", $arguments);

        try {
            switch ($tool) {
                case 'query_natural':
                    return $this->queryNatural($arguments);
                
                case 'execute_sql':
                    return $this->executeSql($arguments);
                
                case 'describe_table':
                    return $this->describeTable($arguments);
                
                case 'list_tables':
                    return $this->listTables($arguments);
                
                case 'analyze_query':
                    return $this->analyzeQuery($arguments);
                
                case 'get_statistics':
                    return $this->getStatistics($arguments);
                
                default:
                    return [
                        'success' => false,
                        'error' => "Unknown database tool: {$tool}",
                        'data' => null
                    ];
            }
        } catch (QueryException $e) {
            return [
                'success' => false,
                'error' => 'Database query error: ' . $e->getMessage(),
                'data' => null
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => 'Database operation failed: ' . $e->getMessage(),
                'data' => null
            ];
        }
    }

    /**
     * Query database using natural language
     */
    protected function queryNatural(array $arguments): array
    {
        $naturalQuery = $arguments['query'];
        $limit = $arguments['limit'] ?? 100;

        // Try to parse natural language to SQL
        $sql = $this->parseNaturalLanguage($naturalQuery);
        
        if (!$sql) {
            // If we can't parse it, provide helpful suggestions
            return [
                'success' => false,
                'error' => 'Could not understand the query. Try patterns like: "get all users", "count orders", "show last 10 calls"',
                'data' => [
                    'suggestions' => [
                        'get all [table_name]',
                        'count [table_name]',
                        'get [table_name] where [column] = [value]',
                        'get last [number] [table_name]',
                        'sum [column] from [table_name]'
                    ]
                ]
            ];
        }

        // Execute the generated SQL
        return $this->executeSql(['sql' => $sql, 'bindings' => []]);
    }

    /**
     * Parse natural language to SQL
     */
    protected function parseNaturalLanguage(string $query): ?string
    {
        $query = trim($query);
        
        // Check against our patterns
        foreach ($this->nlpPatterns as $pattern => $sqlTemplate) {
            if (preg_match($pattern, $query, $matches)) {
                return $this->buildSqlFromTemplate($sqlTemplate, $matches, $query);
            }
        }

        // Try to handle more complex queries
        return $this->parseComplexQuery($query);
    }

    /**
     * Build SQL from template and matches
     */
    protected function buildSqlFromTemplate(string $template, array $matches, string $originalQuery): string
    {
        // Extract table names (pluralize/singularize as needed)
        $tableName = $this->extractTableName($matches, $originalQuery);
        
        $sql = str_replace(':table', $tableName, $template);
        
        // Handle specific replacements based on the pattern
        if (strpos($sql, ':column') !== false && isset($matches[3])) {
            $sql = str_replace(':column', $matches[3], $sql);
        }
        
        if (strpos($sql, ':value') !== false && isset($matches[4])) {
            $value = trim($matches[4], '"\'');
            $sql = str_replace(':value', "'{$value}'", $sql);
        }
        
        if (strpos($sql, ':limit') !== false && isset($matches[2])) {
            $sql = str_replace(':limit', $matches[2], $sql);
        }
        
        return $sql;
    }

    /**
     * Extract table name from query
     */
    protected function extractTableName(array $matches, string $query): string
    {
        // Common table names in the system
        $knownTables = [
            'users' => 'users',
            'user' => 'users',
            'companies' => 'companies',
            'company' => 'companies',
            'branches' => 'branches',
            'branch' => 'branches',
            'appointments' => 'appointments',
            'appointment' => 'appointments',
            'calls' => 'calls',
            'call' => 'calls',
            'customers' => 'customers',
            'customer' => 'customers',
            'staff' => 'staff',
            'services' => 'services',
            'service' => 'services'
        ];
        
        // Try to find table name in matches
        foreach ($matches as $match) {
            $match = strtolower(trim($match));
            if (isset($knownTables[$match])) {
                return $knownTables[$match];
            }
        }
        
        // Try to extract from the query
        $words = explode(' ', strtolower($query));
        foreach ($words as $word) {
            if (isset($knownTables[$word])) {
                return $knownTables[$word];
            }
        }
        
        // Default to last meaningful word (often the table name)
        $lastWord = end($matches);
        return $this->pluralize(strtolower(trim($lastWord)));
    }

    /**
     * Parse more complex queries
     */
    protected function parseComplexQuery(string $query): ?string
    {
        $query = strtolower($query);
        
        // Handle date-based queries
        if (strpos($query, 'today') !== false || strpos($query, 'yesterday') !== false || 
            strpos($query, 'last week') !== false || strpos($query, 'last month') !== false) {
            return $this->parseDateQuery($query);
        }
        
        // Handle join queries
        if (strpos($query, 'with their') !== false || strpos($query, 'and their') !== false) {
            return $this->parseJoinQuery($query);
        }
        
        return null;
    }

    /**
     * Parse date-based queries
     */
    protected function parseDateQuery(string $query): string
    {
        $table = $this->extractTableFromQuery($query);
        
        if (strpos($query, 'today') !== false) {
            return "SELECT * FROM {$table} WHERE DATE(created_at) = CURDATE()";
        } elseif (strpos($query, 'yesterday') !== false) {
            return "SELECT * FROM {$table} WHERE DATE(created_at) = DATE_SUB(CURDATE(), INTERVAL 1 DAY)";
        } elseif (strpos($query, 'last week') !== false) {
            return "SELECT * FROM {$table} WHERE created_at >= DATE_SUB(NOW(), INTERVAL 1 WEEK)";
        } elseif (strpos($query, 'last month') !== false) {
            return "SELECT * FROM {$table} WHERE created_at >= DATE_SUB(NOW(), INTERVAL 1 MONTH)";
        }
        
        return "SELECT * FROM {$table}";
    }

    /**
     * Extract table from query text
     */
    protected function extractTableFromQuery(string $query): string
    {
        $tables = ['users', 'companies', 'branches', 'appointments', 'calls', 'customers', 'staff'];
        
        foreach ($tables as $table) {
            if (strpos($query, $table) !== false || strpos($query, rtrim($table, 's')) !== false) {
                return $table;
            }
        }
        
        return 'users'; // default
    }

    /**
     * Simple pluralization
     */
    protected function pluralize(string $word): string
    {
        if (substr($word, -1) === 's') {
            return $word;
        }
        
        if (substr($word, -1) === 'y') {
            return substr($word, 0, -1) . 'ies';
        }
        
        return $word . 's';
    }

    /**
     * Parse join queries
     */
    protected function parseJoinQuery(string $query): string
    {
        // This is a simplified version - real implementation would be more complex
        if (strpos($query, 'users with their companies') !== false) {
            return "SELECT u.*, c.name as company_name FROM users u LEFT JOIN companies c ON u.company_id = c.id";
        }
        
        if (strpos($query, 'appointments with their customers') !== false) {
            return "SELECT a.*, c.name as customer_name, c.phone as customer_phone 
                    FROM appointments a 
                    LEFT JOIN customers c ON a.customer_id = c.id";
        }
        
        return "SELECT * FROM users"; // fallback
    }

    /**
     * Execute SQL query
     */
    protected function executeSql(array $arguments): array
    {
        $sql = $arguments['sql'];
        $bindings = $arguments['bindings'] ?? [];

        // Safety check - only allow SELECT queries
        if (!$this->isSafeQuery($sql)) {
            return [
                'success' => false,
                'error' => 'Only SELECT queries are allowed for safety reasons',
                'data' => null
            ];
        }

        try {
            $results = DB::select($sql, $bindings);
            
            return [
                'success' => true,
                'error' => null,
                'data' => [
                    'results' => $results,
                    'count' => count($results),
                    'query' => $sql
                ]
            ];
        } catch (QueryException $e) {
            return [
                'success' => false,
                'error' => 'Query error: ' . $e->getMessage(),
                'data' => ['query' => $sql]
            ];
        }
    }

    /**
     * Check if query is safe (SELECT only)
     */
    protected function isSafeQuery(string $sql): bool
    {
        $sql = trim($sql);
        
        // Check if it starts with SELECT, EXPLAIN, SHOW, or DESCRIBE (case insensitive)
        if (!preg_match('/^(SELECT|EXPLAIN|SHOW|DESCRIBE)/i', $sql)) {
            return false;
        }
        
        // Check for dangerous keywords (case insensitive)
        $dangerousKeywords = ['INSERT', 'UPDATE', 'DELETE', 'DROP', 'TRUNCATE', 'ALTER', 'CREATE'];
        $upperSql = strtoupper($sql);
        foreach ($dangerousKeywords as $keyword) {
            if (strpos($upperSql, $keyword) !== false) {
                return false;
            }
        }
        
        return true;
    }

    /**
     * Describe table structure
     */
    protected function describeTable(array $arguments): array
    {
        $table = $arguments['table'];

        if (!Schema::hasTable($table)) {
            return [
                'success' => false,
                'error' => "Table '{$table}' does not exist",
                'data' => null
            ];
        }

        $columns = Schema::getColumnListing($table);
        $columnDetails = [];

        foreach ($columns as $column) {
            $columnDetails[] = [
                'name' => $column,
                'type' => Schema::getColumnType($table, $column),
                // Note: Laravel's Schema builder has limited column info access
            ];
        }

        // Get additional info using raw query
        $tableInfo = DB::select("SHOW CREATE TABLE {$table}");
        
        return [
            'success' => true,
            'error' => null,
            'data' => [
                'table' => $table,
                'columns' => $columnDetails,
                'column_count' => count($columns),
                'create_statement' => $tableInfo[0]->{'Create Table'} ?? null
            ]
        ];
    }

    /**
     * List all tables
     */
    protected function listTables(array $arguments): array
    {
        $pattern = $arguments['pattern'] ?? null;
        
        $tables = DB::select('SHOW TABLES');
        $dbName = env('DB_DATABASE');
        $tableKey = "Tables_in_{$dbName}";
        
        $tableList = [];
        foreach ($tables as $table) {
            $tableName = $table->$tableKey;
            if (!$pattern || strpos($tableName, $pattern) !== false) {
                // Get row count for each table
                $count = DB::table($tableName)->count();
                $tableList[] = [
                    'name' => $tableName,
                    'row_count' => $count
                ];
            }
        }

        return [
            'success' => true,
            'error' => null,
            'data' => [
                'tables' => $tableList,
                'count' => count($tableList)
            ]
        ];
    }

    /**
     * Analyze query execution plan
     */
    protected function analyzeQuery(array $arguments): array
    {
        $sql = $arguments['sql'];

        if (!$this->isSafeQuery($sql)) {
            return [
                'success' => false,
                'error' => 'Only SELECT queries can be analyzed',
                'data' => null
            ];
        }

        try {
            $explain = DB::select("EXPLAIN {$sql}");
            
            return [
                'success' => true,
                'error' => null,
                'data' => [
                    'query' => $sql,
                    'execution_plan' => $explain,
                    'analysis' => $this->analyzeExecutionPlan($explain)
                ]
            ];
        } catch (QueryException $e) {
            return [
                'success' => false,
                'error' => 'Analysis error: ' . $e->getMessage(),
                'data' => null
            ];
        }
    }

    /**
     * Analyze execution plan results
     */
    protected function analyzeExecutionPlan(array $explain): array
    {
        $analysis = [
            'warnings' => [],
            'suggestions' => []
        ];

        foreach ($explain as $row) {
            // Check for full table scans
            if ($row->type === 'ALL') {
                $analysis['warnings'][] = "Full table scan on table '{$row->table}'";
                $analysis['suggestions'][] = "Consider adding an index";
            }

            // Check for missing indexes
            if ($row->key === null && $row->possible_keys !== null) {
                $analysis['warnings'][] = "No index used despite available indexes";
            }

            // Check for temporary tables
            if (strpos($row->Extra ?? '', 'Using temporary') !== false) {
                $analysis['warnings'][] = "Query uses temporary tables";
            }

            // Check for filesort
            if (strpos($row->Extra ?? '', 'Using filesort') !== false) {
                $analysis['warnings'][] = "Query uses filesort";
                $analysis['suggestions'][] = "Consider adding an index on ORDER BY columns";
            }
        }

        return $analysis;
    }

    /**
     * Get database/table statistics
     */
    protected function getStatistics(array $arguments): array
    {
        $table = $arguments['table'] ?? null;
        $type = $arguments['type'] ?? 'all';

        if ($table) {
            // Table-specific statistics
            if (!Schema::hasTable($table)) {
                return [
                    'success' => false,
                    'error' => "Table '{$table}' does not exist",
                    'data' => null
                ];
            }

            $stats = [
                'table' => $table,
                'row_count' => DB::table($table)->count()
            ];

            if ($type === 'all' || $type === 'size') {
                $sizeInfo = DB::select("
                    SELECT 
                        ROUND(((data_length + index_length) / 1024 / 1024), 2) AS size_mb,
                        ROUND((data_length / 1024 / 1024), 2) AS data_size_mb,
                        ROUND((index_length / 1024 / 1024), 2) AS index_size_mb
                    FROM information_schema.TABLES 
                    WHERE table_schema = ? AND table_name = ?
                ", [env('DB_DATABASE'), $table]);

                if (!empty($sizeInfo)) {
                    $stats['size'] = $sizeInfo[0];
                }
            }

            if ($type === 'all' || $type === 'indexes') {
                $indexes = DB::select("SHOW INDEX FROM {$table}");
                $stats['indexes'] = $indexes;
                $stats['index_count'] = count(array_unique(array_column($indexes, 'Key_name')));
            }

        } else {
            // Database-wide statistics
            $dbName = env('DB_DATABASE');
            
            $stats = [
                'database' => $dbName,
                'table_count' => count(DB::select('SHOW TABLES')),
                'total_size' => DB::select("
                    SELECT 
                        ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) AS total_size_mb,
                        ROUND(SUM(data_length) / 1024 / 1024, 2) AS total_data_mb,
                        ROUND(SUM(index_length) / 1024 / 1024, 2) AS total_index_mb
                    FROM information_schema.TABLES 
                    WHERE table_schema = ?
                ", [$dbName])[0]
            ];

            // Get top 5 largest tables
            $largestTables = DB::select("
                SELECT 
                    table_name,
                    ROUND(((data_length + index_length) / 1024 / 1024), 2) AS size_mb,
                    table_rows
                FROM information_schema.TABLES 
                WHERE table_schema = ?
                ORDER BY (data_length + index_length) DESC
                LIMIT 5
            ", [$dbName]);

            $stats['largest_tables'] = $largestTables;
        }

        return [
            'success' => true,
            'error' => null,
            'data' => $stats
        ];
    }

    /**
     * Check if external server is running
     */
    public function isExternalServerRunning(): bool
    {
        // PostgreSQL MCP runs via npx on-demand
        return true;
    }

    /**
     * Start the external server
     */
    public function startExternalServer(): bool
    {
        // PostgreSQL MCP runs on-demand via npx
        return true;
    }

    /**
     * Get server configuration
     */
    public function getConfiguration(): array
    {
        return [
            'database_type' => 'MySQL/MariaDB',
            'external_server' => config('mcp-external.external_servers.postgres'),
            'uses_npx' => true,
            'natural_language_enabled' => true
        ];
    }
}
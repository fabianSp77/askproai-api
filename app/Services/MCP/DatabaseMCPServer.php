<?php

namespace App\Services\MCP;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class DatabaseMCPServer
{
    protected array $config;
    protected array $allowedTables = [];
    
    public function __construct()
    {
        $this->config = config('mcp-database', [
            'cache' => [
                'ttl' => 300,
                'prefix' => 'mcp:db'
            ],
            'limits' => [
                'max_rows' => 1000,
                'max_tables' => 50
            ],
            'read_only' => true
        ]);
        
        // Define allowed tables for read-only access
        $this->allowedTables = [
            'appointments', 'calls', 'customers', 'companies', 'branches',
            'staff', 'services', 'calcom_event_types', 'staff_event_types',
            'phone_numbers', 'webhook_events', 'mcp_metrics', 'branch_event_types',
            'service_event_type_mappings', 'working_hours'
        ];
    }
    
    /**
     * Get database schema information
     */
    public function getSchema(array $params = []): array
    {
        $cacheKey = $this->getCacheKey('schema', $params);
        
        return Cache::remember($cacheKey, $this->config['cache']['ttl'], function () use ($params) {
            $tables = $params['tables'] ?? $this->allowedTables;
            $schema = [];
            
            foreach ($tables as $table) {
                if (!in_array($table, $this->allowedTables)) {
                    continue;
                }
                
                if (Schema::hasTable($table)) {
                    $schema[$table] = [
                        'columns' => $this->getTableColumns($table),
                        'indexes' => $this->getTableIndexes($table),
                        'row_count' => $this->getTableRowCount($table)
                    ];
                }
            }
            
            return [
                'database' => config('database.connections.mysql.database'),
                'tables' => $schema,
                'generated_at' => now()->toIso8601String()
            ];
        });
    }
    
    /**
     * Execute a read-only query
     */
    public function query(array $params): array
    {
        $sql = $params['query'] ?? $params['sql'] ?? '';
        $bindings = $params['bindings'] ?? [];
        
        // Security: Only allow SELECT queries
        if (!$this->isReadOnlyQuery($sql)) {
            return ['error' => 'Only SELECT queries are allowed'];
        }
        
        // Security: Check for allowed tables
        if (!$this->queryUsesAllowedTables($sql)) {
            return ['error' => 'Query contains unauthorized tables'];
        }
        
        try {
            // Start read-only transaction
            DB::beginTransaction();
            
            // Set session to read-only
            DB::statement('SET SESSION TRANSACTION READ ONLY');
            
            // Execute query with limit
            $sql = $this->addLimitIfMissing($sql);
            $results = DB::select($sql, $bindings);
            
            DB::commit();
            
            // Reset transaction mode
            DB::statement('SET SESSION TRANSACTION READ WRITE');
            
            return [
                'data' => $results,
                'count' => count($results),
                'executed_at' => now()->toIso8601String()
            ];
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            // Reset transaction mode
            DB::statement('SET SESSION TRANSACTION READ WRITE');
            
            Log::error('MCP Database query error', [
                'sql' => $sql,
                'error' => $e->getMessage()
            ]);
            
            return [
                'error' => 'Query execution failed',
                'message' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Get recent appointments with issues
     */
    public function getFailedAppointments(array $params = []): array
    {
        $hours = $params['hours'] ?? 24;
        $limit = min($params['limit'] ?? 100, $this->config['limits']['max_rows']);
        
        $sql = "
            SELECT 
                a.id,
                a.appointment_date,
                a.status,
                a.error_message,
                c.name as customer_name,
                c.phone as customer_phone,
                b.name as branch_name,
                s.name as service_name
            FROM appointments a
            LEFT JOIN customers c ON a.customer_id = c.id
            LEFT JOIN branches b ON a.branch_id = b.id
            LEFT JOIN services s ON a.service_id = s.id
            WHERE a.created_at >= DATE_SUB(NOW(), INTERVAL ? HOUR)
            AND (a.status = 'failed' OR a.error_message IS NOT NULL)
            ORDER BY a.created_at DESC
            LIMIT ?
        ";
        
        return $this->query(['query' => $sql, 'bindings' => [$hours, $limit]]);
    }
    
    /**
     * Get call statistics
     */
    public function getCallStats(array $params = []): array
    {
        $days = $params['days'] ?? 7;
        
        $sql = "
            SELECT 
                DATE(created_at) as date,
                COUNT(*) as total_calls,
                SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_calls,
                SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as failed_calls,
                AVG(duration_seconds) as avg_duration,
                SUM(cost) as total_cost
            FROM calls
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
            GROUP BY DATE(created_at)
            ORDER BY date DESC
        ";
        
        return $this->query(['query' => $sql, 'bindings' => [$days]]);
    }
    
    /**
     * Search across multiple tables
     */
    public function search(array $params): array
    {
        $searchTerm = $params['searchTerm'] ?? $params['query'] ?? '';
        $tables = $params['tables'] ?? [];
        
        $results = [];
        $tables = empty($tables) ? ['customers', 'appointments', 'calls'] : $tables;
        
        foreach ($tables as $table) {
            if (!in_array($table, $this->allowedTables)) {
                continue;
            }
            
            $results[$table] = $this->searchTable($table, $searchTerm);
        }
        
        return [
            'search_term' => $searchTerm,
            'results' => $results,
            'searched_at' => now()->toIso8601String()
        ];
    }
    
    /**
     * Get tenant statistics
     */
    public function getTenantStats(array $params = []): array
    {
        $companyId = $params['company_id'] ?? $params['companyId'] ?? null;
        $sql = "
            SELECT 
                c.id,
                c.name,
                COUNT(DISTINCT b.id) as branch_count,
                COUNT(DISTINCT cu.id) as customer_count,
                COUNT(DISTINCT a.id) as appointment_count,
                COUNT(DISTINCT ca.id) as call_count
            FROM companies c
            LEFT JOIN branches b ON b.company_id = c.id
            LEFT JOIN customers cu ON cu.company_id = c.id
            LEFT JOIN appointments a ON a.company_id = c.id
            LEFT JOIN calls ca ON ca.company_id = c.id
        ";
        
        if ($companyId) {
            $sql .= " WHERE c.id = ?";
            $sql .= " GROUP BY c.id, c.name";
            return $this->query(['query' => $sql, 'bindings' => [$companyId]]);
        }
        
        $sql .= " GROUP BY c.id, c.name LIMIT 20";
        return $this->query(['query' => $sql, 'bindings' => []]);
    }
    
    /**
     * Get table columns
     */
    protected function getTableColumns(string $table): array
    {
        return array_map(function ($column) {
            return [
                'name' => $column->Field,
                'type' => $column->Type,
                'nullable' => $column->Null === 'YES',
                'key' => $column->Key,
                'default' => $column->Default,
                'extra' => $column->Extra
            ];
        }, DB::select("SHOW COLUMNS FROM " . self::quoteIdentifier($table)));
    }
    
    /**
     * Get table indexes
     */
    protected function getTableIndexes(string $table): array
    {
        return array_map(function ($index) {
            return [
                'name' => $index->Key_name,
                'column' => $index->Column_name,
                'unique' => !$index->Non_unique,
                'type' => $index->Index_type
            ];
        }, DB::select("SHOW INDEX FROM " . self::quoteIdentifier($table)));
    }
    
    /**
     * Get table row count
     */
    protected function getTableRowCount(string $table): int
    {
        return DB::table($table)->count();
    }
    
    /**
     * Check if query is read-only
     */
    protected function isReadOnlyQuery(string $sql): bool
    {
        $sql = strtoupper(trim($sql));
        return strpos($sql, 'SELECT') === 0 || strpos($sql, 'SHOW') === 0;
    }
    
    /**
     * Check if query uses only allowed tables
     */
    protected function queryUsesAllowedTables(string $sql): bool
    {
        // Extract table names from query
        preg_match_all('/(?:FROM|JOIN)\s+`?(\w+)`?/i', $sql, $matches);
        
        if (empty($matches[1])) {
            return true; // No tables found, likely a simple query
        }
        
        foreach ($matches[1] as $table) {
            if (!in_array($table, $this->allowedTables)) {
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * Add LIMIT clause if missing
     */
    protected function addLimitIfMissing(string $sql): string
    {
        if (!preg_match('/LIMIT\s+\d+/i', $sql)) {
            $sql .= ' LIMIT ' . $this->config['limits']['max_rows'];
        }
        return $sql;
    }
    
    /**
     * Search within a table
     */
    protected function searchTable(string $table, string $searchTerm): array
    {
        // Get searchable columns (text-like columns)
        $columns = $this->getTableColumns($table);
        $searchableColumns = array_filter($columns, function ($col) {
            return strpos($col['type'], 'varchar') !== false || 
                   strpos($col['type'], 'text') !== false;
        });
        
        if (empty($searchableColumns)) {
            return [];
        }
        
        // Build search query
        $conditions = array_map(function ($col) {
            return "`{$col['name']}` LIKE ?";
        }, $searchableColumns);
        
        $sql = "SELECT * FROM `$table` WHERE " . implode(' OR ', $conditions) . " LIMIT 20";
        $bindings = array_fill(0, count($conditions), "%$searchTerm%");
        
        $result = $this->query(['query' => $sql, 'bindings' => $bindings]);
        
        return $result['data'] ?? [];
    }
    
    /**
     * Generate cache key
     */
    protected function getCacheKey(string $type, array $params = []): string
    {
        $prefix = $this->config['cache']['prefix'];
        $key = "{$prefix}:{$type}";
        
        if (!empty($params)) {
            $key .= ':' . md5(json_encode($params));
        }
        
        return $key;
    }
    
    /**
     * Clear cache
     */
    public function clearCache(): void
    {
        Cache::flush();
    }
    
    /**
     * Get phone to branch mapping with caching
     */
    public function getPhoneBranchMapping(array $params = []): array
    {
        $cacheKey = $this->getCacheKey('phone_branch_mapping', $params);
        
        return Cache::remember($cacheKey, $this->config['cache']['ttl'], function () use ($params) {
            $sql = "
                SELECT 
                    pn.phone_number,
                    pn.type,
                    pn.is_active as phone_active,
                    b.id as branch_id,
                    b.uuid as branch_uuid,
                    b.name as branch_name,
                    b.is_active as branch_active,
                    b.calcom_event_type_id,
                    b.retell_agent_id,
                    c.id as company_id,
                    c.name as company_name,
                    c.is_active as company_active,
                    cet.title as event_type_title,
                    cet.slug as event_type_slug
                FROM phone_numbers pn
                INNER JOIN branches b ON pn.branch_id = b.id
                INNER JOIN companies c ON b.company_id = c.id
                LEFT JOIN calcom_event_types cet ON b.calcom_event_type_id = cet.id
                WHERE pn.is_active = 1
            ";
            
            $bindings = [];
            
            // Add optional filters
            if (!empty($params['phone_number'])) {
                $sql .= " AND pn.phone_number = ?";
                $bindings[] = $params['phone_number'];
            }
            
            if (!empty($params['company_id'])) {
                $sql .= " AND c.id = ?";
                $bindings[] = $params['company_id'];
            }
            
            if (!empty($params['branch_id'])) {
                $sql .= " AND b.id = ?";
                $bindings[] = $params['branch_id'];
            }
            
            // Only active branches and companies by default
            if (!isset($params['include_inactive']) || !$params['include_inactive']) {
                $sql .= " AND b.is_active = 1 AND c.is_active = 1";
            }
            
            $sql .= " ORDER BY c.name, b.name, pn.phone_number";
            
            $result = $this->query(['query' => $sql, 'bindings' => $bindings]);
            
            if (isset($result['error'])) {
                return $result;
            }
            
            // Group by phone number for easier lookup
            $mapping = [];
            foreach ($result['data'] ?? [] as $row) {
                $mapping[$row->phone_number] = [
                    'phone_number' => $row->phone_number,
                    'type' => $row->type,
                    'is_active' => (bool)$row->phone_active,
                    'branch' => [
                        'id' => $row->branch_id,
                        'uuid' => $row->branch_uuid,
                        'name' => $row->branch_name,
                        'is_active' => (bool)$row->branch_active,
                        'calcom_event_type_id' => $row->calcom_event_type_id,
                        'retell_agent_id' => $row->retell_agent_id,
                    ],
                    'company' => [
                        'id' => $row->company_id,
                        'name' => $row->company_name,
                        'is_active' => (bool)$row->company_active,
                    ],
                    'event_type' => $row->calcom_event_type_id ? [
                        'id' => $row->calcom_event_type_id,
                        'title' => $row->event_type_title,
                        'slug' => $row->event_type_slug,
                    ] : null,
                ];
            }
            
            return [
                'mapping' => $mapping,
                'total' => count($mapping),
                'generated_at' => now()->toIso8601String(),
            ];
        });
    }
}
<?php

return [
    /*
    |--------------------------------------------------------------------------
    | MCP Database Server Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for the Model Context Protocol (MCP) Database Server
    | that provides read-only access to the database for AI assistants.
    |
    */

    'cache' => [
        'ttl' => env('MCP_DB_CACHE_TTL', 300), // 5 minutes
        'prefix' => 'mcp:db',
    ],

    'limits' => [
        'max_rows' => env('MCP_DB_MAX_ROWS', 1000),
        'max_tables' => env('MCP_DB_MAX_TABLES', 50),
    ],

    'read_only' => env('MCP_DB_READ_ONLY', true),

    /*
    |--------------------------------------------------------------------------
    | Allowed Tables
    |--------------------------------------------------------------------------
    |
    | List of tables that can be accessed via MCP. This is a security
    | measure to prevent access to sensitive tables.
    |
    */
    'allowed_tables' => [
        'appointments',
        'calls',
        'customers',
        'companies',
        'branches',
        'staff',
        'services',
        'calcom_event_types',
        'staff_event_types',
        'phone_numbers',
        'webhook_events',
        'api_call_logs',
        'calcom_bookings',
        'working_hours',
    ],

    /*
    |--------------------------------------------------------------------------
    | Excluded Columns
    |--------------------------------------------------------------------------
    |
    | Columns that should never be returned in query results.
    |
    */
    'excluded_columns' => [
        'password',
        'remember_token',
        'api_token',
        'two_factor_secret',
        'two_factor_recovery_codes',
    ],

    /*
    |--------------------------------------------------------------------------
    | Query Templates
    |--------------------------------------------------------------------------
    |
    | Pre-defined query templates for common operations.
    |
    */
    'query_templates' => [
        'failed_appointments' => "
            SELECT a.*, c.name as customer_name, b.name as branch_name
            FROM appointments a
            LEFT JOIN customers c ON a.customer_id = c.id
            LEFT JOIN branches b ON a.branch_id = b.id
            WHERE a.status = 'failed' 
            AND a.created_at >= DATE_SUB(NOW(), INTERVAL :hours HOUR)
            ORDER BY a.created_at DESC
            LIMIT :limit
        ",
        
        'call_summary' => "
            SELECT 
                DATE(created_at) as date,
                COUNT(*) as total_calls,
                AVG(duration_seconds) as avg_duration,
                SUM(cost) as total_cost
            FROM calls
            WHERE company_id = :company_id
            AND created_at >= DATE_SUB(NOW(), INTERVAL :days DAY)
            GROUP BY DATE(created_at)
            ORDER BY date DESC
        ",
    ],
];
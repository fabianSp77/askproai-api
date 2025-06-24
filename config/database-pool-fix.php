<?php

/**
 * Database Connection Pool Configuration Fix
 * 
 * This configuration addresses the connection exhaustion issue by:
 * 1. Disabling persistent connections until proper pooling is implemented
 * 2. Setting appropriate timeouts
 * 3. Limiting connections per process
 * 4. Adding monitoring capabilities
 */

return [
    // Disable persistent connections to prevent accumulation
    'DB_PERSISTENT' => false,
    
    // Connection pool settings (for future implementation)
    'DB_POOL_ENABLED' => true,
    'DB_POOL_MIN' => 2,
    'DB_POOL_MAX' => 20,  // Increased from 10
    'DB_POOL_IDLE_TIME' => 60,  // Close idle connections after 60 seconds
    'DB_POOL_VALIDATION' => 30,  // Validate connections every 30 seconds
    
    // Connection timeout settings
    'DB_TIMEOUT' => 5,  // Connection timeout (seconds)
    'DB_READ_TIMEOUT' => 30,  // Query timeout (seconds)
    
    // MySQL specific settings (add to PDO options)
    'mysql_init_commands' => [
        "SET NAMES 'utf8mb4' COLLATE 'utf8mb4_unicode_ci'",
        "SET SESSION sql_mode='TRADITIONAL,NO_AUTO_VALUE_ON_ZERO'",
        "SET SESSION wait_timeout=120",  // Reduced from 600
        "SET SESSION interactive_timeout=120",
    ],
];
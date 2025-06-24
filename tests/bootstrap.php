<?php

// Increase memory limit for tests
ini_set("memory_limit", "512M");

// Set longer execution time
set_time_limit(300);

// Disable Xdebug for faster tests
if (function_exists("xdebug_disable")) {
    xdebug_disable();
}

// Ensure we're in testing environment
putenv("APP_ENV=testing");
putenv("CACHE_DRIVER=array");
putenv("SESSION_DRIVER=array");
putenv("QUEUE_CONNECTION=sync");
putenv("MAIL_MAILER=array");
putenv("BROADCAST_DRIVER=log");

// Disable MCP services during testing
putenv("MCP_ENABLED=false");
putenv("DB_CONNECTION_POOLING=false");

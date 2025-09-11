-- MySQL Performance Optimization for AskProAI
-- Execute these settings to optimize MySQL for Laravel application

-- General performance settings
SET GLOBAL innodb_buffer_pool_size = 2G;
SET GLOBAL innodb_log_file_size = 256M;
SET GLOBAL innodb_log_buffer_size = 64M;
SET GLOBAL innodb_flush_log_at_trx_commit = 2;
SET GLOBAL innodb_flush_method = O_DIRECT;

-- Thread and connection settings
SET GLOBAL max_connections = 500;
SET GLOBAL thread_cache_size = 50;
SET GLOBAL table_open_cache = 4000;
SET GLOBAL table_definition_cache = 2000;

-- Query cache (if available in your MySQL version)
SET GLOBAL query_cache_type = 1;
SET GLOBAL query_cache_size = 256M;
SET GLOBAL query_cache_limit = 2M;

-- MyISAM settings (for temporary tables)
SET GLOBAL key_buffer_size = 128M;
SET GLOBAL myisam_sort_buffer_size = 64M;

-- InnoDB specific optimizations
SET GLOBAL innodb_file_per_table = 1;
SET GLOBAL innodb_open_files = 2000;
SET GLOBAL innodb_io_capacity = 2000;
SET GLOBAL innodb_io_capacity_max = 4000;
SET GLOBAL innodb_read_io_threads = 8;
SET GLOBAL innodb_write_io_threads = 8;

-- Sort and join optimizations
SET GLOBAL sort_buffer_size = 4M;
SET GLOBAL join_buffer_size = 4M;
SET GLOBAL read_buffer_size = 2M;
SET GLOBAL read_rnd_buffer_size = 4M;

-- Temporary table optimization
SET GLOBAL tmp_table_size = 256M;
SET GLOBAL max_heap_table_size = 256M;

-- Binary log settings (if using replication)
SET GLOBAL binlog_cache_size = 4M;
SET GLOBAL max_binlog_cache_size = 128M;

-- Performance schema (enable for monitoring)
SET GLOBAL performance_schema = 1;

-- Slow query log
SET GLOBAL slow_query_log = 1;
SET GLOBAL long_query_time = 2;
SET GLOBAL log_queries_not_using_indexes = 1;

-- Create optimized my.cnf settings
-- Copy to /etc/mysql/mysql.conf.d/performance.cnf

/*
[mysqld]
# General performance
innodb_buffer_pool_size = 2G
innodb_log_file_size = 256M
innodb_log_buffer_size = 64M
innodb_flush_log_at_trx_commit = 2
innodb_flush_method = O_DIRECT

# Connection settings
max_connections = 500
thread_cache_size = 50
table_open_cache = 4000
table_definition_cache = 2000

# Query optimization
query_cache_type = 1
query_cache_size = 256M
query_cache_limit = 2M

# InnoDB optimization
innodb_file_per_table = 1
innodb_open_files = 2000
innodb_io_capacity = 2000
innodb_io_capacity_max = 4000
innodb_read_io_threads = 8
innodb_write_io_threads = 8

# Buffer sizes
sort_buffer_size = 4M
join_buffer_size = 4M
read_buffer_size = 2M
read_rnd_buffer_size = 4M
tmp_table_size = 256M
max_heap_table_size = 256M

# Logging
slow_query_log = 1
long_query_time = 2
log_queries_not_using_indexes = 1
slow_query_log_file = /var/log/mysql/slow.log

# Binary logging
binlog_cache_size = 4M
max_binlog_cache_size = 128M

# Character set
character_set_server = utf8mb4
collation_server = utf8mb4_unicode_ci
*/
<?php

namespace App\Database;

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * Base migration class that handles database compatibility issues
 * Specifically addresses:
 * - SQLite compatibility for testing
 * - Duplicate table creation prevention
 * - JSON field handling across different databases
 */
abstract class CompatibleMigration extends Migration
{
    /**
     * Check if we're using SQLite (typically in tests)
     */
    protected function isSQLite(): bool
    {
        return DB::getDriverName() === 'sqlite';
    }
    
    /**
     * Check if we're using MySQL/MariaDB
     */
    protected function isMySQL(): bool
    {
        return in_array(DB::getDriverName(), ['mysql', 'mariadb']);
    }
    
    /**
     * Create a table only if it doesn't exist
     * This prevents duplicate table creation errors
     */
    protected function createTableIfNotExists(string $tableName, callable $callback): void
    {
        if (!Schema::hasTable($tableName)) {
            Schema::create($tableName, $callback);
        }
    }
    
    /**
     * Drop a table if it exists
     */
    protected function dropTableIfExists(string $tableName): void
    {
        Schema::dropIfExists($tableName);
    }
    
    /**
     * Add a JSON column with proper database compatibility
     * @return \Illuminate\Database\Schema\ColumnDefinition
     */
    protected function addJsonColumn(Blueprint $table, string $columnName, bool $nullable = true)
    {
        if ($this->isSQLite()) {
            // SQLite doesn't support JSON columns, use TEXT instead
            $column = $table->text($columnName);
        } else {
            // MySQL/PostgreSQL support JSON columns
            $column = $table->json($columnName);
        }
        
        if ($nullable) {
            $column->nullable();
        }
        
        // MySQL doesn't support default values for JSON columns in some versions
        if (!$this->isMySQL() && !$this->isSQLite()) {
            $column->default('{}');
        }
        
        return $column;
    }
    
    /**
     * Change a column to JSON with proper compatibility
     */
    protected function changeToJsonColumn(string $tableName, string $columnName, bool $nullable = true): void
    {
        if ($this->isSQLite()) {
            // SQLite can't change column types easily, just ensure it exists
            if (!Schema::hasColumn($tableName, $columnName)) {
                Schema::table($tableName, function (Blueprint $table) use ($columnName, $nullable) {
                    $column = $table->text($columnName);
                    if ($nullable) {
                        $column->nullable();
                    }
                });
            }
        } else {
            Schema::table($tableName, function (Blueprint $table) use ($columnName, $nullable) {
                $column = $table->json($columnName);
                if ($nullable) {
                    $column->nullable();
                }
                $column->change();
            });
        }
    }
    
    /**
     * Set default JSON value for existing records
     */
    protected function setJsonDefaults(string $tableName, string $columnName, $defaultValue = []): void
    {
        $jsonString = json_encode($defaultValue);
        
        if ($this->isSQLite()) {
            DB::update("UPDATE {$tableName} SET {$columnName} = ? WHERE {$columnName} IS NULL OR {$columnName} = ''", [$jsonString]);
        } else {
            DB::table($tableName)
                ->whereNull($columnName)
                ->orWhere($columnName, '')
                ->update([$columnName => $jsonString]);
        }
    }
    
    /**
     * Add a column only if it doesn't exist
     */
    protected function addColumnIfNotExists(string $tableName, string $columnName, callable $callback): void
    {
        if (!Schema::hasColumn($tableName, $columnName)) {
            Schema::table($tableName, function (Blueprint $table) use ($callback) {
                $callback($table);
            });
        }
    }
    
    /**
     * Drop a column if it exists
     */
    protected function dropColumnIfExists(string $tableName, string $columnName): void
    {
        if (Schema::hasColumn($tableName, $columnName)) {
            Schema::table($tableName, function (Blueprint $table) use ($columnName) {
                $table->dropColumn($columnName);
            });
        }
    }
    
    /**
     * Add an index only if it doesn't exist
     */
    protected function addIndexIfNotExists(string $tableName, $columns, string $indexName = null): void
    {
        $columns = (array) $columns;
        $indexName = $indexName ?: $tableName . '_' . implode('_', $columns) . '_index';
        
        if ($this->isSQLite()) {
            // SQLite has different way to check indexes
            $indexes = DB::select("PRAGMA index_list($tableName)");
            $indexExists = false;
            foreach ($indexes as $index) {
                if ($index->name === $indexName) {
                    $indexExists = true;
                    break;
                }
            }
        } else {
            // MySQL/PostgreSQL
            $indexExists = DB::select(
                "SELECT * FROM information_schema.statistics WHERE table_schema = ? AND table_name = ? AND index_name = ?",
                [DB::getDatabaseName(), $tableName, $indexName]
            );
            $indexExists = !empty($indexExists);
        }
        
        if (!$indexExists) {
            Schema::table($tableName, function (Blueprint $table) use ($columns, $indexName) {
                $table->index($columns, $indexName);
            });
        }
    }
    
    /**
     * Drop an index if it exists
     */
    protected function dropIndexIfExists(string $tableName, string $indexName): void
    {
        try {
            Schema::table($tableName, function (Blueprint $table) use ($indexName) {
                $table->dropIndex($indexName);
            });
        } catch (\Exception $e) {
            // Index doesn't exist, ignore
        }
    }
    
    /**
     * Check if an index exists
     */
    protected function indexExists(string $tableName, string $indexName): bool
    {
        if ($this->isSQLite()) {
            // SQLite has different way to check indexes
            try {
                $indexes = DB::select("PRAGMA index_list($tableName)");
                foreach ($indexes as $index) {
                    if ($index->name === $indexName) {
                        return true;
                    }
                }
                return false;
            } catch (\Exception $e) {
                return false;
            }
        } else {
            // MySQL/PostgreSQL
            try {
                $result = DB::select(
                    "SELECT * FROM information_schema.statistics WHERE table_schema = ? AND table_name = ? AND index_name = ?",
                    [DB::getDatabaseName(), $tableName, $indexName]
                );
                return !empty($result);
            } catch (\Exception $e) {
                return false;
            }
        }
    }
    
    /**
     * Add a foreign key constraint with proper database compatibility
     * SQLite doesn't support adding foreign keys after table creation
     */
    protected function addForeignKey(Blueprint $table, string $columnName, string $referencedTable, string $referencedColumn = 'id'): void
    {
        if (!$this->isSQLite()) {
            $table->foreign($columnName)->references($referencedColumn)->on($referencedTable);
        }
    }
    
    /**
     * Drop a foreign key constraint with proper database compatibility
     */
    protected function dropForeignKey(string $tableName, string $foreignKeyName): void
    {
        if (!$this->isSQLite()) {
            Schema::table($tableName, function (Blueprint $table) use ($foreignKeyName) {
                $table->dropForeign($foreignKeyName);
            });
        }
    }
    
    /**
     * Add a fulltext index with proper database compatibility
     * SQLite doesn't support fulltext indexes in the same way
     */
    protected function addFullTextIndex(Blueprint $table, $columns, string $indexName = null): void
    {
        if (!$this->isSQLite()) {
            $table->fullText($columns, $indexName);
        }
        // For SQLite, we just skip fulltext indexes as they're not supported
        // Alternative would be to use FTS5 virtual tables, but that's complex
    }
    
    /**
     * Add a fulltext index to an existing table
     */
    protected function addFullTextIndexToTable(string $tableName, $columns, string $indexName = null): void
    {
        if (!$this->isSQLite()) {
            Schema::table($tableName, function (Blueprint $table) use ($columns, $indexName) {
                $table->fullText($columns, $indexName);
            });
        }
    }
}
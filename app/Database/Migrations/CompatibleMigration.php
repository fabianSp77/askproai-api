<?php

namespace App\Database\Migrations;

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Base migration class that provides database-agnostic methods
 * for common operations that differ between MySQL and SQLite
 */
abstract class CompatibleMigration extends Migration
{
    /**
     * Check if running in test environment with SQLite
     */
    protected function isSqlite(): bool
    {
        return config('database.default') === 'sqlite';
    }
    
    /**
     * Get list of tables in database-agnostic way
     */
    protected function getTables(): array
    {
        if ($this->isSqlite()) {
            $tables = DB::select("SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%'");
            return collect($tables)->pluck('name')->toArray();
        }
        
        // MySQL/MariaDB
        $tables = DB::select('SHOW TABLES');
        $key = 'Tables_in_' . config('database.connections.mysql.database');
        return collect($tables)->pluck($key)->toArray();
    }
    
    /**
     * Check if an index exists in database-agnostic way
     */
    protected function indexExists(string $table, string $index): bool
    {
        if ($this->isSqlite()) {
            try {
                $indexes = DB::select("PRAGMA index_list('{$table}')");
                foreach ($indexes as $idx) {
                    if ($idx->name === $index) {
                        return true;
                    }
                }
                return false;
            } catch (\Exception $e) {
                return false;
            }
        }
        
        // MySQL/MariaDB
        try {
            $indexes = DB::select("SHOW INDEX FROM `{$table}`");
            foreach ($indexes as $idx) {
                if ($idx->Key_name === $index) {
                    return true;
                }
            }
            return false;
        } catch (\Exception $e) {
            return false;
        }
    }
    
    /**
     * Get existing indexes for a table
     */
    protected function getIndexes(string $table): array
    {
        if ($this->isSqlite()) {
            try {
                $indexes = DB::select("PRAGMA index_list('{$table}')");
                return collect($indexes)->pluck('name')->toArray();
            } catch (\Exception $e) {
                return [];
            }
        }
        
        // MySQL/MariaDB
        try {
            $indexes = DB::select("SHOW INDEXES FROM `{$table}`");
            return collect($indexes)->pluck('Key_name')->unique()->toArray();
        } catch (\Exception $e) {
            return [];
        }
    }
    
    /**
     * Add foreign key constraint (skip in SQLite tests)
     */
    protected function addForeignKey($table, $column, $referencedTable, $referencedColumn = 'id', $onDelete = 'cascade'): void
    {
        if ($this->isSqlite()) {
            // SQLite has limited foreign key support in tests
            return;
        }
        
        Schema::table($table, function ($blueprint) use ($column, $referencedTable, $referencedColumn, $onDelete) {
            $blueprint->foreign($column)
                ->references($referencedColumn)
                ->on($referencedTable)
                ->onDelete($onDelete);
        });
    }
    
    /**
     * Drop foreign key constraint (skip in SQLite tests)
     */
    protected function dropForeignKey($table, $column): void
    {
        if ($this->isSqlite()) {
            return;
        }
        
        Schema::table($table, function ($blueprint) use ($column) {
            $blueprint->dropForeign([$column]);
        });
    }
    
    /**
     * Modify column (handle SQLite limitations)
     */
    protected function modifyColumn($table, $column, $type, $attributes = []): void
    {
        if ($this->isSqlite()) {
            // SQLite doesn't support direct column modifications
            // Would need to recreate table - skip in tests
            return;
        }
        
        Schema::table($table, function ($blueprint) use ($column, $type, $attributes) {
            $col = $blueprint->$type($column);
            
            foreach ($attributes as $method => $value) {
                if ($value === true) {
                    $col->$method();
                } else {
                    $col->$method($value);
                }
            }
            
            $col->change();
        });
    }
    
    /**
     * Set column to NOT NULL (SQLite compatible)
     */
    protected function setColumnNotNull($table, $column): void
    {
        if ($this->isSqlite()) {
            // SQLite requires table recreation for NOT NULL changes
            return;
        }
        
        DB::statement("ALTER TABLE `{$table}` MODIFY `{$column}` VARCHAR(255) NOT NULL");
    }
    
    /**
     * Check if column has specific type
     */
    protected function columnHasType($table, $column, $type): bool
    {
        if ($this->isSqlite()) {
            // SQLite type checking is complex, skip in tests
            return true;
        }
        
        $result = DB::select("SHOW COLUMNS FROM `{$table}` WHERE Field = ?", [$column]);
        if (empty($result)) {
            return false;
        }
        
        return stripos($result[0]->Type, $type) !== false;
    }
}
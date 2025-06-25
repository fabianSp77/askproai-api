<?php

namespace App\Database;

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

abstract class SafeMigration extends Migration
{
    /**
     * Run the migrations with transaction support
     */
    public function up(): void
    {
        // Skip transactions for operations that don't support them
        if ($this->shouldUseTransaction()) {
            DB::transaction(function () {
                $this->safeUp();
            });
        } else {
            $this->safeUp();
        }
    }
    
    /**
     * Reverse the migrations with transaction support
     */
    public function down(): void
    {
        if ($this->shouldUseTransaction()) {
            DB::transaction(function () {
                $this->safeDown();
            });
        } else {
            $this->safeDown();
        }
    }
    
    /**
     * Check if migration should use transaction
     */
    protected function shouldUseTransaction(): bool
    {
        // SQLite doesn't support DDL transactions
        if (config('database.default') === 'sqlite') {
            return false;
        }
        
        // Check for operations that can't be transactional
        $nonTransactionalKeywords = [
            'CREATE INDEX',
            'DROP INDEX',
            'ALTER TABLE.*ADD FULLTEXT',
        ];
        
        $migrationContent = file_get_contents((new \ReflectionClass($this))->getFileName());
        
        foreach ($nonTransactionalKeywords as $keyword) {
            if (preg_match('/' . $keyword . '/i', $migrationContent)) {
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * Run the migration operations
     */
    abstract protected function safeUp(): void;
    
    /**
     * Reverse the migration operations
     */
    abstract protected function safeDown(): void;
}
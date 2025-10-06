<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('customer_notes')) {
            Schema::table('customer_notes', function (Blueprint $table) {
                // Composite index for filtering important + pinned notes with date sorting
                // Optimizes dashboard queries showing important/pinned notes
                if (!$this->indexExists('customer_notes', 'idx_customer_notes_important_pinned_date')) {
                    $table->index(['is_important', 'is_pinned', 'created_at'], 'idx_customer_notes_important_pinned_date');
                }

                // Composite index for type + category filtering with date
                // Optimizes filtered list views (e.g., "show all support complaints")
                if (!$this->indexExists('customer_notes', 'idx_customer_notes_type_category_date')) {
                    $table->index(['type', 'category', 'created_at'], 'idx_customer_notes_type_category_date');
                }

                // Composite index for visibility filtering by customer
                // Optimizes queries showing customer-specific notes with visibility rules
                if (!$this->indexExists('customer_notes', 'idx_customer_notes_customer_visibility')) {
                    $table->index(['customer_id', 'visibility', 'created_at'], 'idx_customer_notes_customer_visibility');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('customer_notes')) {
            Schema::table('customer_notes', function (Blueprint $table) {
                if ($this->indexExists('customer_notes', 'idx_customer_notes_important_pinned_date')) {
                    $table->dropIndex('idx_customer_notes_important_pinned_date');
                }
                if ($this->indexExists('customer_notes', 'idx_customer_notes_type_category_date')) {
                    $table->dropIndex('idx_customer_notes_type_category_date');
                }
                if ($this->indexExists('customer_notes', 'idx_customer_notes_customer_visibility')) {
                    $table->dropIndex('idx_customer_notes_customer_visibility');
                }
            });
        }
    }

    /**
     * Check if index exists
     */
    private function indexExists($table, $index): bool
    {
        $indexes = Schema::getIndexes($table);
        return collect($indexes)->pluck('name')->contains($index);
    }
};

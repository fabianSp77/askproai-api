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
        
        if (!Schema::hasTable('services')) {
            return;
        }

        Schema::table('services', function (Blueprint $table) {
            // Assignment metadata fields
            $table->enum('assignment_method', ['manual', 'auto', 'import', 'suggested'])->nullable();
            $table->decimal('assignment_confidence', 5, 2)->nullable();
            $table->text('assignment_notes')->nullable();
            $table->timestamp('assignment_date')->nullable();
            $table->unsignedBigInteger('assigned_by')->nullable();

            // Add indexes for better query performance
            $table->index('assignment_method');
            $table->index('assignment_confidence');
            $table->index('assignment_date');

            // Foreign key for user who assigned
            $table->foreign('assigned_by')->references('id')->on('users')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('services', function (Blueprint $table) {
            // Drop foreign key first
            $table->dropForeign(['assigned_by']);

            // Drop indexes
            $table->dropIndex(['assignment_method']);
            $table->dropIndex(['assignment_confidence']);
            $table->dropIndex(['assignment_date']);

            // Drop columns
            $table->dropColumn([
                'assignment_method',
                'assignment_confidence',
                'assignment_notes',
                'assignment_date',
                'assigned_by'
            ]);
        });
    }
};

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
        // Add missing columns to customer_notes table
        
        if (!Schema::hasTable('customer_notes')) {
            return;
        }

        Schema::table('customer_notes', function (Blueprint $table) {
            if (!Schema::hasColumn('customer_notes', 'subject')) {
                $table->string('subject')->nullable();
            }
            if (!Schema::hasColumn('customer_notes', 'type')) {
                $table->string('type', 50)->default('general');
            }
            if (!Schema::hasColumn('customer_notes', 'visibility')) {
                $table->string('visibility', 50)->default('public');
            }
            if (!Schema::hasColumn('customer_notes', 'is_pinned')) {
                $table->boolean('is_pinned')->default(false);
            }

            // Add indexes for better performance
            if (!Schema::hasIndex('customer_notes', 'customer_notes_type_index')) {
                $table->index('type');
            }
            if (!Schema::hasIndex('customer_notes', 'customer_notes_visibility_index')) {
                $table->index('visibility');
            }
            if (!Schema::hasIndex('customer_notes', 'customer_notes_is_pinned_index')) {
                $table->index('is_pinned');
            }
        });

        // Ensure appointments table has proper columns
        
        if (!Schema::hasTable('appointments')) {
            return;
        }

        Schema::table('appointments', function (Blueprint $table) {
            if (!Schema::hasColumn('appointments', 'company_id')) {
                $table->unsignedBigInteger('company_id')->nullable();
                $table->index('company_id');
            }
        });

        // Ensure calls table has proper columns
        
        if (!Schema::hasTable('calls')) {
            return;
        }

        Schema::table('calls', function (Blueprint $table) {
            if (!Schema::hasColumn('calls', 'call_time')) {
                $table->timestamp('call_time')->nullable();
            }
            if (!Schema::hasColumn('calls', 'session_outcome')) {
                $table->string('session_outcome', 50)->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('customer_notes', function (Blueprint $table) {
            $table->dropColumn(['subject', 'type', 'visibility', 'is_pinned']);
        });

        Schema::table('appointments', function (Blueprint $table) {
            if (Schema::hasColumn('appointments', 'company_id')) {
                $table->dropColumn('company_id');
            }
        });

        Schema::table('calls', function (Blueprint $table) {
            if (Schema::hasColumn('calls', 'call_time')) {
                $table->dropColumn('call_time');
            }
            if (Schema::hasColumn('calls', 'session_outcome')) {
                $table->dropColumn('session_outcome');
            }
        });
    }
};
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
        Schema::table('notifications', function (Blueprint $table) {
            // Add new columns if they don't exist
            if (!Schema::hasColumn('notifications', 'category')) {
                $table->string('category', 50)->nullable()->after('type');
            }
            
            if (!Schema::hasColumn('notifications', 'priority')) {
                $table->enum('priority', ['low', 'medium', 'high', 'urgent'])->default('medium')->after('category');
            }
            
            if (!Schema::hasColumn('notifications', 'expires_at')) {
                $table->timestamp('expires_at')->nullable()->after('read_at');
            }
            
            if (!Schema::hasColumn('notifications', 'action_url')) {
                $table->string('action_url')->nullable()->after('data');
            }
            
            if (!Schema::hasColumn('notifications', 'action_text')) {
                $table->string('action_text')->nullable()->after('action_url');
            }
            
            // Add indexes
            $table->index(['notifiable_type', 'notifiable_id', 'read_at']);
            $table->index(['category', 'created_at']);
            $table->index('priority');
            $table->index('expires_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('notifications', function (Blueprint $table) {
            // Drop indexes
            $table->dropIndex(['notifiable_type', 'notifiable_id', 'read_at']);
            $table->dropIndex(['category', 'created_at']);
            $table->dropIndex(['priority']);
            $table->dropIndex(['expires_at']);
            
            // Drop columns
            $table->dropColumn(['category', 'priority', 'expires_at', 'action_url', 'action_text']);
        });
    }
};
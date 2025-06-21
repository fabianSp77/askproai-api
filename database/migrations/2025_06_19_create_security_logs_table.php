<?php

use App\Database\CompatibleMigration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends CompatibleMigration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('security_logs', function (Blueprint $table) {
            $table->id();
            $table->string('type')->index(); // failed_login, suspicious_activity, etc.
            $table->string('ip_address')->index();
            $table->text('user_agent')->nullable();
            $table->foreignId('user_id')->nullable()->constrained();
            $table->foreignId('company_id')->nullable()->constrained();
            $table->string('url');
            $table->string('method', 10);
            $this->addJsonColumn($table, 'data', true);
            $table->timestamp('created_at')->index();
            $table->timestamp('updated_at')->nullable();
            
            // Composite indexes for common queries
            $table->index(['type', 'created_at']);
            $table->index(['ip_address', 'created_at']);
            $table->index(['user_id', 'type', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('security_logs');
    }
};
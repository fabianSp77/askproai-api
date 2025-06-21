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
        $this->createTableIfNotExists('logs', function (Blueprint $table) {
            $table->id();
            $table->string('level', 20)->index(); // error, warning, info, debug
            $table->text('message');
            $table->string('channel', 50)->nullable();
            $this->addJsonColumn($table, 'context', true);
            $table->string('user_id', 50)->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('request_id', 100)->nullable();
            $table->timestamps();
            
            // Indexes for performance
            $table->index('created_at');
            $table->index(['level', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $this->dropTableIfExists('logs');
    }
};
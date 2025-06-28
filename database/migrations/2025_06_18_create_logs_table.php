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
        if (!Schema::hasTable('logs')) {
            $this->createTableIfNotExists('logs', function (Blueprint $table) {
                $table->id();
                $table->string('level', 20)->index();
                $table->text('message');
                $this->addJsonColumn($table, 'context', true);
                $table->string('channel')->default('stack');
                $table->timestamps();
                
                $table->index('created_at');
                $table->index(['level', 'created_at']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $this->dropTableIfExists('logs');
    }
};
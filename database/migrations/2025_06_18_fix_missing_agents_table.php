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
        // Create agents table if it doesn't exist
        if (!Schema::hasTable('agents')) {
            $this->createTableIfNotExists('agents', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('type')->default('retell'); // retell, custom, etc.
                $table->string('external_id')->nullable(); // Retell agent ID
                $table->foreignId('company_id')->constrained()->cascadeOnDelete();
                $this->addJsonColumn($table, 'config', true); // Agent configuration
                $table->boolean('is_active')->default(true);
                $table->timestamps();
                
                $table->index('company_id');
                $table->index('external_id');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $this->dropTableIfExists('agents');
    }
};
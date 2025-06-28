<?php

use App\Database\CompatibleMigration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends CompatibleMigration
{
    public function up(): void
    {
        $this->createTableIfNotExists('unified_event_types', function (Blueprint $table) {
            $table->id();
            $table->uuid('branch_id');
            $table->string('provider'); // 'calcom', 'google', 'outlook', etc.
            $table->string('external_id')->nullable();
            $table->string('name');
            $table->text('description')->nullable();
            $table->integer('duration_minutes')->default(30);
            $table->decimal('price', 10, 2)->nullable();
            $this->addJsonColumn($table, 'provider_data', true); // Provider-spezifische Daten
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            $table->foreign('branch_id')->references('id')->on('branches');
            $table->index(['branch_id', 'provider']);
            $table->unique(['provider', 'external_id']);
        });
    }

    public function down(): void
    {
        $this->dropTableIfExists('unified_event_types');
    }
};

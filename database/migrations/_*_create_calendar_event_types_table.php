<?php

use App\Database\CompatibleMigration;
use Illuminate\Database\Schema\Blueprint;

return new class extends CompatibleMigration
{
    public function up(): void
    {
        $this->createTableIfNotExists('calendar_event_types', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained()->onDelete('cascade');
            $table->string('provider'); // calcom, google, outlook, internal
            $table->string('external_id')->nullable();
            $table->string('name');
            $table->text('description')->nullable();
            $table->integer('duration_minutes')->default(30);
            $table->decimal('price', 10, 2)->nullable();
            $this->addJsonColumn($table, 'provider_data', true); // Provider-spezifische Daten
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            $table->index(['branch_id', 'provider']);
            $table->unique(['branch_id', 'provider', 'external_id']);
        });
    }

    public function down(): void
    {
        $this->dropTableIfExists('calendar_event_types');
    }
};

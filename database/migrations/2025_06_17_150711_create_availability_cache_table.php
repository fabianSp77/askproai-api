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
        Schema::create('availability_cache', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('staff_id')->constrained()->onDelete('cascade');
            $table->foreignId('event_type_id')->nullable()->constrained('calcom_event_types')->onDelete('cascade');
            $table->date('date');
            $this->addJsonColumn($table, 'slots', false); // Available time slots for the date
            $table->string('cache_key')->unique(); // Unique key for cache lookup
            $table->timestamp('cached_at');
            $table->timestamp('expires_at');
            $table->boolean('is_valid')->default(true);
            $table->timestamps();
            
            // Indexes for efficient querying
            $table->index(['staff_id', 'date']);
            $table->index(['event_type_id', 'date']);
            $table->index('expires_at');
            $table->index('cache_key');
            $table->index(['is_valid', 'expires_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('availability_cache');
    }
};

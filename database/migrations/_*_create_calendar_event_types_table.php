<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('calendar_event_types', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained()->onDelete('cascade');
            $table->string('provider'); // calcom, google, outlook, internal
            $table->string('external_id')->nullable();
            $table->string('name');
            $table->text('description')->nullable();
            $table->integer('duration_minutes')->default(30);
            $table->decimal('price', 10, 2)->nullable();
            $table->json('provider_data')->nullable(); // Provider-spezifische Daten
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            $table->index(['branch_id', 'provider']);
            $table->unique(['branch_id', 'provider', 'external_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('calendar_event_types');
    }
};

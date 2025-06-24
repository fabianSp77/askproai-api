<?php

use App\Database\CompatibleMigration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends CompatibleMigration
{
    public function up(): void
    {
        // Erstelle staff_event_types Verknüpfungstabelle
        Schema::create('staff_event_types', function (Blueprint $table) {
            $table->id();
            $table->char('staff_id', 36);
            $table->unsignedBigInteger('event_type_id');
            $table->string('calcom_user_id')->nullable();
            $table->boolean('is_primary')->default(false);
            $table->integer('custom_duration')->nullable();
            $table->decimal('custom_price', 10, 2)->nullable();
            $this->addJsonColumn($table, 'availability_override', true);
            $table->timestamps();
            
            // Foreign keys
            $table->foreign('staff_id')->references('id')->on('staff')->onDelete('cascade');
            $table->foreign('event_type_id')->references('id')->on('calcom_event_types')->onDelete('cascade');
            
            // Unique constraint
            $table->unique(['staff_id', 'event_type_id'], 'unique_staff_event');
            
            // Indexes
            $table->index('staff_id');
            $table->index('event_type_id');
            $table->index('is_primary');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('staff_event_types');
    }
};
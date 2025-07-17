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
        Schema::create('call_activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('call_id')->constrained('calls')->onDelete('cascade');
            $table->foreignId('company_id')->constrained('companies')->onDelete('cascade');
            $table->foreignId('user_id')->nullable()->constrained('portal_users')->onDelete('set null');
            $table->string('activity_type'); // 'status_changed', 'assigned', 'email_sent', 'note_added', 'call_received', etc.
            $table->string('title');
            $table->text('description')->nullable();
            $table->json('metadata')->nullable(); // Additional data like old_status, new_status, email_recipients, etc.
            $table->string('icon')->nullable(); // Icon identifier for UI
            $table->string('color')->nullable(); // Color theme for UI
            $table->boolean('is_system')->default(false); // System-generated vs user-generated
            $table->timestamps();
            
            // Indexes for performance
            $table->index(['call_id', 'created_at']);
            $table->index('company_id');
            $table->index('activity_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('call_activities');
    }
};

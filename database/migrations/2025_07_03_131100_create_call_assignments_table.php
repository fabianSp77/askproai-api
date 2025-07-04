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
        Schema::create('call_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('call_id')->constrained('calls')->onDelete('cascade');
            $table->foreignId('assigned_by')->constrained('portal_users')->onDelete('cascade');
            $table->foreignId('assigned_to')->constrained('portal_users')->onDelete('cascade');
            $table->foreignId('previous_assignee')->nullable()->constrained('portal_users')->onDelete('set null');
            $table->text('notes')->nullable();
            $table->timestamps();
            
            $table->index(['call_id', 'created_at']);
            $table->index('assigned_to');
            $table->index('assigned_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('call_assignments');
    }
};
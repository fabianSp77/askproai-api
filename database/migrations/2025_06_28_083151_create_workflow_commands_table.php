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
        Schema::create('workflow_commands', function (Blueprint $table) {
            $table->id();
            $table->foreignId('command_workflow_id')->constrained()->cascadeOnDelete();
            $table->foreignId('command_template_id')->constrained()->cascadeOnDelete();
            $table->integer('order')->default(0);
            $table->json('config')->nullable();
            $table->text('condition')->nullable();
            $table->timestamps();
            
            // Indexes
            $table->unique(['command_workflow_id', 'order']);
            $table->index(['command_workflow_id', 'command_template_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('workflow_commands');
    }
};

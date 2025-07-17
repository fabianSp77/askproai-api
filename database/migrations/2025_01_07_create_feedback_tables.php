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
        Schema::create('feedback', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies');
            $table->foreignId('user_id')->constrained('portal_users');
            $table->enum('type', ['bug', 'feature', 'improvement', 'question', 'complaint']);
            $table->enum('priority', ['low', 'medium', 'high', 'urgent']);
            $table->string('subject');
            $table->text('message');
            $table->enum('status', ['open', 'in_progress', 'resolved', 'closed'])->default('open');
            $table->json('attachments')->nullable();
            $table->timestamp('first_response_at')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();
            
            $table->index(['company_id', 'status']);
            $table->index(['user_id', 'created_at']);
        });

        Schema::create('feedback_responses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('feedback_id')->constrained('feedback')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('portal_users');
            $table->text('message');
            $table->boolean('is_internal')->default(false);
            $table->timestamps();
            
            $table->index(['feedback_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('feedback_responses');
        Schema::dropIfExists('feedback');
    }
};
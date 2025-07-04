<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('portal_feedback', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('user_id');
            $table->enum('entity_type', ['call', 'appointment', 'feature', 'general']);
            $table->string('entity_id')->nullable();
            $table->integer('rating')->nullable();
            $table->text('comment');
            $table->enum('category', ['bug', 'idea', 'complaint', 'praise']);
            $table->enum('status', ['new', 'reviewed', 'in_progress', 'resolved', 'closed'])->default('new');
            $table->text('admin_response')->nullable();
            $table->unsignedBigInteger('responded_by')->nullable();
            $table->timestamp('responded_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('portal_users')->onDelete('cascade');
            $table->foreign('responded_by')->references('id')->on('users')->onDelete('set null');
            
            $table->index(['company_id', 'status']);
            $table->index(['entity_type', 'entity_id']);
            $table->index('category');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('portal_feedback');
    }
};
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
        Schema::create('admin_updates', function (Blueprint $table) {
            $table->id();

            // Core fields
            $table->string('title')->index();
            $table->text('description');
            $table->longText('content'); // HTML or markdown content

            // Categorization
            $table->string('category')->default('general'); // 'bugfix', 'improvement', 'feature', 'general'
            $table->string('priority')->default('medium'); // 'low', 'medium', 'high', 'critical'
            $table->string('status')->default('published'); // 'draft', 'published', 'archived'

            // Files/attachments
            $table->json('attachments')->nullable(); // JSON array of file paths
            $table->json('code_snippets')->nullable(); // Stores copy-paste ready code blocks

            // Related info
            $table->string('related_files')->nullable(); // Comma-separated file paths
            $table->string('related_issue')->nullable(); // Issue reference or ticket ID
            $table->json('action_items')->nullable(); // TODO items as JSON

            // Tracking
            $table->unsignedBigInteger('created_by')->index();
            $table->text('changelog')->nullable(); // Edit history

            // Visibility
            $table->boolean('is_public')->default(false); // Only Super-Admin sees internal updates
            $table->date('published_at')->nullable();
            $table->date('archived_at')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Indexes for filtering
            $table->index(['status', 'published_at']);
            $table->index(['category', 'priority']);
            $table->index(['created_by', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('admin_updates');
    }
};

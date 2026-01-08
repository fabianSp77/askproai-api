<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 3: Knowledge Base Articles
 *
 * ServiceNow-style knowledge management system for self-service and agent reference.
 * Articles can be linked to categories for contextual help suggestions.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('knowledge_base_articles', function (Blueprint $table) {
            $table->id();

            // Multi-tenant isolation
            $table->unsignedBigInteger('company_id');

            // Article identity
            $table->string('title', 255);
            $table->string('slug', 255);

            // Content
            $table->text('summary')->nullable()->comment('Short summary for search results');
            $table->longText('content')->comment('Rich text content (HTML)');

            // Classification
            $table->unsignedBigInteger('category_id')->nullable();
            $table->json('keywords')->nullable()->comment('Search keywords array');
            $table->enum('article_type', ['how_to', 'faq', 'reference', 'troubleshooting', 'policy'])
                ->default('how_to');

            // Visibility & Status
            $table->boolean('is_published')->default(false);
            $table->boolean('is_featured')->default(false);
            $table->boolean('is_internal')->default(false)->comment('Only visible to staff');

            // Authorship (staff uses UUID char(36))
            $table->char('author_id', 36)->charset('utf8mb4')->collation('utf8mb4_unicode_ci')->nullable();
            $table->char('last_reviewed_by', 36)->charset('utf8mb4')->collation('utf8mb4_unicode_ci')->nullable();
            $table->timestamp('last_reviewed_at')->nullable();

            // Analytics
            $table->unsignedInteger('view_count')->default(0);
            $table->unsignedInteger('helpful_count')->default(0);
            $table->unsignedInteger('not_helpful_count')->default(0);

            // Ordering
            $table->integer('sort_order')->default(0);

            $table->timestamps();

            // Unique slug per company
            $table->unique(['company_id', 'slug']);

            // Performance indexes
            $table->index(['company_id', 'is_published']);
            $table->index(['company_id', 'category_id']);
            $table->index(['company_id', 'article_type']);
            $table->index('view_count');

            // Foreign keys
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->foreign('category_id')->references('id')->on('service_case_categories')->onDelete('set null');
            $table->foreign('author_id')->references('id')->on('staff')->onDelete('set null');
            $table->foreign('last_reviewed_by')->references('id')->on('staff')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('knowledge_base_articles');
    }
};

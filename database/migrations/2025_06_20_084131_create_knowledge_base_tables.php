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
        // Categories for organizing documents
        Schema::create('knowledge_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('icon')->nullable();
            $table->text('description')->nullable();
            $table->unsignedBigInteger('parent_id')->nullable();
            $table->integer('order')->default(0);
            $table->boolean('is_visible')->default(true);
            $this->addJsonColumn($table, 'metadata', true);
            $table->timestamps();
            
            $table->index('slug');
            $table->index('parent_id');
            $table->foreign('parent_id')->references('id')->on('knowledge_categories')->onDelete('set null');
        });

        // Main documents table
        Schema::create('knowledge_documents', function (Blueprint $table) {
            $table->id();
            $table->string('path')->unique(); // File path relative to docs directory
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('excerpt')->nullable();
            $table->longText('content')->nullable(); // Cached markdown content
            $table->longText('html_content')->nullable(); // Cached HTML content
            $table->unsignedBigInteger('category_id')->nullable();
            $table->string('type')->default('markdown'); // markdown, api, guide, etc.
            $table->string('status')->default('published'); // draft, published, archived
            $table->integer('reading_time')->nullable(); // Estimated reading time in minutes
            $table->integer('views_count')->default(0);
            $table->integer('search_count')->default(0);
            $this->addJsonColumn($table, 'metadata', true); // frontmatter and other metadata
            $this->addJsonColumn($table, 'auto_tags', true); // AI-generated tags
            $table->string('hash')->nullable(); // File content hash for change detection
            $table->dateTime('last_modified_at')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            
            $table->index('slug');
            $table->index('category_id');
            $table->index('type');
            $table->index('status');
            $table->index('views_count');
            $this->addFullTextIndex($table, ['title', 'content']);
            
            $table->foreign('category_id')->references('id')->on('knowledge_categories')->onDelete('set null');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('updated_by')->references('id')->on('users')->onDelete('set null');
        });

        // Tags for flexible categorization
        Schema::create('knowledge_tags', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('color')->default('#6B7280');
            $table->text('description')->nullable();
            $table->integer('usage_count')->default(0);
            $table->timestamps();
            
            $table->index('slug');
            $table->index('usage_count');
        });

        // Many-to-many relationship between documents and tags
        Schema::create('knowledge_document_tag', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('document_id');
            $table->unsignedBigInteger('tag_id');
            $table->boolean('is_primary')->default(false);
            $table->timestamps();
            
            $table->unique(['document_id', 'tag_id']);
            $table->foreign('document_id')->references('id')->on('knowledge_documents')->onDelete('cascade');
            $table->foreign('tag_id')->references('id')->on('knowledge_tags')->onDelete('cascade');
        });

        // Version control for documents
        Schema::create('knowledge_versions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('document_id');
            $table->integer('version_number');
            $table->longText('content');
            $table->longText('diff')->nullable(); // Diff from previous version
            $table->string('commit_message')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
            
            $table->index(['document_id', 'version_number']);
            $table->foreign('document_id')->references('id')->on('knowledge_documents')->onDelete('cascade');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
        });

        // Search index with vector embeddings for semantic search
        Schema::create('knowledge_search_index', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('document_id');
            $table->string('section_title')->nullable();
            $table->text('content_chunk'); // Chunk of content for embedding
            $this->addJsonColumn($table, 'embedding', true); // Vector embedding for semantic search
            $this->addJsonColumn($table, 'keywords', true); // Extracted keywords
            $table->float('relevance_score')->default(1.0);
            $table->timestamps();
            
            $table->index('document_id');
            $table->index('relevance_score');
            $this->addFullTextIndex($table, 'content_chunk');
            
            $table->foreign('document_id')->references('id')->on('knowledge_documents')->onDelete('cascade');
        });

        // Code snippets extracted from documents
        Schema::create('knowledge_code_snippets', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('document_id');
            $table->string('language');
            $table->string('title')->nullable();
            $table->text('code');
            $table->text('description')->nullable();
            $table->boolean('is_executable')->default(false);
            $this->addJsonColumn($table, 'execution_config', true); // Config for running the code
            $table->integer('usage_count')->default(0);
            $table->timestamps();
            
            $table->index('document_id');
            $table->index('language');
            $table->index('is_executable');
            
            $table->foreign('document_id')->references('id')->on('knowledge_documents')->onDelete('cascade');
        });

        // Document relationships (related docs, prerequisites, etc.)
        Schema::create('knowledge_relationships', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('source_document_id');
            $table->unsignedBigInteger('target_document_id');
            $table->string('relationship_type'); // related, prerequisite, next, previous, etc.
            $table->float('strength')->default(1.0); // Relationship strength (0-1)
            $table->boolean('is_auto_detected')->default(false);
            $table->timestamps();
            
            $table->unique(['source_document_id', 'target_document_id', 'relationship_type'], 'unique_relationship');
            $table->index('relationship_type');
            
            $table->foreign('source_document_id')->references('id')->on('knowledge_documents')->onDelete('cascade');
            $table->foreign('target_document_id')->references('id')->on('knowledge_documents')->onDelete('cascade');
        });

        // User interactions and analytics
        Schema::create('knowledge_analytics', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('document_id')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('event_type'); // view, search, copy_code, download, etc.
            $this->addJsonColumn($table, 'event_data', true);
            $table->string('session_id')->nullable();
            $table->string('ip_address')->nullable();
            $table->string('user_agent')->nullable();
            $table->timestamps();
            
            $table->index('document_id');
            $table->index('user_id');
            $table->index('event_type');
            $table->index('created_at');
            
            $table->foreign('document_id')->references('id')->on('knowledge_documents')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
        });

        // Collaborative features - comments and annotations
        Schema::create('knowledge_comments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('document_id');
            $table->unsignedBigInteger('parent_id')->nullable();
            $table->unsignedBigInteger('user_id');
            $table->text('content');
            $table->string('status')->default('active'); // active, resolved, deleted
            $this->addJsonColumn($table, 'position', true); // Position in document for inline comments
            $table->timestamps();
            
            $table->index('document_id');
            $table->index('parent_id');
            $table->index('status');
            
            $table->foreign('document_id')->references('id')->on('knowledge_documents')->onDelete('cascade');
            $table->foreign('parent_id')->references('id')->on('knowledge_comments')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });

        // Personal notebooks for developers
        Schema::create('knowledge_notebooks', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('title');
            $table->string('slug');
            $table->text('description')->nullable();
            $table->boolean('is_public')->default(false);
            $this->addJsonColumn($table, 'metadata', true);
            $table->timestamps();
            
            $table->unique(['user_id', 'slug']);
            $table->index('is_public');
            
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });

        // Notebook entries
        Schema::create('knowledge_notebook_entries', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('notebook_id');
            $table->string('title');
            $table->longText('content');
            $this->addJsonColumn($table, 'tags', true);
            $table->integer('order')->default(0);
            $table->timestamps();
            
            $table->index('notebook_id');
            $table->foreign('notebook_id')->references('id')->on('knowledge_notebooks')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('knowledge_notebook_entries');
        Schema::dropIfExists('knowledge_notebooks');
        Schema::dropIfExists('knowledge_comments');
        Schema::dropIfExists('knowledge_analytics');
        Schema::dropIfExists('knowledge_relationships');
        Schema::dropIfExists('knowledge_code_snippets');
        Schema::dropIfExists('knowledge_search_index');
        Schema::dropIfExists('knowledge_versions');
        Schema::dropIfExists('knowledge_document_tag');
        Schema::dropIfExists('knowledge_tags');
        Schema::dropIfExists('knowledge_documents');
        Schema::dropIfExists('knowledge_categories');
    }
};

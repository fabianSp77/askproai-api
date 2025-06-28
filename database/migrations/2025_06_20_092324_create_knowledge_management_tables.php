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
        $this->createTableIfNotExists('knowledge_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('icon')->nullable();
            $table->string('color')->nullable();
            $table->integer('order')->default(0);
            $table->unsignedBigInteger('parent_id')->nullable();
            $table->timestamps();
            
            $table->foreign('parent_id')->references('id')->on('knowledge_categories')->onDelete('cascade');
            $table->index(['slug', 'parent_id']);
        });

        // Tags for flexible tagging
        $this->createTableIfNotExists('knowledge_tags', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('color')->nullable();
            $table->timestamps();
            
            $table->index('slug');
        });

        // Main documents table
        $this->createTableIfNotExists('knowledge_documents', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('excerpt')->nullable();
            $table->longText('content');
            $table->longText('raw_content')->nullable(); // Original markdown content
            $table->string('file_path')->nullable();
            $table->string('file_type')->default('markdown');
            $table->unsignedBigInteger('category_id')->nullable();
            $this->addJsonColumn($table, 'metadata', true); // Additional metadata
            $table->string('status')->default('published'); // draft, published, archived
            $table->integer('view_count')->default(0);
            $table->integer('helpful_count')->default(0);
            $table->integer('not_helpful_count')->default(0);
            $table->timestamp('last_indexed_at')->nullable();
            $table->timestamp('file_modified_at')->nullable();
            $table->timestamps();
            
            $table->foreign('category_id')->references('id')->on('knowledge_categories')->nullOnDelete();
            $table->index(['slug', 'status']);
            $table->index('category_id');
            $this->addFullTextIndex($table, ['title', 'content', 'excerpt']);
        });

        // Many-to-many relationship for documents and tags
        $this->createTableIfNotExists('knowledge_document_tags', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('document_id');
            $table->unsignedBigInteger('tag_id');
            $table->timestamps();
            
            $table->foreign('document_id')->references('id')->on('knowledge_documents')->onDelete('cascade');
            $table->foreign('tag_id')->references('id')->on('knowledge_tags')->onDelete('cascade');
            $table->unique(['document_id', 'tag_id'], 'knowledge_doc_tag_unique');
        });

        // Search index for better performance
        $this->createTableIfNotExists('knowledge_search_index', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('document_id');
            $table->string('term');
            $table->float('relevance')->default(1.0);
            $table->string('field'); // title, content, tag, etc.
            $table->timestamps();
            
            $table->foreign('document_id')->references('id')->on('knowledge_documents')->onDelete('cascade');
            $table->index(['term', 'relevance']);
            $table->index('document_id');
        });

        // User feedback and interactions
        $this->createTableIfNotExists('knowledge_feedback', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('document_id');
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('session_id')->nullable();
            $table->boolean('is_helpful');
            $table->text('comment')->nullable();
            $table->string('ip_address')->nullable();
            $table->string('user_agent')->nullable();
            $table->timestamps();
            
            $table->foreign('document_id')->references('id')->on('knowledge_documents')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
            $table->index(['document_id', 'is_helpful']);
            $table->index('session_id');
        });

        // Related documents for better navigation
        $this->createTableIfNotExists('knowledge_related_documents', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('document_id');
            $table->unsignedBigInteger('related_document_id');
            $table->float('relevance_score')->default(1.0);
            $table->string('relation_type')->default('similar'); // similar, prerequisite, next, etc.
            $table->timestamps();
            
            $table->foreign('document_id')->references('id')->on('knowledge_documents')->onDelete('cascade');
            $table->foreign('related_document_id')->references('id')->on('knowledge_documents')->onDelete('cascade');
            $table->unique(['document_id', 'related_document_id'], 'knowledge_related_docs_unique');
            $table->index(['document_id', 'relevance_score']);
        });

        // Analytics for tracking popular content
        $this->createTableIfNotExists('knowledge_analytics', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('document_id');
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('session_id')->nullable();
            $table->string('event_type'); // view, search, click, share, etc.
            $this->addJsonColumn($table, 'event_data', true);
            $table->string('referrer')->nullable();
            $table->string('ip_address')->nullable();
            $table->string('user_agent')->nullable();
            $table->timestamps();
            
            $table->foreign('document_id')->references('id')->on('knowledge_documents')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
            $table->index(['document_id', 'event_type', 'created_at']);
            $table->index('session_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop tables in reverse order to respect foreign key constraints
        $this->dropTableIfExists('knowledge_analytics');
        $this->dropTableIfExists('knowledge_related_documents');
        $this->dropTableIfExists('knowledge_feedback');
        $this->dropTableIfExists('knowledge_search_index');
        $this->dropTableIfExists('knowledge_document_tags');
        $this->dropTableIfExists('knowledge_tags');
        $this->dropTableIfExists('knowledge_documents');
        $this->dropTableIfExists('knowledge_categories');
    }
};

<?php

use App\Database\CompatibleMigration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends CompatibleMigration
{
    public function up()
    {
        // Documentation items table
        Schema::create('documentation_items', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('title');
            $table->text('description');
            $table->longText('content')->nullable();
            $table->string('url');
            $table->string('category'); // critical, process, technical, reference
            $table->enum('difficulty', ['beginner', 'intermediate', 'advanced'])->default('beginner');
            $table->integer('estimated_reading_time')->default(5); // in minutes
            $table->json('tags')->nullable();
            $table->json('features')->nullable();
            $table->json('prerequisites')->nullable();
            $table->json('related_documents')->nullable();
            $table->integer('view_count')->default(0);
            $table->decimal('rating', 2, 1)->nullable();
            $table->string('version')->default('1.0');
            $table->boolean('is_outdated')->default(false);
            $table->boolean('is_interactive')->default(false);
            $table->boolean('has_video')->default(false);
            $table->text('ai_summary')->nullable();
            $table->json('metadata')->nullable();
            $table->string('icon')->nullable();
            $table->string('color')->nullable();
            $table->timestamps();
            
            $table->index(['category', 'difficulty']);
            $table->index('slug');
            
            // Use compatible fulltext index method
            $this->addFullTextIndex($table, ['title', 'description', 'ai_summary'], 'fulltext_documentation_search');
        });
        
        // User document favorites
        Schema::create('user_doc_favorites', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('document_id');
            $table->timestamps();
            
            $table->unique(['user_id', 'document_id']);
            $table->index('user_id');
        });
        
        // Document views tracking
        Schema::create('doc_views', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
            $table->string('document_id');
            $table->string('session_id')->nullable();
            $table->integer('time_spent')->default(0); // in seconds
            $table->integer('scroll_depth')->default(0); // percentage
            $table->string('user_agent')->nullable();
            $table->string('referrer')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('viewed_at');
            $table->timestamps();
            
            $table->index(['user_id', 'document_id']);
            $table->index('viewed_at');
        });
        
        // Reading progress tracking
        Schema::create('reading_progress', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('document_id');
            $table->integer('progress')->default(0); // percentage
            $table->json('completed_sections')->nullable();
            $table->timestamp('last_read_at')->nullable();
            $table->timestamps();
            
            $table->unique(['user_id', 'document_id']);
            $table->index('user_id');
        });
        
        // Document comments and discussions
        Schema::create('doc_comments', function (Blueprint $table) {
            $table->id();
            $table->string('document_id');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('parent_id')->nullable()->constrained('doc_comments')->onDelete('cascade');
            $table->text('comment');
            $table->boolean('is_question')->default(false);
            $table->boolean('is_resolved')->default(false);
            $table->integer('upvotes')->default(0);
            $table->integer('downvotes')->default(0);
            $table->timestamps();
            
            $table->index(['document_id', 'created_at']);
            $table->index('user_id');
        });
        
        // Document versions tracking
        Schema::create('doc_versions', function (Blueprint $table) {
            $table->id();
            $table->string('document_id');
            $table->string('version');
            $table->text('changelog')->nullable();
            $table->json('diff')->nullable();
            $table->foreignId('updated_by')->constrained('users')->onDelete('cascade');
            $table->timestamps();
            
            $table->index(['document_id', 'version']);
        });
        
        // Documentation search logs
        Schema::create('doc_search_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
            $table->string('query');
            $table->integer('results_count')->default(0);
            $table->json('clicked_results')->nullable();
            $table->string('session_id')->nullable();
            $table->timestamps();
            
            $table->index('user_id');
            $table->index('created_at');
        });
        
        // Documentation ratings
        Schema::create('doc_ratings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('document_id');
            $table->integer('rating'); // 1-5
            $table->text('feedback')->nullable();
            $table->timestamps();
            
            $table->unique(['user_id', 'document_id']);
            $table->index('document_id');
        });
        
        // Documentation analytics
        Schema::create('doc_analytics', function (Blueprint $table) {
            $table->id();
            $table->string('event_type'); // page_view, search, download, share, etc.
            $table->string('document_id')->nullable();
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
            $table->json('properties')->nullable();
            $table->string('session_id')->nullable();
            $table->string('user_agent')->nullable();
            $table->string('ip_address')->nullable();
            $table->timestamp('occurred_at');
            $table->timestamps();
            
            $table->index(['event_type', 'occurred_at']);
            $table->index('user_id');
            $table->index('document_id');
        });
        
        // AI-powered Q&A logs
        Schema::create('doc_ai_queries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
            $table->text('question');
            $table->text('answer');
            $table->json('context_documents')->nullable();
            $table->decimal('confidence_score', 3, 2)->nullable();
            $table->boolean('was_helpful')->nullable();
            $table->text('feedback')->nullable();
            $table->timestamps();
            
            $table->index('user_id');
            $table->index('created_at');
        });
    }
    
    public function down()
    {
        Schema::dropIfExists('doc_ai_queries');
        Schema::dropIfExists('doc_analytics');
        Schema::dropIfExists('doc_ratings');
        Schema::dropIfExists('doc_search_logs');
        Schema::dropIfExists('doc_versions');
        Schema::dropIfExists('doc_comments');
        Schema::dropIfExists('reading_progress');
        Schema::dropIfExists('doc_views');
        Schema::dropIfExists('user_doc_favorites');
        Schema::dropIfExists('documentation_items');
    }
};
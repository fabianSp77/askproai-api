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
        Schema::create('help_article_views', function (Blueprint $table) {
            $table->id();
            $table->string('category', 50);
            $table->string('topic', 100);
            $table->string('title');
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent')->nullable();
            $table->foreignId('portal_user_id')->nullable()->constrained()->onDelete('set null');
            $table->string('session_id', 100)->nullable();
            $table->string('referrer')->nullable();
            $table->timestamps();
            
            // Indexes for performance
            $table->index(['category', 'topic']);
            $table->index('created_at');
            $table->index('portal_user_id');
            $table->index('session_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('help_article_views');
    }
};

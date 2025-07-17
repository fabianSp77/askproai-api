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
        Schema::create('help_article_feedback', function (Blueprint $table) {
            $table->id();
            $table->string('category', 50);
            $table->string('topic', 100);
            $table->boolean('helpful');
            $table->text('comment')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->foreignId('portal_user_id')->nullable()->constrained()->onDelete('set null');
            $table->string('session_id', 100)->nullable();
            $table->timestamps();
            
            // Indexes
            $table->index(['category', 'topic']);
            $table->index('helpful');
            $table->index('created_at');
            $table->index('portal_user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('help_article_feedback');
    }
};

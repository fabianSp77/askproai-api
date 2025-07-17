<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('search_indices', function (Blueprint $table) {
            $table->id();
            $table->string('searchable_type');
            $table->unsignedBigInteger('searchable_id');
            $table->string('title');
            $table->text('content')->nullable();
            $table->string('category'); // customer, appointment, call, document, etc.
            $table->string('icon')->nullable();
            $table->string('route')->nullable(); // Route to navigate to
            $table->json('metadata')->nullable(); // Additional searchable data
            $table->integer('weight')->default(1); // For ranking
            $table->boolean('is_active')->default(true);
            $table->foreignId('company_id')->constrained();
            $table->timestamps();
            
            $table->index(['searchable_type', 'searchable_id']);
            $table->index('company_id');
            $table->index('category');
            
            // Fulltext index only for MySQL
            if (config('database.default') === 'mysql') {
                $table->fulltext(['title', 'content']);
            }
        });
    }

    public function down()
    {
        Schema::dropIfExists('search_indices');
    }
};
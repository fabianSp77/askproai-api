<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('prompt_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->text('content');
            $table->json('variables')->nullable(); // Array of variable names
            $table->unsignedBigInteger('parent_id')->nullable();
            $table->string('category')->default('general');
            $table->string('version')->default('1.0.0');
            $table->boolean('is_active')->default(true);
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            $table->foreign('parent_id')->references('id')->on('prompt_templates')->onDelete('set null');
            $table->index(['category', 'is_active']);
            $table->index('parent_id');
        });
    }

    public function down()
    {
        Schema::dropIfExists('prompt_templates');
    }
};
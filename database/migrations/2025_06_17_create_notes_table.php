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
        Schema::create('notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('title');
            $table->text('content');
            $table->string('type')->default('general'); // general, important, medical, preference, complaint
            $table->boolean('is_pinned')->default(false);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
            
            $table->index(['customer_id', 'is_pinned']);
            $table->index('type');
            $table->index('created_at');
            $table->index('user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notes');
    }
};
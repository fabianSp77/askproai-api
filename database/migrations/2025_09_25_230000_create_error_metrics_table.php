<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations
     */
    public function up(): void
    {
        if (Schema::hasTable('error_metrics')) {
            return;
        }

        Schema::create('error_metrics', function (Blueprint $table) {
            $table->id();
            $table->string('error_hash', 32)->index();
            $table->string('exception_class');
            $table->text('message');
            $table->string('file');
            $table->integer('line');
            $table->string('url')->nullable();
            $table->string('method', 10)->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->json('context')->nullable();
            $table->timestamp('occurred_at')->index();
            $table->timestamps();

            // Indexes for performance
            $table->index(['error_hash', 'occurred_at'], 'idx_error_hash_occurred');
            $table->index(['exception_class', 'occurred_at'], 'idx_exception_occurred');
            $table->index(['user_id', 'occurred_at'], 'idx_user_occurred');
        });
    }

    /**
     * Reverse the migrations
     */
    public function down(): void
    {
        Schema::dropIfExists('error_metrics');
    }
};
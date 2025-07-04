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
        Schema::create('gdpr_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained()->onDelete('cascade');
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->enum('type', ['export', 'deletion']);
            $table->enum('status', ['pending_confirmation', 'completed', 'expired', 'cancelled'])
                ->default('pending_confirmation');
            $table->string('token')->unique();
            $table->string('file_path')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamp('downloaded_at')->nullable();
            $table->integer('download_count')->default(0);
            $table->json('metadata')->nullable();
            $table->timestamps();
            
            // Indexes
            $table->index(['customer_id', 'type']);
            $table->index(['token']);
            $table->index(['status']);
            $table->index(['expires_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('gdpr_requests');
    }
};
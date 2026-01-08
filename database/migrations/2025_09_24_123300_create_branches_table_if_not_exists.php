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
        if (!Schema::hasTable('branches')) {
            Schema::create('branches', function (Blueprint $table) {
                $table->char('id', 36)->charset('utf8mb4')->collation('utf8mb4_unicode_ci')->primary(); // UUID as primary key
                $table->unsignedBigInteger('company_id');
                $table->foreign('company_id')->references('id')->on('companies')->cascadeOnDelete();
                $table->string('name');
                $table->string('slug')->nullable();
                $table->string('address')->nullable();
                $table->string('city')->nullable();
                $table->string('state')->nullable();
                $table->string('country')->nullable();
                $table->string('postal_code', 20)->nullable();
                $table->string('phone', 20)->nullable();
                $table->string('email')->nullable();
                $table->decimal('latitude', 10, 8)->nullable();
                $table->decimal('longitude', 11, 8)->nullable();
                $table->string('timezone')->default('Europe/Berlin');
                $table->boolean('is_active')->default(true);
                $table->json('metadata')->nullable();
                $table->timestamps();

                // Indexes
                $table->index(['company_id', 'is_active']);
                $table->unique(['company_id', 'slug']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('branches');
    }
};
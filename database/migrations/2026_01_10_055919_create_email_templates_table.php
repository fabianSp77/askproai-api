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
        Schema::create('email_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')
                ->constrained('companies')
                ->onDelete('cascade');
            $table->string('name', 255);
            $table->string('subject', 500);
            $table->text('body_html');
            $table->json('variables')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            // Index for fast lookup by company
            $table->index('company_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('email_templates');
    }
};

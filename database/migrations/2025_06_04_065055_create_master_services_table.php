<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('master_services', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('company_id')->constrained('companies')->onDelete('cascade');
            $table->string('name');
            $table->text('description')->nullable();
            $table->integer('base_duration')->default(30);
            $table->decimal('base_price', 10, 2)->nullable();
            $table->string('calcom_event_type_id')->nullable();
            $table->string('retell_service_identifier')->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();

            $table->index(['company_id', 'active']);
            $table->index('retell_service_identifier');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('master_services');
    }
};

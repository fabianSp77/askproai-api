<?php

use App\Database\CompatibleMigration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends CompatibleMigration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('event_type_import_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->uuid('branch_id');
            $table->foreign('branch_id')->references('id')->on('branches')->onDelete('cascade');
            $table->unsignedBigInteger('user_id')->nullable();
            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
            $table->string('import_type', 50)->default('manual'); // manual, automatic, webhook
            $table->integer('total_found')->default(0);
            $table->integer('total_imported')->default(0);
            $table->integer('total_skipped')->default(0);
            $table->integer('total_failed')->default(0);
            $table->string('status', 50)->default('pending'); // pending, processing, completed, failed
            $this->addJsonColumn($table, 'details', true); // Store detailed import information
            $this->addJsonColumn($table, 'errors', true); // Store any errors that occurred
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            
            // Indexes
            $table->index(['company_id', 'created_at']);
            $table->index(['branch_id', 'created_at']);
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('event_type_import_logs');
    }
};
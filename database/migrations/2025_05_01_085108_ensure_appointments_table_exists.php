<?php

use App\Database\CompatibleMigration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends CompatibleMigration {
    public function up(): void
    {
        if (! Schema::hasTable('appointments')) {
            $this->createTableIfNotExists('appointments', function (Blueprint $table) {
                $table->id();
                $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
                $table->string('external_id')->nullable()->index();
                $table->timestamp('starts_at')->nullable();
                $table->timestamp('ends_at')->nullable();
                $this->addJsonColumn($table, 'payload', true);
                $table->string('status')->default('pending');
                $table->timestamps();
            });
        }
    }
    public function down(): void { /* intentionally empty */ }
};

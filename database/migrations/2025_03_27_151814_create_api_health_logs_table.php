<?php

use App\Database\CompatibleMigration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends CompatibleMigration {
    public function up() {
        $this->createTableIfNotExists('api_health_logs', function (Blueprint $table) {
            $table->id();
            $table->string('service');
            $table->foreignId('company_id')->nullable()->constrained()->nullOnDelete();
            $table->string('status');
            $table->text('message')->nullable();
            $table->float('response_time')->nullable();
            $table->timestamps();
        });
    }

    public function down() {
        $this->dropTableIfExists('api_health_logs');
    }
};

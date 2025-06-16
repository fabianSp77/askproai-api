<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up() {
        Schema::create('api_health_logs', function (Blueprint $table) {
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
        Schema::dropIfExists('api_health_logs');
    }
};

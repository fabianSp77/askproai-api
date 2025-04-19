<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up() {
        Schema::create('retell_agents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('phone_number_id')->nullable()->constrained()->nullOnDelete();
            $table->string('agent_id');
            $table->string('name');
            $table->json('settings')->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();
        });
    }

    public function down() {
        Schema::dropIfExists('retell_agents');
    }
};

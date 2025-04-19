<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up() {
        Schema::create('companies', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('address')->nullable();
            $table->string('contact_person')->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->json('opening_hours')->nullable();
            $table->string('calcom_api_key')->nullable();
            $table->string('calcom_user_id')->nullable();
            $table->string('retell_api_key')->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();
        });
    }

    public function down() {
        Schema::dropIfExists('companies');
    }
};

<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('phone_numbers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('branch_id')->index();
            $table->string('number')->unique();
            $table->boolean('active')->default(true);
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('phone_numbers'); }
};

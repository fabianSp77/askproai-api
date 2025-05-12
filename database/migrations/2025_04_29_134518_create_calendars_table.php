<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('calendars', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('staff_id')->index();
            $table->enum('provider',['calcom','google'])->default('calcom');
            $table->text('api_key')->nullable();
            $table->string('event_type_id')->nullable();
            $table->string('external_user_id')->nullable();
            $table->timestamp('validated_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }
    public function down(): void { Schema::dropIfExists('calendars'); }
};

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
        Schema::create('calls', function (Blueprint $table) {
            $table->id();
            $table->string('call_id')->nullable()->index();
            $table->string('call_status')->nullable();
            $table->string('user_sentiment')->nullable();
            $table->boolean('successful')->default(true);
            $table->timestamp('call_time')->nullable();
            $table->integer('call_duration')->nullable();
            $table->string('type')->nullable();
            $table->decimal('cost', 10, 2)->nullable();
            $table->string('phone_number')->nullable();
            $table->string('name')->nullable();
            $table->string('email')->nullable();
            $table->text('summary')->nullable();
            $table->text('transcript')->nullable();
            $table->string('disconnect_reason')->nullable();
            $table->json('raw_data')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('calls');
    }
};

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
        Schema::create('api_credentials', function (Blueprint $table) {
            $table->id();
            $table->morphs('credentialable'); // company_id/branch_id/staff_id
            $table->string('service'); // 'calcom' oder 'retell'
            $table->string('key_type'); // 'api_key', 'user_id', 'event_type_id'
            $table->text('value')->nullable();
            $table->boolean('is_inherited')->default(false);
            $table->unsignedBigInteger('inherited_from_id')->nullable();
            $table->string('inherited_from_type')->nullable();
            $table->timestamps();
            $table->index(['service', 'key_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('api_credentials');
    }
};

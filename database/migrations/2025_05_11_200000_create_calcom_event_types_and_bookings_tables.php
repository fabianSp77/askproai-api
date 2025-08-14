<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        /* ----------------- event-types ----------------- */
        Schema::create('calcom_event_types', function (Blueprint $table) {
            $table->bigIncrements('id');               // BIGINT PK
            $table->char('staff_id', 36)->nullable()->index();   // FK → staff.uuid
            $table->string('name');
            $table->timestamps();

            $table->foreign('staff_id')
                ->references('id')->on('staff')
                ->cascadeOnDelete();
        });

        /* ----------------- bookings ----------------- */
        Schema::create('calcom_bookings', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('event_type_id')->index();
            $table->uuid('branch_id')->nullable();     // FK → branches.uuid
            $table->string('external_id')->nullable();
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->timestamps();

            $table->foreign('event_type_id')
                ->references('id')->on('calcom_event_types')
                ->cascadeOnDelete();

            $table->foreign('branch_id')
                ->references('id')->on('branches')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('calcom_bookings');
        Schema::dropIfExists('calcom_event_types');
    }
};

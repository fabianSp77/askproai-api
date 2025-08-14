<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        /* -------- calcom_event_types -------------------------------------- */
        Schema::create('calcom_event_types', function (Blueprint $t) {
            $t->id();                           // auto‑increment
            $t->string('calcom_id')->unique();  // z.B. "2026302"
            $t->string('title');                // "Beratung 30 min"
            $t->uuid('staff_id'); // FK → staff
            $t->boolean('active')->default(true);
            $t->timestamps();

            $t->foreign('staff_id')->references('id')->on('staff')
                ->cascadeOnDelete();
        });

        /* -------- calcom_bookings ----------------------------------------- */
        Schema::create('calcom_bookings', function (Blueprint $t) {
            $t->id();
            $t->string('calcom_uid')->unique();   // Cal.com Booking‑UID
            $t->foreignId('appointment_id')       // FK → appointments
                ->constrained()->cascadeOnDelete();
            $t->enum('status', ['booked', 'rescheduled', 'canceled'])
                ->default('booked');
            $t->json('raw_payload')->nullable();  // komplette Webhook‑JSON
            $t->timestamps();
        });

        /* -------- appointments ➡︎ Referenz auf Cal.com -------------------- */
        Schema::table('appointments', function (Blueprint $t) {
            $t->unsignedBigInteger('calcom_booking_id')
                ->nullable()->after('id');
        });
    }

    public function down(): void
    {
        Schema::table('appointments', fn (Blueprint $t) => $t->dropColumn('calcom_booking_id'));
        Schema::dropIfExists('calcom_bookings');
        Schema::dropIfExists('calcom_event_types');
    }
};

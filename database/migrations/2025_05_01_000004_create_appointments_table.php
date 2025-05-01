<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('appointments', function (Blueprint $t) {
            // Primärschlüssel als UUID (CHAR(36))
            $t->uuid('id')->primary();

            // Pflicht-Bezüge (alle haben UUID als PK)
            $t->char('branch_id', 36);
            $t->char('service_id', 36);

            // Mitarbeiter (UUID) optional – z. B. bei “erst freien wählen”
            $t->char('staff_id', 36)->nullable();

            // Kunde – hier BIGINT, weil customers-PK auto-increment ist
            $t->unsignedBigInteger('customer_id')->nullable();

            // Zeitfenster des Termins
            $t->timestamp('starts_at');
            $t->timestamp('ends_at');

            $t->timestamps();
            $t->softDeletes();

            /* ───────── Foreign-Keys ───────── */
            $t->foreign('branch_id')->references('id')->on('branches')->cascadeOnDelete();
            $t->foreign('service_id')->references('id')->on('services')->cascadeOnDelete();
            $t->foreign('staff_id')->references('id')->on('staff')->nullOnDelete();       // bei Löschung Mitarbeiter ⇒ NULL
            $t->foreign('customer_id')->references('id')->on('customers')->nullOnDelete(); // bei Löschung Kunde     ⇒ NULL
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('appointments');
    }
};

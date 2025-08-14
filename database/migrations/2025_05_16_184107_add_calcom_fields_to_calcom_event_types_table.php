<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('calcom_event_types', function (Blueprint $table) {
            $table->unsignedBigInteger('calcom_numeric_event_type_id')
                ->nullable()
                ->after('name')
                ->index()
                ->comment('Die numerische Event Type ID von Cal.com (z.B. 12345)');

            $table->integer('duration_minutes')
                ->nullable()
                ->after('calcom_numeric_event_type_id')
                ->comment('Dauer der Dienstleistung in Minuten');

            $table->text('description')
                ->nullable()
                ->after('duration_minutes')
                ->comment('Beschreibung der Dienstleistung/des Event Typs');

            $table->decimal('price', 8, 2)
                ->nullable()
                ->after('description')
                ->comment('Preis der Dienstleistung');

            $table->boolean('is_active')
                ->default(true)
                ->after('price')
                ->comment('Ist dieser Event Type aktiv und buchbar?');
        });
    }

    public function down(): void
    {
        Schema::table('calcom_event_types', function (Blueprint $table) {
            $table->dropColumn([
                'calcom_numeric_event_type_id',
                'duration_minutes',
                'description',
                'price',
                'is_active',
            ]);
        });
    }
};

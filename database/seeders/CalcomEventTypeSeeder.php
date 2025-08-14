<?php

namespace Database\Seeders;

use App\Models\CalcomEventType;
use Illuminate\Database\Seeder;

class CalcomEventTypeSeeder extends Seeder
{
    /**
     * Verknüpft den Cal.com-Event-Typ 2026302 mit dem
     * Staff-Datensatz von Fabian Spitzer.
     */
    public function run(): void
    {
        CalcomEventType::updateOrCreate(
            ['calcom_id' => 2026302],                   // ➊ Event-Type
            ['staff_id' => '9ede975f-2e65-4c85-99f6-301f388dc7eb'] // ➋ Staff-UUID
        );
    }
}

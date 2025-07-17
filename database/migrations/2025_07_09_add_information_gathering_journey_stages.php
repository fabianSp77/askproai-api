<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Check if table exists first
        if (!Schema::hasTable('customer_journey_stages')) {
            return;
        }
        
        // Verschiebe bestehende Stages um Platz zu machen
        DB::table('customer_journey_stages')
            ->where('order', '>=', 2)
            ->increment('order');
            
        // Füge neue Stages für Informationssammlung hinzu
        $newStages = [
            [
                'code' => 'information_gathering',
                'name' => 'Informationen sammeln',
                'description' => 'Kunde wird beraten oder Informationen werden gesammelt',
                'order' => 2,
                'color' => 'blue',
                'icon' => 'heroicon-o-clipboard-document-list',
                'next_stages' => json_encode(['appointment_scheduled', 'quotation_sent', 'follow_up_needed', 'not_interested']),
                'automation_rules' => json_encode([]),
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'code' => 'quotation_sent',
                'name' => 'Angebot versendet',
                'description' => 'Ein Angebot wurde an den Kunden geschickt',
                'order' => 3,
                'color' => 'purple',
                'icon' => 'heroicon-o-document-text',
                'next_stages' => json_encode(['appointment_scheduled', 'quotation_accepted', 'follow_up_needed', 'not_interested']),
                'automation_rules' => json_encode([]),
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'code' => 'quotation_accepted',
                'name' => 'Angebot angenommen',
                'description' => 'Kunde hat das Angebot angenommen',
                'order' => 4,
                'color' => 'green',
                'icon' => 'heroicon-o-check-circle',
                'next_stages' => json_encode(['appointment_scheduled', 'service_provided', 'regular_customer']),
                'automation_rules' => json_encode([]),
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'code' => 'service_provided',
                'name' => 'Leistung erbracht',
                'description' => 'Die Dienstleistung wurde erfolgreich erbracht',
                'order' => 5,
                'color' => 'emerald',
                'icon' => 'heroicon-o-sparkles',
                'next_stages' => json_encode(['regular_customer', 'follow_up_needed']),
                'automation_rules' => json_encode([]),
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now()
            ]
        ];
        
        // Insert only if not exists
        foreach ($newStages as $stage) {
            DB::table('customer_journey_stages')
                ->insertOrIgnore($stage);
        }
        
        // Update next_stages für initial_contact
        DB::table('customer_journey_stages')
            ->where('code', 'initial_contact')
            ->update([
                'next_stages' => json_encode(['information_gathering', 'appointment_scheduled', 'quotation_sent', 'not_interested'])
            ]);
            
        // Update order für verschobene Stages
        DB::table('customer_journey_stages')
            ->where('code', 'appointment_scheduled')
            ->update(['order' => 6]);
            
        DB::table('customer_journey_stages')
            ->where('code', 'appointment_completed')
            ->update(['order' => 7]);
            
        DB::table('customer_journey_stages')
            ->where('code', 'regular_customer')
            ->update(['order' => 8]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Lösche die neuen Stages
        DB::table('customer_journey_stages')
            ->whereIn('code', ['information_gathering', 'quotation_sent', 'quotation_accepted', 'service_provided'])
            ->delete();
            
        // Stelle die ursprüngliche Reihenfolge wieder her
        DB::table('customer_journey_stages')
            ->where('code', 'appointment_scheduled')
            ->update(['order' => 2]);
            
        DB::table('customer_journey_stages')
            ->where('code', 'appointment_completed')
            ->update(['order' => 3]);
            
        DB::table('customer_journey_stages')
            ->where('code', 'regular_customer')
            ->update(['order' => 4]);
            
        // Reset next_stages für initial_contact
        DB::table('customer_journey_stages')
            ->where('code', 'initial_contact')
            ->update([
                'next_stages' => json_encode(['appointment_scheduled', 'not_interested'])
            ]);
    }
};
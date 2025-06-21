<?php

use App\Database\CompatibleMigration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends CompatibleMigration
{
    public function up(): void
    {
        if (!Schema::hasTable('business_hours_templates')) {
            Schema::create('business_hours_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('description')->nullable();
            $this->addJsonColumn($table, 'hours', false);
            $table->boolean('is_default')->default(false);
            $table->timestamps();
        });

        // Standard-Templates einfügen
        DB::table('business_hours_templates')->insert([
            [
                'name' => 'Standard Bürozeiten',
                'description' => 'Mo-Fr 9:00-18:00',
                'hours' => json_encode([
                    'monday' => ['open' => '09:00', 'close' => '18:00', 'closed' => false],
                    'tuesday' => ['open' => '09:00', 'close' => '18:00', 'closed' => false],
                    'wednesday' => ['open' => '09:00', 'close' => '18:00', 'closed' => false],
                    'thursday' => ['open' => '09:00', 'close' => '18:00', 'closed' => false],
                    'friday' => ['open' => '09:00', 'close' => '18:00', 'closed' => false],
                    'saturday' => ['closed' => true],
                    'sunday' => ['closed' => true]
                ]),
                'is_default' => true,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'name' => 'Einzelhandel',
                'description' => 'Mo-Sa mit verlängerten Öffnungszeiten',
                'hours' => json_encode([
                    'monday' => ['open' => '09:00', 'close' => '20:00', 'closed' => false],
                    'tuesday' => ['open' => '09:00', 'close' => '20:00', 'closed' => false],
                    'wednesday' => ['open' => '09:00', 'close' => '20:00', 'closed' => false],
                    'thursday' => ['open' => '09:00', 'close' => '20:00', 'closed' => false],
                    'friday' => ['open' => '09:00', 'close' => '20:00', 'closed' => false],
                    'saturday' => ['open' => '10:00', 'close' => '18:00', 'closed' => false],
                    'sunday' => ['closed' => true]
                ]),
                'is_default' => false,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'name' => 'Friseur/Salon',
                'description' => 'Di-Sa mit Montag Ruhetag',
                'hours' => json_encode([
                    'monday' => ['closed' => true],
                    'tuesday' => ['open' => '09:00', 'close' => '18:00', 'closed' => false],
                    'wednesday' => ['open' => '09:00', 'close' => '18:00', 'closed' => false],
                    'thursday' => ['open' => '09:00', 'close' => '20:00', 'closed' => false],
                    'friday' => ['open' => '09:00', 'close' => '18:00', 'closed' => false],
                    'saturday' => ['open' => '09:00', 'close' => '14:00', 'closed' => false],
                    'sunday' => ['closed' => true]
                ]),
                'is_default' => false,
                'created_at' => now(),
                'updated_at' => now()
            ]
        ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('business_hours_templates');
    }
};

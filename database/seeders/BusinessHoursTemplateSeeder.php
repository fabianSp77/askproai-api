<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\BusinessHoursTemplate;

class BusinessHoursTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $templates = [
            [
                'name' => 'Bürozeiten',
                'description' => 'Standard Bürozeiten Mo-Fr 9-17 Uhr',
                'hours' => [
                    'monday' => ['is_open' => true, 'open_time' => '09:00', 'close_time' => '17:00'],
                    'tuesday' => ['is_open' => true, 'open_time' => '09:00', 'close_time' => '17:00'],
                    'wednesday' => ['is_open' => true, 'open_time' => '09:00', 'close_time' => '17:00'],
                    'thursday' => ['is_open' => true, 'open_time' => '09:00', 'close_time' => '17:00'],
                    'friday' => ['is_open' => true, 'open_time' => '09:00', 'close_time' => '17:00'],
                    'saturday' => ['is_open' => false, 'open_time' => '', 'close_time' => ''],
                    'sunday' => ['is_open' => false, 'open_time' => '', 'close_time' => ''],
                ]
            ],
            [
                'name' => 'Einzelhandel',
                'description' => 'Typische Einzelhandelszeiten Mo-Sa',
                'hours' => [
                    'monday' => ['is_open' => true, 'open_time' => '09:00', 'close_time' => '19:00'],
                    'tuesday' => ['is_open' => true, 'open_time' => '09:00', 'close_time' => '19:00'],
                    'wednesday' => ['is_open' => true, 'open_time' => '09:00', 'close_time' => '19:00'],
                    'thursday' => ['is_open' => true, 'open_time' => '09:00', 'close_time' => '19:00'],
                    'friday' => ['is_open' => true, 'open_time' => '09:00', 'close_time' => '19:00'],
                    'saturday' => ['is_open' => true, 'open_time' => '09:00', 'close_time' => '16:00'],
                    'sunday' => ['is_open' => false, 'open_time' => '', 'close_time' => ''],
                ]
            ],
            [
                'name' => 'Friseur',
                'description' => 'Typische Friseur-Öffnungszeiten',
                'hours' => [
                    'monday' => ['is_open' => false, 'open_time' => '', 'close_time' => ''],
                    'tuesday' => ['is_open' => true, 'open_time' => '09:00', 'close_time' => '18:00'],
                    'wednesday' => ['is_open' => true, 'open_time' => '09:00', 'close_time' => '18:00'],
                    'thursday' => ['is_open' => true, 'open_time' => '09:00', 'close_time' => '20:00'],
                    'friday' => ['is_open' => true, 'open_time' => '09:00', 'close_time' => '18:00'],
                    'saturday' => ['is_open' => true, 'open_time' => '08:00', 'close_time' => '14:00'],
                    'sunday' => ['is_open' => false, 'open_time' => '', 'close_time' => ''],
                ]
            ],
        ];

        foreach ($templates as $template) {
            BusinessHoursTemplate::create($template);
        }
    }
}

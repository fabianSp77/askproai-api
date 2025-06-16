<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BusinessHoursTemplate extends Model
{
    protected $fillable = [
        'name',
        'description',
        'hours'
    ];

    protected $casts = [
        'hours' => 'array'
    ];

    // Vordefinierte Templates
    public static function getDefaultTemplates()
    {
        return [
            [
                'name' => 'Bürozeiten (Mo-Fr 9-17 Uhr)',
                'description' => 'Standardöffnungszeiten für Büros',
                'hours' => [
                    'monday' => ['open' => '09:00', 'close' => '17:00', 'closed' => false],
                    'tuesday' => ['open' => '09:00', 'close' => '17:00', 'closed' => false],
                    'wednesday' => ['open' => '09:00', 'close' => '17:00', 'closed' => false],
                    'thursday' => ['open' => '09:00', 'close' => '17:00', 'closed' => false],
                    'friday' => ['open' => '09:00', 'close' => '17:00', 'closed' => false],
                    'saturday' => ['open' => '', 'close' => '', 'closed' => true],
                    'sunday' => ['open' => '', 'close' => '', 'closed' => true],
                ]
            ],
            [
                'name' => 'Einzelhandel (Mo-Sa)',
                'description' => 'Typische Öffnungszeiten für Geschäfte',
                'hours' => [
                    'monday' => ['open' => '09:00', 'close' => '18:30', 'closed' => false],
                    'tuesday' => ['open' => '09:00', 'close' => '18:30', 'closed' => false],
                    'wednesday' => ['open' => '09:00', 'close' => '18:30', 'closed' => false],
                    'thursday' => ['open' => '09:00', 'close' => '18:30', 'closed' => false],
                    'friday' => ['open' => '09:00', 'close' => '18:30', 'closed' => false],
                    'saturday' => ['open' => '09:00', 'close' => '14:00', 'closed' => false],
                    'sunday' => ['open' => '', 'close' => '', 'closed' => true],
                ]
            ],
            [
                'name' => 'Friseur (Di-Sa)',
                'description' => 'Typische Öffnungszeiten für Friseursalons',
                'hours' => [
                    'monday' => ['open' => '', 'close' => '', 'closed' => true],
                    'tuesday' => ['open' => '09:00', 'close' => '18:00', 'closed' => false],
                    'wednesday' => ['open' => '09:00', 'close' => '18:00', 'closed' => false],
                    'thursday' => ['open' => '09:00', 'close' => '20:00', 'closed' => false],
                    'friday' => ['open' => '09:00', 'close' => '18:00', 'closed' => false],
                    'saturday' => ['open' => '08:00', 'close' => '14:00', 'closed' => false],
                    'sunday' => ['open' => '', 'close' => '', 'closed' => true],
                ]
            ]
        ];
    }
}

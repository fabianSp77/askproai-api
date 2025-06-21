<?php

namespace App\Services\Setup;

use App\Models\Company;
use App\Models\Service;
use Illuminate\Support\Facades\Log;

class ServiceTemplateService
{
    /**
     * Industry service templates
     */
    private array $industryTemplates = [
        'medical' => [
            'Erstberatung' => ['duration' => 30, 'price' => 50],
            'Behandlung' => ['duration' => 45, 'price' => 80],
            'Nachuntersuchung' => ['duration' => 20, 'price' => 40],
            'Notfalltermin' => ['duration' => 30, 'price' => 100],
        ],
        'beauty' => [
            'Haarschnitt' => ['duration' => 45, 'price' => 35],
            'Färben' => ['duration' => 120, 'price' => 85],
            'Maniküre' => ['duration' => 45, 'price' => 30],
            'Massage' => ['duration' => 60, 'price' => 60],
        ],
        'handwerk' => [
            'Kostenvoranschlag' => ['duration' => 30, 'price' => 0],
            'Reparatur Klein' => ['duration' => 60, 'price' => 50],
            'Reparatur Groß' => ['duration' => 180, 'price' => 150],
            'Wartung' => ['duration' => 90, 'price' => 80],
        ],
        'legal' => [
            'Erstberatung' => ['duration' => 45, 'price' => 150],
            'Vertragsberatung' => ['duration' => 60, 'price' => 200],
            'Gerichtstermin' => ['duration' => 120, 'price' => 300],
            'Dokumentenprüfung' => ['duration' => 30, 'price' => 100],
        ],
    ];

    /**
     * Create services from industry template
     */
    public function createFromIndustryTemplate(Company $company, string $industry): array
    {
        $templates = $this->industryTemplates[$industry] ?? $this->industryTemplates['medical'];
        $services = [];

        foreach ($templates as $serviceName => $config) {
            $service = Service::create([
                'company_id' => $company->id,
                'name' => $serviceName,
                'duration' => $config['duration'],
                'price' => $config['price'],
                'buffer_time' => $this->getBufferTime($industry),
                'is_active' => true,
                'description' => $this->getServiceDescription($serviceName, $industry),
                'settings' => [
                    'online_booking' => true,
                    'requires_prepayment' => false,
                    'max_advance_booking' => 30, // days
                    'min_advance_booking' => 1, // hours
                ]
            ]);

            $services[] = $service;
        }

        Log::info('Services created from template', [
            'company_id' => $company->id,
            'industry' => $industry,
            'count' => count($services)
        ]);

        return $services;
    }

    /**
     * Get buffer time based on industry
     */
    private function getBufferTime(string $industry): int
    {
        return match($industry) {
            'medical' => 10,
            'beauty' => 15,
            'handwerk' => 30,
            'legal' => 15,
            default => 15
        };
    }

    /**
     * Get service description
     */
    private function getServiceDescription(string $serviceName, string $industry): string
    {
        $descriptions = [
            'medical' => [
                'Erstberatung' => 'Erstgespräch und Untersuchung für neue Patienten',
                'Behandlung' => 'Reguläre Behandlungstermine',
                'Nachuntersuchung' => 'Kontrolltermin nach Behandlung',
                'Notfalltermin' => 'Dringende Termine am selben Tag',
            ],
            'beauty' => [
                'Haarschnitt' => 'Waschen, Schneiden und Föhnen',
                'Färben' => 'Haare färben inklusive Beratung',
                'Maniküre' => 'Nagelpflege und Lackierung',
                'Massage' => 'Entspannungsmassage',
            ],
        ];

        return $descriptions[$industry][$serviceName] ?? '';
    }
}
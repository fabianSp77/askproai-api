<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use App\Models\Company;
use App\Models\Branch;
use App\Models\Staff;
use App\Models\PhoneNumber;
use App\Models\CalcomEventType;

class CompanyTemplateSeeder extends Seeder
{
    public function run(): void
    {
        // --- 1. AskProAI (Einzelberater) ---
        $company = Company::updateOrCreate(['name' => 'AskProAI'], [
            'typ' => 'Einzelunternehmen',
        ]);
        $branch = Branch::updateOrCreate([
            'id' => (string) Str::uuid(),
            'name' => 'Berlin',
            'company_id' => $company->id,
        ]);
        $staff = Staff::updateOrCreate([
            'id' => (string) Str::uuid(),
            'name' => 'Fabian Spitzer',
            'branch_id' => $branch->id,
        ]);
        CalcomEventType::updateOrCreate([
            'staff_id' => $staff->id,
            'name' => 'Beratung',
        ], [
            'calcom_event_type_id' => 2547902,
            'duration' => 30,
            'calendar' => 'Fabian Spitzer Consulting (Google)',
        ]);
        CalcomEventType::updateOrCreate([
            'staff_id' => $staff->id,
            'name' => 'Check-In',
        ], [
            'calcom_event_type_id' => 2547903,
            'duration' => 15,
            'calendar' => 'Fabian Spitzer Consulting (Google)',
        ]);
        PhoneNumber::updateOrCreate([
            'id' => (string) Str::uuid(),
            'branch_id' => $branch->id,
            'number' => '+493083793369',
        ]);

        // --- 2. Friseursalon-Kette ---
        $friseur = Company::updateOrCreate(['name' => 'ModernHair Friseure'], [
            'typ' => 'Friseursalon-Kette',
        ]);
        $filialen = [
            ['Berlin', '+49301234567', [
                ['Anna', [['Damenhaarschnitt', 3001001, 45], ['Farbe', 3001002, 90]], 'Anna ModernHair'],
                ['Mona', [['Damenhaarschnitt', 3001003, 45]], 'Mona ModernHair'],
            ]],
            ['Hamburg', '+49401234567', [
                ['Peter', [['Herrenhaarschnitt', 3002001, 30], ['Farbe', 3002002, 90]], 'Peter ModernHair'],
                ['Tim', [['Herrenhaarschnitt', 3002003, 30]], 'Tim ModernHair'],
                ['Jens', [['Herrenhaarschnitt', 3002004, 30]], 'Jens ModernHair'],
            ]],
            ['München', '+49891234567', [
                ['Lisa', [['Damenhaarschnitt', 3003001, 45], ['Farbe', 3003002, 90]], 'Lisa ModernHair'],
                ['Tom', [['Herrenhaarschnitt', 3003003, 30]], 'Tom ModernHair'],
            ]],
            ['Köln', '+49221234567', [
                ['Sophie', [['Damenhaarschnitt', 3004001, 45]], 'Sophie ModernHair'],
                ['Lukas', [['Herrenhaarschnitt', 3004002, 30], ['Farbe', 3004003, 90]], 'Lukas ModernHair'],
            ]],
            ['Frankfurt', '+49691234567', [
                ['Markus', [['Herrenhaarschnitt', 3005001, 30]], 'Markus ModernHair'],
                ['Julia', [['Damenhaarschnitt', 3005002, 45], ['Farbe', 3005003, 90]], 'Julia ModernHair'],
            ]],
        ];
        foreach ($filialen as [$fname, $phone, $mitarbeiter]) {
            $b = Branch::updateOrCreate([
                'id' => (string) Str::uuid(),
                'name' => $fname,
                'company_id' => $friseur->id,
            ]);
            PhoneNumber::updateOrCreate([
                'id' => (string) Str::uuid(),
                'branch_id' => $b->id,
                'number' => $phone,
            ]);
            foreach ($mitarbeiter as [$sname, $diensts, $kal]) {
                $s = Staff::updateOrCreate([
                    'id' => (string) Str::uuid(),
                    'name' => $sname,
                    'branch_id' => $b->id,
                ]);
                foreach ($diensts as [$dname, $etid, $dauer]) {
                    CalcomEventType::updateOrCreate([
                        'staff_id' => $s->id,
                        'name' => $dname,
                    ], [
                        'calcom_event_type_id' => $etid,
                        'duration' => $dauer,
                        'calendar' => $kal,
                    ]);
                }
            }
        }
        // --- 3. Fitnessstudio-Kette ---
        $fitx = Company::updateOrCreate(['name' => 'FitXpert'], [
            'typ' => 'Fitnessstudio-Kette',
        ]);
        $fit_filialen = [
            ['Kassel', '+49561234567', [
                ['Trainer A', [['Probetraining', 4001001, 60]], 'Trainer A FitXpert'],
                ['Trainer B', [['Probetraining', 4001002, 60]], 'Trainer B FitXpert'],
            ]],
            ['Berlin', '+49301231234', [
                ['Trainer C', [['Probetraining', 4002001, 60]], 'Trainer C FitXpert'],
            ]],
            ['Hamburg', '+49401231234', [
                ['Trainer D', [['Probetraining', 4003001, 60]], 'Trainer D FitXpert'],
                ['Trainer E', [['Probetraining', 4003002, 60]], 'Trainer E FitXpert'],
                ['Trainer F', [['Probetraining', 4003003, 60]], 'Trainer F FitXpert'],
            ]],
            ['Köln', '+49221231234', [
                ['Trainer G', [['Probetraining', 4004001, 60]], 'Trainer G FitXpert'],
                ['Trainer H', [['Probetraining', 4004002, 60]], 'Trainer H FitXpert'],
            ]],
            ['München', '+49891231234', [
                ['Trainer I', [['Probetraining', 4005001, 60]], 'Trainer I FitXpert'],
                ['Trainer J', [['Probetraining', 4005002, 60]], 'Trainer J FitXpert'],
                ['Trainer K', [['Probetraining', 4005003, 60]], 'Trainer K FitXpert'],
            ]],
        ];
        foreach ($fit_filialen as [$fname, $phone, $mitarbeiter]) {
            $b = Branch::updateOrCreate([
                'id' => (string) Str::uuid(),
                'name' => $fname,
                'company_id' => $fitx->id,
            ]);
            PhoneNumber::updateOrCreate([
                'id' => (string) Str::uuid(),
                'branch_id' => $b->id,
                'number' => $phone,
            ]);
            foreach ($mitarbeiter as [$sname, $diensts, $kal]) {
                $s = Staff::updateOrCreate([
                    'id' => (string) Str::uuid(),
                    'name' => $sname,
                    'branch_id' => $b->id,
                ]);
                foreach ($diensts as [$dname, $etid, $dauer]) {
                    CalcomEventType::updateOrCreate([
                        'staff_id' => $s->id,
                        'name' => $dname,
                    ], [
                        'calcom_event_type_id' => $etid,
                        'duration' => $dauer,
                        'calendar' => $kal,
                    ]);
                }
            }
        }
    }
}

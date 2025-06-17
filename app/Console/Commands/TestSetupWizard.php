<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Company;
use App\Models\Branch;
use App\Models\Service;

class TestSetupWizard extends Command
{
    protected $signature = 'setup:test {--industry=medical : Industry template to use}';
    protected $description = 'Test the Quick Setup Wizard functionality';

    public function handle()
    {
        $this->info('🚀 Testing Quick Setup Wizard...');
        
        $industry = $this->option('industry');
        
        // Simulate wizard data with unique timestamp
        $timestamp = now()->format('YmdHis');
        $wizardData = [
            'company_name' => 'Test ' . ucfirst($industry) . ' ' . $timestamp,
            'industry' => $industry,
            'branch_name' => 'Hauptfiliale',
            'branch_city' => 'Berlin',
            'branch_address' => 'Teststraße 123',
            'branch_phone' => '+49 30 ' . rand(1000000, 9999999),
        ];
        
        $this->info('Creating company: ' . $wizardData['company_name']);
        
        try {
            // Create company
            $company = Company::create([
                'name' => $wizardData['company_name'],
                'industry' => $wizardData['industry'],
                'settings' => [
                    'wizard_completed' => true,
                    'setup_date' => now(),
                    'template_used' => $wizardData['industry']
                ]
            ]);
            
            $this->info('✅ Company created: ID ' . $company->id);
            
            // Create branch
            $branch = new Branch();
            $branch->company_id = $company->id;
            $branch->name = $wizardData['branch_name'];
            $branch->city = $wizardData['branch_city'];
            $branch->address = $wizardData['branch_address'];
            $branch->phone_number = $wizardData['branch_phone'];
            $branch->active = true;
            $branch->save();
            
            $this->info('✅ Branch created: ' . $branch->name);
            
            // Create template services
            $templates = [
                'medical' => ['Erstberatung', 'Behandlung', 'Nachuntersuchung'],
                'beauty' => ['Haarschnitt', 'Färben', 'Maniküre'],
                'handwerk' => ['Kostenvoranschlag', 'Reparatur', 'Installation'],
                'legal' => ['Erstberatung', 'Vertragsberatung', 'Rechtsberatung'],
            ];
            
            $services = $templates[$industry] ?? ['Standard Service'];
            
            foreach ($services as $serviceName) {
                Service::create([
                    'company_id' => $company->id,
                    'name' => $serviceName,
                    'duration' => 30,
                    'is_active' => true,
                ]);
            }
            
            $this->info('✅ Created ' . count($services) . ' services');
            
            // Summary
            $this->newLine();
            $this->info('=== SETUP COMPLETE ===');
            $this->table(
                ['Property', 'Value'],
                [
                    ['Company', $company->name],
                    ['Industry', $company->industry],
                    ['Branch', $branch->name . ' (' . $branch->city . ')'],
                    ['Phone', $branch->phone_number],
                    ['Services', implode(', ', $services)],
                    ['Status', 'Ready to receive calls!'],
                ]
            );
            
            $this->newLine();
            $this->info('🎉 Setup Wizard test completed successfully!');
            $this->info('Test phone number: ' . $branch->phone_number);
            
        } catch (\Exception $e) {
            $this->error('❌ Setup failed: ' . $e->getMessage());
            return 1;
        }
        
        return 0;
    }
}
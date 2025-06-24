<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Company;
use App\Models\Branch;
use App\Models\PhoneNumber;
use App\Models\CalcomEventType;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use App\Scopes\TenantScope;

class CreateTestCompany extends Command
{
    protected $signature = 'test:create-company 
                            {name : Company name}
                            {phone : Phone number (e.g. +4930123456)}
                            {--calcom-key= : Cal.com API key}
                            {--retell-key= : Retell.ai API key}
                            {--agent-id= : Retell agent ID}
                            {--event-type-id= : Cal.com event type ID}';

    protected $description = 'Create a test company with all necessary configurations';

    public function handle()
    {
        $name = $this->argument('name');
        $phone = $this->argument('phone');
        $calcomKey = $this->option('calcom-key') ?: env('DEFAULT_CALCOM_API_KEY');
        $retellKey = $this->option('retell-key') ?: env('DEFAULT_RETELL_API_KEY');
        $agentId = $this->option('agent-id') ?: env('DEFAULT_RETELL_AGENT_ID');
        $eventTypeId = $this->option('event-type-id');
        
        $this->info("Creating test company: $name");
        
        // Clear any session data that might interfere
        if (session()->has('company_id')) {
            session()->forget('company_id');
        }
        
        DB::beginTransaction();
        
        try {
            // 1. Create Company
            $company = Company::create([
                'id' => Str::uuid(),
                'name' => $name,
                'slug' => Str::slug($name),
                'email' => strtolower(str_replace(' ', '', $name)) . '@test.com',
                'phone' => $phone,
                'is_active' => true,
                'retell_api_key' => $retellKey ? encrypt($retellKey) : null,
                'calcom_api_key' => $calcomKey ? encrypt($calcomKey) : null,
                'settings' => [
                    'timezone' => 'Europe/Berlin',
                    'language' => 'de',
                    'booking_buffer_minutes' => 15,
                    'error_handling' => [
                        'mode' => 'callback',
                        'callback_message' => 'Ein Mitarbeiter wird sich bei Ihnen melden.'
                    ]
                ]
            ]);
            
            $this->info("✓ Company created: {$company->id}");
            
            // 2. Create Branch using DB insert to bypass trait validation
            $branchId = Str::uuid();
            DB::table('branches')->insert([
                'id' => $branchId,
                'uuid' => $branchId,
                'company_id' => $company->id,
                'name' => 'Hauptfiliale',
                'phone_number' => $phone,
                'email' => $company->email,
                'address' => 'Teststraße 1, 10115 Berlin',
                'is_active' => true,
                'active' => true,
                'calcom_event_type_id' => $eventTypeId,
                'created_at' => now(),
                'updated_at' => now()
            ]);
            
            $this->info("✓ Branch created: {$branchId}");
            
            // 3. Create Phone Number using DB insert
            $phoneId = Str::uuid();
            DB::table('phone_numbers')->insert([
                'id' => $phoneId,
                'company_id' => $company->id,
                'branch_id' => $branchId,
                'number' => $phone,
                'retell_agent_id' => $agentId,
                'is_active' => true,
                'is_primary' => true,
                'type' => 'office',
                'capabilities' => json_encode(['inbound', 'outbound']),
                'created_at' => now(),
                'updated_at' => now()
            ]);
            
            $this->info("✓ Phone number created with agent: $agentId");
            
            // 4. If event type ID provided, create local record
            if ($eventTypeId && $calcomKey) {
                $eventTypeDbId = DB::table('calcom_event_types')->insertGetId([
                    'company_id' => $company->id,
                    'calcom_numeric_event_type_id' => $eventTypeId,
                    'name' => 'Test Event Type',
                    'duration_minutes' => 30,
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
                
                // Create branch event type relationship
                DB::table('branch_event_types')->insert([
                    'branch_id' => $branchId,
                    'event_type_id' => $eventTypeDbId,
                    'is_primary' => true,
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
                
                $this->info("✓ Event type configured: $eventTypeId");
            }
            
            DB::commit();
            
            $this->info("\n🎉 Test company created successfully!");
            $this->info("\nQuick Test Commands:");
            $this->info("1. Test phone resolution:");
            $this->info("   php artisan tinker");
            $this->info("   >>> \$resolver = app(\App\Services\PhoneNumberResolver::class);");
            $this->info("   >>> \$result = \$resolver->resolve('$phone');");
            $this->info("   >>> dump(\$result);");
            $this->info("\n2. Test webhook:");
            $this->info("   curl -X POST http://localhost/api/retell/webhook \\");
            $this->info("     -H 'Content-Type: application/json' \\");
            $this->info("     -d '{\"event\":\"call_ended\",\"call\":{\"call_id\":\"test123\",\"to\":\"$phone\",\"from\":\"+4917612345678\"}}'");
            
            return 0;
            
        } catch (\Exception $e) {
            DB::rollback();
            $this->error("Failed to create test company: " . $e->getMessage());
            return 1;
        }
    }
}
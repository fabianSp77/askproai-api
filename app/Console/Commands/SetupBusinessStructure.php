<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Company;
use App\Models\PrepaidBalance;
use App\Models\PrepaidTransaction;
use App\Models\Branch;
use App\Models\Staff;
use App\Models\Service;
use Illuminate\Support\Facades\DB;

class SetupBusinessStructure extends Command
{
    protected $signature = 'business:setup';
    protected $description = 'Setup complete business structure with reseller and hair salon';

    public function handle()
    {
        $this->info('=== Business Setup Script ===');

        try {
            DB::beginTransaction();

            // 1. Create or verify consulting company (Reseller)
            $this->info('1. Setting up consulting company (Reseller)...');
            
            $reseller = Company::where('name', 'Premium Telecom Solutions GmbH')->first();
            
            if (!$reseller) {
                $reseller = Company::create([
                    'name' => 'Premium Telecom Solutions GmbH',
                    'slug' => 'premium-telecom-solutions',
                    'company_type' => 'reseller',
                    'industry' => 'Telecommunications & Consulting',
                    'email' => 'info@premium-telecom.com',
                    'phone' => '+49 30 12345678',
                    'address' => 'Potsdamer Platz 1',
                    'city' => 'Berlin',
                    'postal_code' => '10785',
                    'country' => 'DE',
                    'timezone' => 'Europe/Berlin',
                    'currency' => 'EUR',
                    'is_active' => true,
                    'commission_rate' => 0.10,
                ]);
                $this->line('   ✓ Created reseller company: ' . $reseller->name);
            } else {
                $this->line('   ✓ Reseller company already exists: ' . $reseller->name);
            }

            // Create prepaid balance for reseller
            $resellerBalance = PrepaidBalance::where('company_id', $reseller->id)->first();
            if (!$resellerBalance) {
                $resellerBalance = PrepaidBalance::create([
                    'company_id' => $reseller->id,
                    'balance' => 500.00,
                    'bonus_balance' => 0.00,
                    'reserved_balance' => 0.00,
                    'low_balance_threshold' => 50.00,
                    'auto_topup_enabled' => true,
                    'auto_topup_threshold' => 100.00,
                    'auto_topup_amount' => 200.00,
                ]);
                
                PrepaidTransaction::create([
                    'company_id' => $reseller->id,
                    'prepaid_balance_id' => $resellerBalance->id,
                    'type' => 'topup',
                    'amount' => 500.00,
                    'balance_before' => 0.00,
                    'balance_after' => 500.00,
                    'description' => 'Initial setup - Reseller starting balance',
                ]);
                
                $this->line('   ✓ Created prepaid balance: €' . $resellerBalance->balance);
            } else {
                $this->line('   ✓ Reseller prepaid balance exists: €' . $resellerBalance->balance);
            }

            // 2. Create hair salon as client
            $this->info('');
            $this->info('2. Setting up hair salon client...');
            
            $salon = Company::where('name', 'Salon Schönheit')->first();
            
            if (!$salon) {
                $salon = Company::create([
                    'name' => 'Salon Schönheit',
                    'slug' => 'salon-schoenheit',
                    'company_type' => 'client',
                    'parent_company_id' => $reseller->id,
                    'industry' => 'Beauty & Hair Care',
                    'email' => 'info@salon-schoenheit.de',
                    'phone' => '+49 40 98765432',
                    'address' => 'Mönckebergstraße 15',
                    'city' => 'Hamburg',
                    'postal_code' => '20095',
                    'country' => 'DE',
                    'timezone' => 'Europe/Berlin',
                    'currency' => 'EUR',
                    'is_active' => true,
                ]);
                $this->line('   ✓ Created hair salon: ' . $salon->name);
            } else {
                $this->line('   ✓ Hair salon already exists: ' . $salon->name);
            }

            // Create prepaid balance for salon
            $salonBalance = PrepaidBalance::where('company_id', $salon->id)->first();
            if (!$salonBalance) {
                $salonBalance = PrepaidBalance::create([
                    'company_id' => $salon->id,
                    'balance' => 200.00,
                    'bonus_balance' => 0.00,
                    'reserved_balance' => 0.00,
                    'low_balance_threshold' => 20.00,
                    'auto_topup_enabled' => true,
                    'auto_topup_threshold' => 50.00,
                    'auto_topup_amount' => 100.00,
                ]);
                
                PrepaidTransaction::create([
                    'company_id' => $salon->id,
                    'prepaid_balance_id' => $salonBalance->id,
                    'type' => 'topup', 
                    'amount' => 200.00,
                    'balance_before' => 0.00,
                    'balance_after' => 200.00,
                    'description' => 'Initial setup - Client starting balance',
                ]);
                
                $this->line('   ✓ Created prepaid balance: €' . $salonBalance->balance);
            } else {
                $this->line('   ✓ Salon prepaid balance exists: €' . $salonBalance->balance);
            }

            // 3. Create main branch for the salon
            $this->info('');
            $this->info('3. Setting up salon branch...');
            
            $branch = Branch::where('company_id', $salon->id)->where('name', 'Salon Schönheit Hauptfiliale')->first();
            
            if (!$branch) {
                $branch = Branch::create([
                    'company_id' => $salon->id,
                    'name' => 'Salon Schönheit Hauptfiliale',
                    'slug' => 'hauptfiliale',
                    'phone_number' => '+49 40 98765432',
                    'notification_email' => 'termine@salon-schoenheit.de',
                    'address' => 'Mönckebergstraße 15',
                    'city' => 'Hamburg',
                    'postal_code' => '20095',
                    'country' => 'DE',
                    'business_hours' => [
                        'monday' => ['open' => '09:00', 'close' => '18:00'],
                        'tuesday' => ['open' => '09:00', 'close' => '18:00'],
                        'wednesday' => ['open' => '09:00', 'close' => '18:00'],
                        'thursday' => ['open' => '09:00', 'close' => '20:00'],
                        'friday' => ['open' => '09:00', 'close' => '20:00'],
                        'saturday' => ['open' => '08:00', 'close' => '16:00'],
                        'sunday' => ['closed' => true],
                    ],
                    'active' => true,
                ]);
                $this->line('   ✓ Created branch: ' . $branch->name);
            } else {
                $this->line('   ✓ Branch already exists: ' . $branch->name);
            }

            // 4. Create 3 staff members
            $this->info('');
            $this->info('4. Setting up staff members...');
            
            $staffData = [
                [
                    'name' => 'Maria Schmidt',
                    'email' => 'maria@salon-schoenheit.de',
                    'phone' => '+49 40 98765433',
                    'is_owner' => true,
                    'role' => 'Salon Owner & Master Stylist',
                ],
                [
                    'name' => 'Julia Weber',
                    'email' => 'julia@salon-schoenheit.de', 
                    'phone' => '+49 40 98765434',
                    'is_owner' => false,
                    'role' => 'Senior Hair Stylist',
                ],
                [
                    'name' => 'Anna Müller',
                    'email' => 'anna@salon-schoenheit.de',
                    'phone' => '+49 40 98765435',
                    'is_owner' => false,
                    'role' => 'Junior Hair Stylist & Colorist',
                ],
            ];

            $createdStaff = [];
            foreach ($staffData as $staffInfo) {
                $staff = Staff::where('company_id', $salon->id)
                             ->where('email', $staffInfo['email'])
                             ->first();
                             
                if (!$staff) {
                    $staff = Staff::create([
                        'company_id' => $salon->id,
                        'branch_id' => $branch->id,
                        'home_branch_id' => $branch->id,
                        'name' => $staffInfo['name'],
                        'email' => $staffInfo['email'],
                        'phone' => $staffInfo['phone'],
                        'active' => true,
                        'is_active' => true,
                        'is_bookable' => true,
                        'notes' => $staffInfo['role'] . ($staffInfo['is_owner'] ? ' (Owner)' : ''),
                    ]);
                    $this->line('   ✓ Created staff: ' . $staff->name . ' - ' . $staffInfo['role']);
                } else {
                    $this->line('   ✓ Staff already exists: ' . $staff->name);
                }
                $createdStaff[] = $staff;
            }

            // 5. Create services for the salon
            $this->info('');
            $this->info('5. Setting up salon services...');
            
            $servicesData = [
                [
                    'name' => 'Herrenhaarschnitt',
                    'description' => 'Klassischer Herrenhaarschnitt mit Styling',
                    'price' => 35.00,
                    'duration' => 45,
                    'category' => 'Herrenschnitte',
                ],
                [
                    'name' => 'Damenhaarschnitt',
                    'description' => 'Damenhaarschnitt mit Waschen und Föhnen',
                    'price' => 55.00,
                    'duration' => 90,
                    'category' => 'Damenschnitte',
                ],
                [
                    'name' => 'Coloration',
                    'description' => 'Professionelle Haarfarbe mit Beratung',
                    'price' => 85.00,
                    'duration' => 120,
                    'category' => 'Coloration',
                ],
                [
                    'name' => 'Strähnen/Highlights',
                    'description' => 'Strähnen oder Highlights nach Wunsch',
                    'price' => 95.00,
                    'duration' => 150,
                    'category' => 'Coloration',
                ],
                [
                    'name' => 'Dauerwelle',
                    'description' => 'Klassische oder moderne Dauerwelle',
                    'price' => 75.00,
                    'duration' => 120,
                    'category' => 'Styling',
                ],
            ];

            foreach ($servicesData as $serviceInfo) {
                $service = Service::where('company_id', $salon->id)
                                 ->where('name', $serviceInfo['name'])
                                 ->first();
                                 
                if (!$service) {
                    $service = Service::create([
                        'company_id' => $salon->id,
                        'branch_id' => $branch->id,
                        'name' => $serviceInfo['name'],
                        'description' => $serviceInfo['description'],
                        'price' => $serviceInfo['price'],
                        'default_duration_minutes' => $serviceInfo['duration'],
                        'duration' => $serviceInfo['duration'],
                        'category' => $serviceInfo['category'],
                        'active' => true,
                        'is_online_bookable' => true,
                    ]);
                    $this->line('   ✓ Created service: ' . $service->name . ' - €' . $service->price . ' (' . $service->duration . 'min)');
                } else {
                    $this->line('   ✓ Service already exists: ' . $service->name);
                }
            }

            // 6. Test billing calculation
            $this->info('');
            $this->info('6. Testing per-second billing calculation...');
            
            // Simulate a 3 minute 45 second call (225 seconds)
            $callDurationSeconds = 225;
            $resellerRatePerMinute = 0.30;
            $clientRatePerMinute = 0.40;
            
            // Calculate per-second costs
            $resellerCostPerSecond = $resellerRatePerMinute / 60;
            $clientCostPerSecond = $clientRatePerMinute / 60;
            
            $resellerCost = $callDurationSeconds * $resellerCostPerSecond;
            $clientCost = $callDurationSeconds * $clientCostPerSecond;
            $resellerProfit = $clientCost - $resellerCost;
            
            $this->line('   Call Duration: ' . $callDurationSeconds . ' seconds (' . gmdate('i:s', $callDurationSeconds) . ')');
            $this->line('   Reseller pays system: €' . number_format($resellerCost, 4) . ' (€' . $resellerRatePerMinute . '/min)');
            $this->line('   Client pays reseller: €' . number_format($clientCost, 4) . ' (€' . $clientRatePerMinute . '/min)');
            $this->line('   Reseller profit: €' . number_format($resellerProfit, 4));

            // Simulate balance deduction
            if ($salonBalance->deductBalance($clientCost, "Test call - {$callDurationSeconds} seconds", 'call')) {
                $this->line('   ✓ Successfully deducted €' . number_format($clientCost, 4) . ' from salon balance');
                $this->line('   ✓ New salon balance: €' . $salonBalance->fresh()->effective_balance);
            } else {
                $this->line('   ✗ Failed to deduct balance (insufficient funds)');
            }

            DB::commit();
            
            $this->info('');
            $this->info('=== Setup Complete ===');
            $this->line('Summary:');
            $this->line('- Reseller: ' . $reseller->name . ' (ID: ' . $reseller->id . ')');
            $this->line('- Client: ' . $salon->name . ' (ID: ' . $salon->id . ')');
            $this->line('- Branch: ' . $branch->name . ' (ID: ' . $branch->id . ')');
            $this->line('- Staff: ' . count($createdStaff) . ' members created');
            $this->line('- Services: ' . count($servicesData) . ' services created');
            $this->line('- Billing: Per-second accuracy implemented');
            $this->line('- Prepaid: Both companies have prepaid balances');
            
        } catch (\Exception $e) {
            DB::rollBack();
            $this->error('Error: ' . $e->getMessage());
            $this->error('Trace: ' . $e->getTraceAsString());
        }
    }
}
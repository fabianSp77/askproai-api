<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Customer;
use App\Models\Appointment;
use Carbon\Carbon;

class TestSabineImport extends Command
{
    protected $signature = 'test:sabine-import';
    protected $description = 'Create Sabine Geyer appointment manually';

    public function handle()
    {
        $this->info('Creating Sabine Geyer appointment...');

        // Find or create Sabine Geyer customer
        $customer = Customer::where('name', 'LIKE', '%Sabine%Geyer%')->first();

        if (!$customer) {
            $customer = Customer::create([
                'name' => 'Sabine Geyer',
                'email' => '491755743147@sms.cal.com',
                'phone' => '+491755743147',
                'source' => 'cal.com',
                'notes' => 'Created from Cal.com booking',
            ]);
            $this->info('Created new customer: Sabine Geyer');
        } else {
            $this->info('Found existing customer: ' . $customer->name);
            // Update phone if missing
            if (!$customer->phone) {
                $customer->update(['phone' => '+491755743147']);
                $this->info('Updated phone number');
            }
        }

        // Get default company
        $company = \App\Models\Company::first();
        if (!$company) {
            $this->error('No company found in database');
            return 1;
        }

        // Create the appointment for September 20, 2025 at 08:00
        $appointment = Appointment::updateOrCreate(
            [
                'customer_id' => $customer->id,
                'starts_at' => '2025-09-20 08:00:00',
            ],
            [
                'company_id' => $company->id,
                'ends_at' => '2025-09-20 08:30:00',
                'status' => 'confirmed',
                'source' => 'cal.com',
                'notes' => '30-minütiger Termin mit Sabine Geyer',
                'calcom_v2_booking_id' => 'prGfz6zSvpYXsnsNmimdPC', // Use v2 field for string IDs
                'metadata' => json_encode([
                    'booking_link' => 'https://app.cal.com/booking/prGfz6zSvpYXsnsNmimdPC',
                    'imported_at' => now()->toIso8601String(),
                ]),
            ]
        );

        $this->info('✅ Appointment created/updated:');
        $this->info('  ID: ' . $appointment->id);
        $this->info('  Customer: ' . $customer->name);
        $this->info('  Date: ' . $appointment->starts_at . ' to ' . $appointment->ends_at);
        $this->info('  Status: ' . $appointment->status);
        $this->info('  Source: ' . $appointment->source);

        return 0;
    }
}
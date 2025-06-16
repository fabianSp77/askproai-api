<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Customer;
use App\Models\Appointment;
use App\Models\Company;
use App\Models\Call;
use App\Models\Staff;
use App\Models\Branch;

class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        // Eltern-Objekte zuerst (so können FKs genutzt werden)
        $customers = Customer::factory()->count(50)->create();
        $branches = Branch::factory()->count(5)->create();
        $companies = Company::factory()->count(10)->create();

        // Staff mit bestehender Branch zuordnen
        Staff::factory()->count(15)->make()->each(function($staff) use ($branches) {
            $staff->branch_id = $branches->random()->id;
            $staff->save();
        });

        // Appointments zu bestehenden Kunden
        Appointment::factory()->count(100)->make()->each(function($appointment) use ($customers) {
            $appointment->customer_id = $customers->random()->id;
            $appointment->save();
        });

        // Calls zu bestehenden Kunden
        Call::factory()->count(30)->make()->each(function($call) use ($customers) {
            $call->customer_id = $customers->random()->id;
            $call->save();
        });
    }
}

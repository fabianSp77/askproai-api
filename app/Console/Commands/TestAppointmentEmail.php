<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Appointment;
use App\Models\Company;
use App\Jobs\SendAppointmentEmailJob;

class TestAppointmentEmail extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'email:test-appointment {appointment_id?} {--locale=de}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test appointment confirmation email sending';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $appointmentId = $this->argument('appointment_id');
        $locale = $this->option('locale');
        
        // Set a company context for the command
        $company = Company::first();
        if (!$company) {
            $this->error('No companies found in the database.');
            return 1;
        }
        
        // Bind the company ID to the app container for TenantScope
        app()->instance('current_company_id', $company->id);
        $this->info("Using company context: {$company->name}");
        
        if (!$appointmentId) {
            // Find the most recent appointment with a customer email
            $appointment = Appointment::with(['customer', 'staff', 'service', 'branch.company'])
                ->whereHas('customer', function ($query) {
                    $query->whereNotNull('email');
                })
                ->where('company_id', $company->id)
                ->latest()
                ->first();
                
            if (!$appointment) {
                $this->error('No appointments found with customer email addresses.');
                return 1;
            }
            
            $this->info("Using most recent appointment: ID {$appointment->id}");
        } else {
            $appointment = Appointment::with(['customer', 'staff', 'service', 'branch.company'])
                ->find($appointmentId);
                
            if (!$appointment) {
                $this->error("Appointment with ID {$appointmentId} not found.");
                return 1;
            }
        }
        
        // Display appointment details
        $this->info("\nAppointment Details:");
        $this->line("ID: {$appointment->id}");
        $this->line("Customer: {$appointment->customer->first_name} {$appointment->customer->last_name}");
        $this->line("Email: {$appointment->customer->email}");
        $this->line("Date: {$appointment->starts_at->format('d.m.Y H:i')}");
        $this->line("Service: " . ($appointment->service->name ?? 'N/A'));
        $this->line("Staff: " . ($appointment->staff ? "{$appointment->staff->first_name} {$appointment->staff->last_name}" : 'N/A'));
        $this->line("Branch: {$appointment->branch->name}");
        $this->line("Company: {$appointment->branch->company->name}");
        
        if (!$appointment->customer->email) {
            $this->error("\nCustomer has no email address. Cannot send email.");
            return 1;
        }
        
        $this->info("\nDispatching email job...");
        
        try {
            // Dispatch the email job
            SendAppointmentEmailJob::dispatch(
                $appointment,
                'confirmation',
                $locale
            );
            
            $this->info("Email job dispatched successfully!");
            $this->line("Check the queue worker logs to see the email being processed.");
            $this->line("Run 'php artisan queue:work --queue=emails' to process the email queue.");
            
            return 0;
        } catch (\Exception $e) {
            $this->error("Failed to dispatch email job: " . $e->getMessage());
            return 1;
        }
    }
}
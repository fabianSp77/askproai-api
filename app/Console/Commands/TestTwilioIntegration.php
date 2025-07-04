<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\MCP\TwilioMCPServer;
use App\Models\Company;
use App\Models\Customer;
use App\Models\Appointment;
use Carbon\Carbon;

class TestTwilioIntegration extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:twilio 
                            {action : Action to perform (sms|whatsapp|validate|reminder)}
                            {--phone= : Phone number to test}
                            {--message= : Message to send}
                            {--company-id= : Company ID to use}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test Twilio SMS/WhatsApp integration';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $action = $this->argument('action');
        $twilioMCP = new TwilioMCPServer();
        
        switch ($action) {
            case 'sms':
                $this->testSms($twilioMCP);
                break;
                
            case 'whatsapp':
                $this->testWhatsApp($twilioMCP);
                break;
                
            case 'validate':
                $this->testValidation($twilioMCP);
                break;
                
            case 'reminder':
                $this->testReminder($twilioMCP);
                break;
                
            default:
                $this->error("Invalid action. Use: sms, whatsapp, validate, or reminder");
        }
    }
    
    protected function testSms(TwilioMCPServer $twilioMCP)
    {
        $phone = $this->option('phone') ?? $this->ask('Enter phone number (e.g., +49123456789)');
        $message = $this->option('message') ?? 'This is a test SMS from AskProAI. If you receive this, the integration is working! 🎉';
        $companyId = $this->option('company-id');
        
        $this->info("Sending SMS to: $phone");
        
        try {
            $result = $twilioMCP->sendSms([
                'to' => $phone,
                'message' => $message,
                'company_id' => $companyId
            ]);
            
            if ($result['success']) {
                $this->info("✅ SMS sent successfully!");
                $this->table(
                    ['Field', 'Value'],
                    [
                        ['Message SID', $result['message_sid']],
                        ['To', $result['to']],
                        ['From', $result['from']],
                        ['Status', $result['status']],
                        ['Price', $result['price'] . ' ' . $result['price_unit']],
                        ['Segments', $result['segments']]
                    ]
                );
            } else {
                $this->error("❌ Failed to send SMS");
            }
        } catch (\Exception $e) {
            $this->error("Error: " . $e->getMessage());
        }
    }
    
    protected function testWhatsApp(TwilioMCPServer $twilioMCP)
    {
        $phone = $this->option('phone') ?? $this->ask('Enter WhatsApp number (e.g., +49123456789)');
        $message = $this->option('message') ?? 'Hello from AskProAI! 👋 This is a test WhatsApp message. Your appointment system is ready to send notifications via WhatsApp.';
        $companyId = $this->option('company-id');
        
        $this->info("Sending WhatsApp message to: $phone");
        
        try {
            $result = $twilioMCP->sendWhatsapp([
                'to' => $phone,
                'message' => $message,
                'company_id' => $companyId
            ]);
            
            if ($result['success']) {
                $this->info("✅ WhatsApp message sent successfully!");
                $this->table(
                    ['Field', 'Value'],
                    [
                        ['Message SID', $result['message_sid']],
                        ['To', $result['to']],
                        ['From', $result['from']],
                        ['Status', $result['status']],
                        ['Price', $result['price'] . ' ' . $result['price_unit']]
                    ]
                );
            } else {
                $this->error("❌ Failed to send WhatsApp message");
            }
        } catch (\Exception $e) {
            $this->error("Error: " . $e->getMessage());
        }
    }
    
    protected function testValidation(TwilioMCPServer $twilioMCP)
    {
        $phone = $this->option('phone') ?? $this->ask('Enter phone number to validate');
        
        $this->info("Validating phone number: $phone");
        
        try {
            $result = $twilioMCP->validatePhoneNumber([
                'phone_number' => $phone,
                'country_code' => 'DE'
            ]);
            
            if ($result['valid']) {
                $this->info("✅ Phone number is valid!");
                $this->table(
                    ['Field', 'Value'],
                    [
                        ['Valid', $result['valid'] ? 'Yes' : 'No'],
                        ['Formatted', $result['formatted']],
                        ['National', $result['national']],
                        ['Country Code', $result['country_code']]
                    ]
                );
            } else {
                $this->error("❌ Invalid phone number: " . ($result['error'] ?? 'Unknown error'));
            }
        } catch (\Exception $e) {
            $this->error("Error: " . $e->getMessage());
        }
    }
    
    protected function testReminder(TwilioMCPServer $twilioMCP)
    {
        // Create a test appointment
        $company = Company::first();
        if (!$company) {
            $this->error("No company found in database");
            return;
        }
        
        $customer = Customer::factory()->create([
            'company_id' => $company->id,
            'name' => 'Test Customer',
            'phone' => $this->option('phone') ?? '+49 30 12345678',
            'email' => 'test@example.com',
            'sms_opt_in' => true,
            'whatsapp_opt_in' => true,
            'preferred_language' => 'de'
        ]);
        
        $appointment = Appointment::factory()->create([
            'company_id' => $company->id,
            'customer_id' => $customer->id,
            'starts_at' => Carbon::now()->addDay(),
            'ends_at' => Carbon::now()->addDay()->addHour(),
            'status' => 'confirmed'
        ]);
        
        $this->info("Testing appointment reminder for appointment ID: " . $appointment->id);
        
        // Test SMS reminder
        if ($this->confirm('Send SMS reminder?')) {
            try {
                $result = $twilioMCP->sendAppointmentReminder([
                    'appointment_id' => $appointment->id,
                    'channel' => 'sms'
                ]);
                
                $this->info("✅ SMS reminder sent!");
                $this->info("Message SID: " . $result['message_sid']);
            } catch (\Exception $e) {
                $this->error("SMS Error: " . $e->getMessage());
            }
        }
        
        // Test WhatsApp reminder
        if ($this->confirm('Send WhatsApp reminder?')) {
            try {
                $result = $twilioMCP->sendAppointmentReminder([
                    'appointment_id' => $appointment->id,
                    'channel' => 'whatsapp'
                ]);
                
                $this->info("✅ WhatsApp reminder sent!");
                $this->info("Message SID: " . $result['message_sid']);
            } catch (\Exception $e) {
                $this->error("WhatsApp Error: " . $e->getMessage());
            }
        }
        
        // Clean up test data
        if ($this->confirm('Delete test data?')) {
            $appointment->delete();
            $customer->delete();
            $this->info("Test data cleaned up");
        }
    }
}
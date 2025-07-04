<?php

namespace App\Services\MCP;

use App\Models\Company;
use App\Models\Customer;
use App\Exceptions\MCPException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

/**
 * MCP Server for Twilio Integration
 * 
 * Handles SMS and WhatsApp messaging through Twilio's API
 */
class TwilioMCPServer
{
    protected ?string $accountSid = null;
    protected ?string $authToken = null;
    protected ?string $fromNumber = null;
    protected ?string $whatsappFrom = null;
    protected bool $sandboxMode = false;
    
    /**
     * Get MCP server information
     */
    public function getServerInfo(): array
    {
        return [
            'name' => 'askproai_twilio',
            'version' => '1.0.0',
            'description' => 'Twilio SMS and WhatsApp messaging integration for AskProAI',
            'functions' => [
                'send_sms',
                'send_whatsapp',
                'validate_phone_number',
                'check_message_status',
                'list_phone_numbers',
                'send_appointment_reminder',
                'send_bulk_sms',
                'get_messaging_rates'
            ]
        ];
    }
    
    /**
     * Initialize Twilio configuration
     */
    protected function initialize(?int $companyId = null): void
    {
        if ($companyId) {
            $company = Company::find($companyId);
            if ($company && $company->twilio_account_sid) {
                $this->accountSid = decrypt($company->twilio_account_sid);
                $this->authToken = decrypt($company->twilio_auth_token);
                $this->fromNumber = $company->twilio_phone_number;
                $this->whatsappFrom = $company->twilio_whatsapp_number ?? config('services.twilio.whatsapp_from');
                return;
            }
        }
        
        // Fall back to default config
        $this->accountSid = config('services.twilio.sid');
        $this->authToken = config('services.twilio.token');
        $this->fromNumber = config('services.twilio.from');
        $this->whatsappFrom = config('services.twilio.whatsapp_from');
        $this->sandboxMode = config('services.twilio.sandbox_mode', false);
    }
    
    /**
     * Send SMS message
     */
    public function sendSms(array $params): array
    {
        $this->validateParams($params, ['to', 'message']);
        $this->initialize($params['company_id'] ?? null);
        
        if (!$this->accountSid || !$this->authToken) {
            throw new MCPException('Twilio credentials not configured');
        }
        
        try {
            $to = $this->formatPhoneNumber($params['to']);
            $message = $params['message'];
            
            // Check message length
            if (strlen($message) > 1600) {
                throw new MCPException('Message exceeds maximum length of 1600 characters');
            }
            
            // Check if customer has opted in
            if (isset($params['customer_id'])) {
                $customer = Customer::find($params['customer_id']);
                if ($customer && !$customer->sms_opt_in) {
                    throw new MCPException('Customer has not opted in for SMS notifications');
                }
            }
            
            // Send via Twilio API
            $response = Http::withBasicAuth($this->accountSid, $this->authToken)
                ->asForm()
                ->post("https://api.twilio.com/2010-04-01/Accounts/{$this->accountSid}/Messages.json", [
                    'From' => $this->fromNumber,
                    'To' => $to,
                    'Body' => $message,
                    'StatusCallback' => url('/api/mcp/twilio/status-callback'),
                ]);
            
            if (!$response->successful()) {
                $error = $response->json();
                throw new MCPException($error['message'] ?? 'Failed to send SMS');
            }
            
            $data = $response->json();
            
            // Log the message
            $this->logMessage('sms', $to, $message, $data);
            
            return [
                'success' => true,
                'message_sid' => $data['sid'],
                'to' => $data['to'],
                'from' => $data['from'],
                'status' => $data['status'],
                'price' => $data['price'],
                'price_unit' => $data['price_unit'],
                'segments' => $data['num_segments'] ?? 1
            ];
            
        } catch (\Exception $e) {
            Log::error('Twilio SMS failed', [
                'to' => $params['to'],
                'error' => $e->getMessage()
            ]);
            
            throw new MCPException('Failed to send SMS: ' . $e->getMessage());
        }
    }
    
    /**
     * Send WhatsApp message
     */
    public function sendWhatsapp(array $params): array
    {
        $this->validateParams($params, ['to', 'message']);
        $this->initialize($params['company_id'] ?? null);
        
        if (!$this->accountSid || !$this->authToken) {
            throw new MCPException('Twilio credentials not configured');
        }
        
        try {
            $to = $this->formatPhoneNumber($params['to'], 'whatsapp');
            $message = $params['message'];
            
            // Check if customer has opted in
            if (isset($params['customer_id'])) {
                $customer = Customer::find($params['customer_id']);
                if ($customer && !$customer->whatsapp_opt_in) {
                    throw new MCPException('Customer has not opted in for WhatsApp notifications');
                }
            }
            
            // Format WhatsApp numbers
            $from = $this->whatsappFrom;
            if (!str_starts_with($from, 'whatsapp:')) {
                $from = 'whatsapp:' . $from;
            }
            if (!str_starts_with($to, 'whatsapp:')) {
                $to = 'whatsapp:' . $to;
            }
            
            // Send via Twilio API
            $response = Http::withBasicAuth($this->accountSid, $this->authToken)
                ->asForm()
                ->post("https://api.twilio.com/2010-04-01/Accounts/{$this->accountSid}/Messages.json", [
                    'From' => $from,
                    'To' => $to,
                    'Body' => $message,
                    'StatusCallback' => url('/api/mcp/twilio/status-callback'),
                ]);
            
            if (!$response->successful()) {
                $error = $response->json();
                throw new MCPException($error['message'] ?? 'Failed to send WhatsApp message');
            }
            
            $data = $response->json();
            
            // Log the message
            $this->logMessage('whatsapp', $to, $message, $data);
            
            return [
                'success' => true,
                'message_sid' => $data['sid'],
                'to' => $data['to'],
                'from' => $data['from'],
                'status' => $data['status'],
                'price' => $data['price'],
                'price_unit' => $data['price_unit']
            ];
            
        } catch (\Exception $e) {
            Log::error('Twilio WhatsApp failed', [
                'to' => $params['to'],
                'error' => $e->getMessage()
            ]);
            
            throw new MCPException('Failed to send WhatsApp message: ' . $e->getMessage());
        }
    }
    
    /**
     * Send appointment reminder
     */
    public function sendAppointmentReminder(array $params): array
    {
        $this->validateParams($params, ['appointment_id', 'channel']);
        
        $appointmentId = $params['appointment_id'];
        $channel = $params['channel']; // 'sms' or 'whatsapp'
        
        // Get appointment with relations
        $appointment = DB::table('appointments')
            ->join('customers', 'appointments.customer_id', '=', 'customers.id')
            ->join('services', 'appointments.service_id', '=', 'services.id')
            ->join('branches', 'appointments.branch_id', '=', 'branches.id')
            ->leftJoin('staff', 'appointments.staff_id', '=', 'staff.id')
            ->where('appointments.id', $appointmentId)
            ->select(
                'appointments.*',
                'customers.name as customer_name',
                'customers.phone as customer_phone',
                'customers.preferred_language',
                'customers.sms_opt_in',
                'customers.whatsapp_opt_in',
                'customers.id as customer_id',
                'services.name as service_name',
                'branches.name as branch_name',
                'branches.address as branch_address',
                'staff.first_name as staff_first_name',
                'staff.last_name as staff_last_name'
            )
            ->first();
        
        if (!$appointment) {
            throw new MCPException('Appointment not found');
        }
        
        // Format appointment time
        $appointmentTime = Carbon::parse($appointment->starts_at);
        $lang = $appointment->preferred_language ?? 'de';
        
        // Generate reminder message
        if ($lang === 'de') {
            $message = sprintf(
                "Erinnerung: Ihr Termin am %s um %s Uhr bei %s. Adresse: %s",
                $appointmentTime->format('d.m.Y'),
                $appointmentTime->format('H:i'),
                $appointment->branch_name,
                $appointment->branch_address
            );
            
            if ($appointment->staff_first_name) {
                $message .= sprintf(" bei %s %s", $appointment->staff_first_name, $appointment->staff_last_name);
            }
        } else {
            $message = sprintf(
                "Reminder: Your appointment on %s at %s at %s. Address: %s",
                $appointmentTime->format('m/d/Y'),
                $appointmentTime->format('g:i A'),
                $appointment->branch_name,
                $appointment->branch_address
            );
            
            if ($appointment->staff_first_name) {
                $message .= sprintf(" with %s %s", $appointment->staff_first_name, $appointment->staff_last_name);
            }
        }
        
        // Send based on channel
        if ($channel === 'sms') {
            return $this->sendSms([
                'to' => $appointment->customer_phone,
                'message' => $message,
                'customer_id' => $appointment->customer_id,
                'company_id' => $appointment->company_id
            ]);
        } else {
            return $this->sendWhatsapp([
                'to' => $appointment->customer_phone,
                'message' => $message,
                'customer_id' => $appointment->customer_id,
                'company_id' => $appointment->company_id
            ]);
        }
    }
    
    /**
     * Validate phone number
     */
    public function validatePhoneNumber(array $params): array
    {
        $this->validateParams($params, ['phone_number']);
        $this->initialize($params['company_id'] ?? null);
        
        $phoneNumber = $params['phone_number'];
        $countryCode = $params['country_code'] ?? 'DE';
        
        try {
            // Basic validation
            $cleaned = preg_replace('/[^0-9+]/', '', $phoneNumber);
            
            // Check if it starts with country code
            if (!str_starts_with($cleaned, '+')) {
                // Add default country code
                if ($countryCode === 'DE' && str_starts_with($cleaned, '0')) {
                    $cleaned = '+49' . substr($cleaned, 1);
                } else {
                    $cleaned = '+49' . $cleaned;
                }
            }
            
            // Validate length (German numbers)
            if (str_starts_with($cleaned, '+49')) {
                $nationalNumber = substr($cleaned, 3);
                if (strlen($nationalNumber) < 10 || strlen($nationalNumber) > 12) {
                    return [
                        'valid' => false,
                        'error' => 'Invalid German phone number length'
                    ];
                }
            }
            
            return [
                'valid' => true,
                'formatted' => $cleaned,
                'national' => substr($cleaned, 3),
                'country_code' => substr($cleaned, 0, 3)
            ];
            
        } catch (\Exception $e) {
            return [
                'valid' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Check message status
     */
    public function checkMessageStatus(array $params): array
    {
        $this->validateParams($params, ['message_sid']);
        $this->initialize($params['company_id'] ?? null);
        
        if (!$this->accountSid || !$this->authToken) {
            throw new MCPException('Twilio credentials not configured');
        }
        
        try {
            $response = Http::withBasicAuth($this->accountSid, $this->authToken)
                ->get("https://api.twilio.com/2010-04-01/Accounts/{$this->accountSid}/Messages/{$params['message_sid']}.json");
            
            if (!$response->successful()) {
                throw new MCPException('Failed to fetch message status');
            }
            
            $data = $response->json();
            
            return [
                'message_sid' => $data['sid'],
                'status' => $data['status'],
                'error_code' => $data['error_code'],
                'error_message' => $data['error_message'],
                'date_sent' => $data['date_sent'],
                'date_updated' => $data['date_updated'],
                'price' => $data['price'],
                'price_unit' => $data['price_unit']
            ];
            
        } catch (\Exception $e) {
            throw new MCPException('Failed to check message status: ' . $e->getMessage());
        }
    }
    
    /**
     * Get messaging rates for a country
     */
    public function getMessagingRates(array $params): array
    {
        $this->validateParams($params, ['country_code']);
        
        $countryCode = strtoupper($params['country_code']);
        $channel = $params['channel'] ?? 'sms'; // 'sms' or 'whatsapp'
        
        // Hardcoded rates for common countries (in USD)
        $smsRates = [
            'DE' => 0.075,  // Germany
            'AT' => 0.076,  // Austria
            'CH' => 0.065,  // Switzerland
            'US' => 0.0075, // United States
            'GB' => 0.040,  // United Kingdom
            'FR' => 0.073,  // France
            'IT' => 0.074,  // Italy
            'ES' => 0.073,  // Spain
        ];
        
        $whatsappRates = [
            'DE' => 0.065,
            'AT' => 0.065,
            'CH' => 0.065,
            'US' => 0.010,
            'GB' => 0.055,
            'FR' => 0.065,
            'IT' => 0.065,
            'ES' => 0.065,
        ];
        
        $rates = $channel === 'whatsapp' ? $whatsappRates : $smsRates;
        $rate = $rates[$countryCode] ?? 0.10; // Default rate
        
        return [
            'country_code' => $countryCode,
            'channel' => $channel,
            'rate' => $rate,
            'currency' => 'USD',
            'rate_eur' => round($rate * 0.92, 4), // Approximate EUR conversion
            'segments_included' => 1,
            'note' => 'Rates are approximate and may vary based on volume and carrier'
        ];
    }
    
    /**
     * Log message to database
     */
    protected function logMessage(string $channel, string $to, string $message, array $twilioData): void
    {
        try {
            DB::table('sms_message_logs')->insert([
                'company_id' => $twilioData['account_sid'] === $this->accountSid ? null : 1,
                'channel' => $channel,
                'to' => $to,
                'from' => $twilioData['from'] ?? $this->fromNumber,
                'message' => $message,
                'twilio_sid' => $twilioData['sid'],
                'status' => $twilioData['status'],
                'price' => $twilioData['price'],
                'price_unit' => $twilioData['price_unit'],
                'segments' => $twilioData['num_segments'] ?? 1,
                'metadata' => json_encode([
                    'date_created' => $twilioData['date_created'],
                    'direction' => $twilioData['direction'] ?? 'outbound-api',
                    'sandbox_mode' => $this->sandboxMode
                ]),
                'created_at' => now(),
                'updated_at' => now()
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to log SMS message', [
                'error' => $e->getMessage(),
                'channel' => $channel,
                'to' => $to
            ]);
        }
    }
    
    /**
     * Format phone number for Twilio
     */
    protected function formatPhoneNumber(string $phoneNumber, string $type = 'sms'): string
    {
        // Remove all non-numeric characters except +
        $cleaned = preg_replace('/[^0-9+]/', '', $phoneNumber);
        
        // Ensure it has a country code
        if (!str_starts_with($cleaned, '+')) {
            // Assume German number if no country code
            if (str_starts_with($cleaned, '0')) {
                $cleaned = '+49' . substr($cleaned, 1);
            } else {
                $cleaned = '+49' . $cleaned;
            }
        }
        
        return $cleaned;
    }
    
    /**
     * Validate required parameters
     */
    protected function validateParams(array $params, array $required): void
    {
        foreach ($required as $param) {
            if (!isset($params[$param]) || empty($params[$param])) {
                throw new MCPException("Missing required parameter: {$param}");
            }
        }
    }
    
    /**
     * Send bulk SMS
     */
    public function sendBulkSms(array $params): array
    {
        $this->validateParams($params, ['recipients', 'message']);
        
        $recipients = $params['recipients']; // Array of phone numbers
        $message = $params['message'];
        $results = [];
        $successCount = 0;
        $failureCount = 0;
        
        foreach ($recipients as $recipient) {
            try {
                $result = $this->sendSms([
                    'to' => $recipient,
                    'message' => $message,
                    'company_id' => $params['company_id'] ?? null
                ]);
                
                $results[] = [
                    'to' => $recipient,
                    'success' => true,
                    'message_sid' => $result['message_sid']
                ];
                $successCount++;
                
            } catch (\Exception $e) {
                $results[] = [
                    'to' => $recipient,
                    'success' => false,
                    'error' => $e->getMessage()
                ];
                $failureCount++;
            }
            
            // Rate limiting - 1 message per second
            if (count($recipients) > 1) {
                sleep(1);
            }
        }
        
        return [
            'total' => count($recipients),
            'success' => $successCount,
            'failed' => $failureCount,
            'results' => $results
        ];
    }
    
    /**
     * List available phone numbers for a company
     */
    public function listPhoneNumbers(array $params): array
    {
        $companyId = $params['company_id'] ?? null;
        
        $numbers = [];
        
        // Get company-specific numbers
        if ($companyId) {
            $company = Company::find($companyId);
            if ($company) {
                if ($company->twilio_phone_number) {
                    $numbers[] = [
                        'number' => $company->twilio_phone_number,
                        'type' => 'sms',
                        'capabilities' => ['sms', 'voice'],
                        'company_specific' => true
                    ];
                }
                
                if ($company->twilio_whatsapp_number) {
                    $numbers[] = [
                        'number' => $company->twilio_whatsapp_number,
                        'type' => 'whatsapp',
                        'capabilities' => ['whatsapp'],
                        'company_specific' => true
                    ];
                }
            }
        }
        
        // Add default numbers
        if ($this->fromNumber) {
            $numbers[] = [
                'number' => $this->fromNumber,
                'type' => 'sms',
                'capabilities' => ['sms', 'voice'],
                'company_specific' => false,
                'default' => true
            ];
        }
        
        if ($this->whatsappFrom) {
            $numbers[] = [
                'number' => $this->whatsappFrom,
                'type' => 'whatsapp',
                'capabilities' => ['whatsapp'],
                'company_specific' => false,
                'default' => true,
                'sandbox' => $this->sandboxMode
            ];
        }
        
        return [
            'numbers' => $numbers,
            'sandbox_mode' => $this->sandboxMode
        ];
    }
}
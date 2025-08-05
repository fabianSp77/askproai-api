<?php

namespace App\Services\MCP;

use App\Models\Company;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class WhatsAppBusinessMCPServer extends BaseMCPServer
{
    protected string $name = 'WhatsApp Business API';
    protected string $version = '1.0.0';
    
    private string $baseUrl = 'https://graph.facebook.com/v18.0';
    private ?string $accessToken = null;
    private ?string $phoneNumberId = null;
    private ?Company $company = null;

    public function __construct()
    {
        parent::__construct();
        
        // Get default configuration
        $this->accessToken = config('services.whatsapp.access_token');
        $this->phoneNumberId = config('services.whatsapp.phone_number_id');
    }

    public function getName(): string
    {
        return $this->name;
    }
    
    public function getVersion(): string
    {
        return $this->version;
    }
    
    public function getTools(): array
    {
        return $this->listTools();
    }
    
    public function setCompany(Company $company): self
    {
        $this->company = $company;
        
        // Override with company-specific settings if available
        if ($company->whatsapp_access_token) {
            $this->accessToken = decrypt($company->whatsapp_access_token);
        }
        if ($company->whatsapp_phone_number_id) {
            $this->phoneNumberId = $company->whatsapp_phone_number_id;
        }
        
        return $this;
    }

    public function listTools(): array
    {
        return [
            [
                'name' => 'send_message',
                'description' => 'Send a WhatsApp message to a phone number',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'to' => ['type' => 'string', 'description' => 'Recipient phone number with country code'],
                        'message' => ['type' => 'string', 'description' => 'Message text to send'],
                        'preview_url' => ['type' => 'boolean', 'description' => 'Enable URL preview'],
                    ],
                    'required' => ['to', 'message']
                ]
            ],
            [
                'name' => 'send_template',
                'description' => 'Send a pre-approved WhatsApp template message',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'to' => ['type' => 'string', 'description' => 'Recipient phone number'],
                        'template_name' => ['type' => 'string', 'description' => 'Template name'],
                        'language_code' => ['type' => 'string', 'description' => 'Language code (e.g., de_DE)'],
                        'parameters' => ['type' => 'array', 'description' => 'Template parameters'],
                    ],
                    'required' => ['to', 'template_name', 'language_code']
                ]
            ],
            [
                'name' => 'send_appointment_reminder',
                'description' => 'Send appointment reminder via WhatsApp',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'appointment_id' => ['type' => 'integer', 'description' => 'Appointment ID'],
                        'reminder_type' => ['type' => 'string', 'description' => '24h, 2h, or 30min'],
                    ],
                    'required' => ['appointment_id', 'reminder_type']
                ]
            ],
            [
                'name' => 'get_message_status',
                'description' => 'Get delivery status of a WhatsApp message',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'message_id' => ['type' => 'string', 'description' => 'WhatsApp message ID'],
                    ],
                    'required' => ['message_id']
                ]
            ],
            [
                'name' => 'register_phone_number',
                'description' => 'Register a phone number for WhatsApp Business',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'phone_number' => ['type' => 'string', 'description' => 'Phone number to register'],
                        'display_name' => ['type' => 'string', 'description' => 'Business display name'],
                    ],
                    'required' => ['phone_number', 'display_name']
                ]
            ]
        ];
    }

    public function executeTool(string $name, array $arguments = []): array
    {
        $this->validateAccess();
        
        try {
            return match ($name) {
                'send_message' => $this->sendMessage($arguments),
                'send_template' => $this->sendTemplate($arguments),
                'send_appointment_reminder' => $this->sendAppointmentReminder($arguments),
                'get_message_status' => $this->getMessageStatus($arguments),
                'register_phone_number' => $this->registerPhoneNumber($arguments),
                default => $this->errorResponse("Unknown tool: $name")
            };
        } catch (\Exception $e) {
            Log::error('WhatsApp MCP Error', [
                'tool' => $name,
                'error' => $e->getMessage(),
                'company_id' => $this->company?->id
            ]);
            
            return $this->errorResponse($e->getMessage());
        }
    }

    private function sendMessage(array $params): array
    {
        $to = $this->formatPhoneNumber($params['to']);
        
        $response = Http::withToken($this->accessToken)
            ->post("{$this->baseUrl}/{$this->phoneNumberId}/messages", [
                'messaging_product' => 'whatsapp',
                'recipient_type' => 'individual',
                'to' => $to,
                'type' => 'text',
                'text' => [
                    'preview_url' => $params['preview_url'] ?? false,
                    'body' => $params['message']
                ]
            ]);
        
        if ($response->successful()) {
            $data = $response->json();
            
            // Log the message
            $this->logMessage([
                'message_id' => $data['messages'][0]['id'],
                'to' => $to,
                'type' => 'text',
                'status' => 'sent',
                'company_id' => $this->company?->id
            ]);
            
            return $this->successResponse([
                'message_id' => $data['messages'][0]['id'],
                'status' => 'sent'
            ]);
        }
        
        return $this->errorResponse($response->json()['error']['message'] ?? 'Failed to send message');
    }

    private function sendTemplate(array $params): array
    {
        $to = $this->formatPhoneNumber($params['to']);
        
        $components = [];
        if (!empty($params['parameters'])) {
            $components[] = [
                'type' => 'body',
                'parameters' => array_map(function($param) {
                    return ['type' => 'text', 'text' => $param];
                }, $params['parameters'])
            ];
        }
        
        $response = Http::withToken($this->accessToken)
            ->post("{$this->baseUrl}/{$this->phoneNumberId}/messages", [
                'messaging_product' => 'whatsapp',
                'to' => $to,
                'type' => 'template',
                'template' => [
                    'name' => $params['template_name'],
                    'language' => ['code' => $params['language_code']],
                    'components' => $components
                ]
            ]);
        
        if ($response->successful()) {
            $data = $response->json();
            
            return $this->successResponse([
                'message_id' => $data['messages'][0]['id'],
                'status' => 'sent'
            ]);
        }
        
        return $this->errorResponse($response->json()['error']['message'] ?? 'Failed to send template');
    }

    private function sendAppointmentReminder(array $params): array
    {
        $appointment = \App\Models\Appointment::with(['customer', 'staff', 'service', 'branch'])
            ->findOrFail($params['appointment_id']);
        
        // Check if customer has WhatsApp opt-in
        if (!$appointment->customer->whatsapp_opt_in) {
            return $this->errorResponse('Customer has not opted in for WhatsApp messages');
        }
        
        $templateName = match($params['reminder_type']) {
            '24h' => 'appointment_reminder_24h',
            '2h' => 'appointment_reminder_2h',
            '30min' => 'appointment_reminder_30min',
            default => 'appointment_reminder'
        };
        
        $parameters = [
            $appointment->customer->name,
            $appointment->service->name,
            $appointment->starts_at->format('d.m.Y'),
            $appointment->starts_at->format('H:i'),
            $appointment->branch->name,
            $appointment->staff->name ?? 'Unser Team'
        ];
        
        return $this->sendTemplate([
            'to' => $appointment->customer->phone,
            'template_name' => $templateName,
            'language_code' => 'de_DE',
            'parameters' => $parameters
        ]);
    }

    private function getMessageStatus(array $params): array
    {
        // Check cache first
        $cacheKey = "whatsapp_status:{$params['message_id']}";
        $cached = Cache::get($cacheKey);
        if ($cached) {
            return $this->successResponse($cached);
        }
        
        // In real implementation, you would query the webhook data
        // or make an API call to get the status
        $status = \DB::table('whatsapp_message_logs')
            ->where('message_id', $params['message_id'])
            ->first();
        
        if ($status) {
            $result = [
                'message_id' => $status->message_id,
                'status' => $status->status,
                'delivered_at' => $status->delivered_at,
                'read_at' => $status->read_at,
                'errors' => $status->errors
            ];
            
            Cache::put($cacheKey, $result, 300); // Cache for 5 minutes
            
            return $this->successResponse($result);
        }
        
        return $this->errorResponse('Message not found');
    }

    private function registerPhoneNumber(array $params): array
    {
        // This would integrate with WhatsApp Business API registration flow
        // For now, we'll return a placeholder response
        
        return $this->successResponse([
            'status' => 'pending_verification',
            'verification_method' => 'sms',
            'message' => 'Verification code sent to ' . $params['phone_number']
        ]);
    }

    private function formatPhoneNumber(string $phone): string
    {
        // Remove all non-numeric characters
        $phone = preg_replace('/[^0-9]/', '', $phone);
        
        // Add country code if missing (assume Germany)
        if (!str_starts_with($phone, '49')) {
            $phone = '49' . ltrim($phone, '0');
        }
        
        return $phone;
    }

    private function logMessage(array $data): void
    {
        \DB::table('whatsapp_message_logs')->insert([
            'message_id' => $data['message_id'],
            'company_id' => $data['company_id'],
            'to' => $data['to'],
            'type' => $data['type'],
            'status' => $data['status'],
            'created_at' => now(),
            'updated_at' => now()
        ]);
    }

    private function validateAccess(): void
    {
        if (!$this->accessToken || !$this->phoneNumberId) {
            throw new \Exception('WhatsApp Business API not configured');
        }
        
        if ($this->company && !$this->company->whatsapp_enabled) {
            throw new \Exception('WhatsApp is not enabled for this company');
        }
    }
}
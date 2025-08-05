<?php

namespace App\Services\MCP;

use App\Models\Customer;
use App\Models\Company;
use App\Models\Branch;
use App\Models\Call;
use App\Models\WebhookEvent;
use App\Exceptions\SecurityException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Services\SecureAppointmentBookingService;
use App\Jobs\ProcessRetellCallEndedJob;
use App\Events\WebhookReceived;

/**
 * SECURE VERSION: Webhook MCP Server with proper tenant isolation
 * 
 * This server handles webhook processing with strict multi-tenant security.
 * All operations are scoped to the authenticated company context.
 * 
 * Security Features:
 * - Mandatory company context validation
 * - No cross-tenant data access
 * - Audit logging for all webhook operations
 * - Secure customer resolution
 * - No arbitrary company fallbacks
 */
class SecureWebhookMCPServer extends BaseMCPServer
{
    /**
     * @var Company|null Current company context
     */
    protected ?Company $company = null;
    
    /**
     * @var bool Audit logging enabled
     */
    protected bool $auditEnabled = true;
    
    /**
     * Constructor - resolves company context
     */
    public function __construct()
    {
        parent::__construct();
        $this->resolveCompanyContext();
    }
    
    /**
     * Set company context explicitly (only for super admins or system operations)
     */
    public function setCompanyContext(Company $company): self
    {
        // Only allow super admins or system operations to override context
        if (Auth::check() && !Auth::user()->hasRole('super_admin')) {
            throw new SecurityException('Unauthorized company context override');
        }
        
        $this->company = $company;
        
        $this->auditAccess('company_context_override', [
            'company_id' => $company->id,
            'user_id' => Auth::id(),
        ]);
        
        return $this;
    }
    
    /**
     * Get server info
     */
    public function getServerInfo(): array
    {
        return [
            'name' => 'secure-webhook-mcp-server',
            'version' => '1.0.0',
            'description' => 'Secure webhook processing with tenant isolation',
            'endpoints' => [
                'process-retell-webhook',
                'process-calcom-webhook',
                'process-stripe-webhook',
                'list-webhook-events',
                'replay-webhook-event'
            ]
        ];
    }
    
    /**
     * Execute a webhook operation
     */
    public function execute(string $operation, array $params = []): array
    {
        $this->ensureCompanyContext();
        
        $this->logDebug("Executing secure webhook operation", [
            'operation' => $operation,
            'params' => $params,
            'company_id' => $this->company->id
        ]);
        
        try {
            switch ($operation) {
                case 'process-retell-webhook':
                    return $this->processRetellWebhookSecure($params);
                    
                case 'process-calcom-webhook':
                    return $this->processCalcomWebhookSecure($params);
                    
                case 'process-stripe-webhook':
                    return $this->processStripeWebhookSecure($params);
                    
                case 'list-webhook-events':
                    return $this->listWebhookEventsSecure($params);
                    
                case 'replay-webhook-event':
                    return $this->replayWebhookEventSecure($params);
                    
                default:
                    return $this->errorResponse("Unknown operation: {$operation}");
            }
        } catch (\Exception $e) {
            $this->logError("Secure webhook operation failed", $e, [
                'operation' => $operation,
                'company_id' => $this->company->id
            ]);
            return $this->errorResponse($e->getMessage());
        }
    }
    
    /**
     * Process Retell webhook with security validation
     */
    protected function processRetellWebhookSecure(array $params): array
    {
        $this->validateParams($params, ['event_type', 'call']);
        
        $eventType = $params['event_type'];
        $callData = $params['call'];
        
        // Validate call belongs to company by phone number
        if (!$this->validateCallBelongsToCompany($callData)) {
            throw new SecurityException('Call does not belong to authenticated company');
        }
        
        $this->auditAccess('process_retell_webhook', [
            'event_type' => $eventType,
            'call_id' => $callData['call_id'] ?? null
        ]);
        
        // Store webhook event
        $webhookEvent = WebhookEvent::create([
            'company_id' => $this->company->id, // Force company context
            'source' => 'retell',
            'event_type' => $eventType,
            'payload' => json_encode($params),
            'signature' => $params['signature'] ?? null,
            'processed_at' => null
        ]);
        
        // Process based on event type
        switch ($eventType) {
            case 'call_ended':
                return $this->processRetellCallEndedSecure($callData, $webhookEvent);
                
            case 'call_started':
                return $this->processRetellCallStartedSecure($callData, $webhookEvent);
                
            default:
                Log::info('SecureWebhook: Unhandled Retell event type', [
                    'event_type' => $eventType,
                    'company_id' => $this->company->id
                ]);
                
                $webhookEvent->update(['processed_at' => now()]);
                
                return $this->successResponse([
                    'message' => 'Event received',
                    'event_type' => $eventType
                ]);
        }
    }
    
    /**
     * Process Retell call ended event securely
     */
    protected function processRetellCallEndedSecure(array $callData, WebhookEvent $webhookEvent): array
    {
        // Find or create customer with company validation
        $customer = null;
        if (!empty($callData['from_number'])) {
            $customer = $this->findOrCreateCustomerSecure([
                'phone' => $callData['from_number'],
                'company_id' => $this->company->id
            ]);
        }
        
        // Create or update call record
        $call = Call::updateOrCreate(
            [
                'retell_call_id' => $callData['call_id'],
                'company_id' => $this->company->id // Force company context
            ],
            [
                'from_number' => $callData['from_number'] ?? null,
                'to_number' => $callData['to_number'] ?? null,
                'customer_id' => $customer?->id,
                'branch_id' => $this->resolveBranchFromPhoneSecure($callData['to_number'] ?? null),
                'status' => 'ended',
                'duration_sec' => $callData['duration'] ?? 0,
                'recording_url' => $callData['recording_url'] ?? null,
                'transcript' => $callData['transcript'] ?? null,
                'metadata' => json_encode($callData),
                'ended_at' => now()
            ]
        );
        
        // Queue job for appointment processing
        ProcessRetellCallEndedJob::dispatch($call)->onQueue('webhooks');
        
        $webhookEvent->update(['processed_at' => now()]);
        
        return $this->successResponse([
            'message' => 'Call ended webhook processed',
            'call_id' => $call->id,
            'customer_id' => $customer?->id
        ]);
    }
    
    /**
     * Process Retell call started event securely
     */
    protected function processRetellCallStartedSecure(array $callData, WebhookEvent $webhookEvent): array
    {
        // Create call record
        $call = Call::create([
            'company_id' => $this->company->id, // Force company context
            'retell_call_id' => $callData['call_id'],
            'from_number' => $callData['from_number'] ?? null,
            'to_number' => $callData['to_number'] ?? null,
            'branch_id' => $this->resolveBranchFromPhoneSecure($callData['to_number'] ?? null),
            'status' => 'in_progress',
            'started_at' => now()
        ]);
        
        $webhookEvent->update(['processed_at' => now()]);
        
        return $this->successResponse([
            'message' => 'Call started webhook processed',
            'call_id' => $call->id
        ]);
    }
    
    /**
     * Find or create customer with company validation
     */
    protected function findOrCreateCustomerSecure(array $customerData): ?Customer
    {
        if (empty($customerData['phone'])) {
            return null;
        }
        
        // Force company context
        $customerData['company_id'] = $this->company->id;
        
        // Find existing customer
        $customer = Customer::where('phone', $customerData['phone'])
            ->where('company_id', $this->company->id) // CRITICAL: Company scope
            ->first();
            
        if ($customer) {
            return $customer;
        }
        
        // Create new customer
        return Customer::create([
            'company_id' => $this->company->id, // CRITICAL: Force company
            'phone' => $customerData['phone'],
            'first_name' => $customerData['first_name'] ?? 'Unbekannt',
            'last_name' => $customerData['last_name'] ?? '',
            'email' => $customerData['email'] ?? null,
            'source' => 'phone_ai'
        ]);
    }
    
    /**
     * Resolve branch from phone number with company validation
     */
    protected function resolveBranchFromPhoneSecure(?string $phoneNumber): ?int
    {
        if (!$phoneNumber || !$this->company) {
            return null;
        }
        
        $phoneNumberRecord = DB::table('phone_numbers')
            ->where('phone_number', $phoneNumber)
            ->where('company_id', $this->company->id) // CRITICAL: Company scope
            ->where('is_active', true)
            ->first();
            
        return $phoneNumberRecord?->branch_id;
    }
    
    /**
     * Validate call belongs to company by phone number
     */
    protected function validateCallBelongsToCompany(array $callData): bool
    {
        if (empty($callData['to_number'])) {
            return false;
        }
        
        // Check if the called number belongs to the company
        $phoneExists = DB::table('phone_numbers')
            ->where('phone_number', $callData['to_number'])
            ->where('company_id', $this->company->id)
            ->where('is_active', true)
            ->exists();
            
        return $phoneExists;
    }
    
    /**
     * List webhook events for the company
     */
    protected function listWebhookEventsSecure(array $params): array
    {
        $query = WebhookEvent::where('company_id', $this->company->id) // Company scope
            ->orderBy('created_at', 'desc');
            
        if (!empty($params['source'])) {
            $query->where('source', $params['source']);
        }
        
        if (!empty($params['event_type'])) {
            $query->where('event_type', $params['event_type']);
        }
        
        if (!empty($params['processed'])) {
            if ($params['processed'] === true) {
                $query->whereNotNull('processed_at');
            } else {
                $query->whereNull('processed_at');
            }
        }
        
        $events = $query->limit($params['limit'] ?? 50)->get();
        
        return $this->successResponse([
            'events' => $events->map(function ($event) {
                return [
                    'id' => $event->id,
                    'source' => $event->source,
                    'event_type' => $event->event_type,
                    'processed' => !is_null($event->processed_at),
                    'processed_at' => $event->processed_at,
                    'created_at' => $event->created_at,
                    'payload_size' => strlen($event->payload)
                ];
            }),
            'total' => $events->count()
        ]);
    }
    
    /**
     * Replay webhook event with security validation
     */
    protected function replayWebhookEventSecure(array $params): array
    {
        $this->validateParams($params, ['event_id']);
        
        $event = WebhookEvent::where('id', $params['event_id'])
            ->where('company_id', $this->company->id) // CRITICAL: Company scope
            ->first();
            
        if (!$event) {
            throw new SecurityException('Webhook event not found or does not belong to company');
        }
        
        $this->auditAccess('replay_webhook_event', [
            'event_id' => $event->id,
            'source' => $event->source,
            'event_type' => $event->event_type
        ]);
        
        // Replay the event
        $payload = json_decode($event->payload, true);
        
        switch ($event->source) {
            case 'retell':
                return $this->processRetellWebhookSecure($payload);
                
            case 'calcom':
                return $this->processCalcomWebhookSecure($payload);
                
            case 'stripe':
                return $this->processStripeWebhookSecure($payload);
                
            default:
                return $this->errorResponse("Unknown webhook source: {$event->source}");
        }
    }
    
    /**
     * Process Cal.com webhook (placeholder for implementation)
     */
    protected function processCalcomWebhookSecure(array $params): array
    {
        $this->auditAccess('process_calcom_webhook', $params);
        
        // TODO: Implement Cal.com webhook processing
        return $this->successResponse([
            'message' => 'Cal.com webhook processed',
            'event_type' => $params['event'] ?? 'unknown'
        ]);
    }
    
    /**
     * Process Stripe webhook (placeholder for implementation)
     */
    protected function processStripeWebhookSecure(array $params): array
    {
        $this->auditAccess('process_stripe_webhook', $params);
        
        // TODO: Implement Stripe webhook processing
        return $this->successResponse([
            'message' => 'Stripe webhook processed',
            'event_type' => $params['type'] ?? 'unknown'
        ]);
    }
    
    /**
     * Resolve company context from authenticated user
     */
    protected function resolveCompanyContext(): void
    {
        if (Auth::check()) {
            $user = Auth::user();
            
            if ($user->company_id) {
                $this->company = Company::find($user->company_id);
            }
        }
    }
    
    /**
     * Ensure company context is set
     */
    protected function ensureCompanyContext(): void
    {
        if (!$this->company) {
            throw new SecurityException('No valid company context for webhook operations');
        }
    }
    
    /**
     * Audit access to webhook operations
     */
    protected function auditAccess(string $operation, array $context = []): void
    {
        if (!$this->auditEnabled) {
            return;
        }
        
        try {
            if (DB::connection()->getSchemaBuilder()->hasTable('security_audit_logs')) {
                DB::table('security_audit_logs')->insert([
                    'event_type' => 'webhook_mcp_access',
                    'user_id' => Auth::id(),
                    'company_id' => $this->company->id ?? null,
                    'ip_address' => request()->ip() ?? '127.0.0.1',
                    'url' => request()->fullUrl() ?? 'console',
                    'metadata' => json_encode(array_merge($context, [
                        'operation' => $operation,
                        'user_agent' => request()->userAgent()
                    ])),
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
            }
        } catch (\Exception $e) {
            Log::warning('SecureWebhookMCP: Audit logging failed', [
                'error' => $e->getMessage()
            ]);
        }
    }
    
    /**
     * Disable audit logging (for testing)
     */
    public function disableAudit(): self
    {
        $this->auditEnabled = false;
        return $this;
    }
}
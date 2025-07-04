<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Call;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use App\Scopes\TenantScope;

/**
 * Minimaler Controller für Krückeberg Servicegruppe Datensammlung
 * WICHTIG: Ändert NICHTS an der bestehenden Retell-Integration!
 */
class RetellDataCollectionController extends Controller
{
    private NotificationService $notificationService;
    
    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }
    
    /**
     * Sammelt Kundendaten ohne Terminbuchung
     * Wird von Retell Custom Function aufgerufen
     */
    public function collectData(Request $request)
    {
        try {
            // Generate correlation ID for better tracking
            $correlationId = $request->header('X-Correlation-Id') ?? Str::uuid()->toString();
            
            Log::info('RetellDataCollection: Incoming request', [
                'correlation_id' => $correlationId,
                'headers' => $request->headers->all(),
                'data' => $request->all()
            ]);
            
            // Try to find call_id from various sources
            // First try from the call object (Retell sends it here)
            $callId = $request->input('call.call_id') ?? 
                      $request->header('X-Retell-Call-Id') ?? 
                      $request->input('call_id') ?? 
                      null;
                      
            // Get phone number early for better call lookup
            $phoneNumber = $request->input('call.from_number') ?? 
                          $request->input('from_number') ?? 
                          null;
                          
            // Extract from dynamic variables if needed
            if (!$phoneNumber || $phoneNumber === '{{caller_phone_number}}') {
                $dynamicVars = $request->input('call.retell_llm_dynamic_variables', []);
                $phoneNumber = $dynamicVars['caller_phone_number'] ?? $phoneNumber;
            }
            
            // If no call_id found, generate a temporary one
            if (!$callId) {
                Log::info('RetellDataCollection: No call_id provided, generating temporary ID', [
                    'correlation_id' => $correlationId
                ]);
                $callId = 'temp-' . date('Ymd-His') . '-' . strtoupper(\Str::random(6));
            }
            
            // Find the call record
            $call = null;
            
            // Always try to find existing call first
            if ($callId && !str_starts_with($callId, 'temp-')) {
                // First try with the exact call ID using all scopes disabled
                $call = Call::withoutGlobalScopes()
                    ->where('retell_call_id', $callId)
                    ->first();
                    
                if ($call) {
                    Log::info('RetellDataCollection: Found existing call by ID', [
                        'correlation_id' => $correlationId,
                        'call_id' => $callId,
                        'db_id' => $call->id,
                        'created_at' => $call->created_at->toISOString()
                    ]);
                } else {
                    Log::warning('RetellDataCollection: Call not found by ID, checking by phone number', [
                        'correlation_id' => $correlationId,
                        'call_id' => $callId,
                        'phone' => $phoneNumber
                    ]);
                    
                    // Try to find by phone number with a wider time window
                    if ($phoneNumber && $phoneNumber !== '{{caller_phone_number}}') {
                        // Try to find a recent call from this phone number
                        $call = Call::withoutGlobalScopes()
                            ->where('from_number', $phoneNumber)
                            ->where('created_at', '>=', now()->subMinutes(30)) // Wider window
                            ->orderBy('created_at', 'desc')
                            ->first();
                            
                        if ($call) {
                            Log::info('RetellDataCollection: Found call by phone number', [
                                'correlation_id' => $correlationId,
                                'call_id' => $call->retell_call_id,
                                'db_id' => $call->id,
                                'phone' => $phoneNumber
                            ]);
                        }
                    }
                }
                    
                if (!$call) {
                    Log::warning('RetellDataCollection: Call not found, will create standalone record', [
                        'correlation_id' => $correlationId,
                        'call_id' => $callId
                    ]);
                }
            }
            
            // Data from Retell comes nested under 'args'
            $args = $request->input('args', []);
            
            // Map Retell parameter names to our internal structure
            $customerData = [
                'full_name' => trim(($args['vorname'] ?? '') . ' ' . ($args['nachname'] ?? '')),
                'company' => $args['firma'] ?? null,
                'customer_number' => $args['kundennummer'] ?? null,
                'phone_primary' => $args['telefon_primaer'] ?? null,
                'phone_secondary' => $args['telefon_sekundaer'] ?? null,
                'email' => $args['email'] ?? null,
                'request' => $args['anliegen'] ?? null,
                'notes' => $args['weitere_notizen'] ?? null,
                'consent' => $args['einverstaendnis_datenspeicherung'] ?? false,
                'collected_at' => now()->toISOString()
            ];
            
            // Replace placeholders with actual values
            if ($customerData['phone_primary'] === '{{caller_phone_number}}' || empty($customerData['phone_primary'])) {
                // Use the phone number we extracted earlier
                if ($phoneNumber && $phoneNumber !== '{{caller_phone_number}}' && $phoneNumber !== 'unknown') {
                    $customerData['phone_primary'] = $phoneNumber;
                    Log::info('RetellDataCollection: Auto-populated phone number', [
                        'correlation_id' => $correlationId,
                        'phone' => $phoneNumber,
                        'source' => 'request_data'
                    ]);
                } else if ($call && $call->from_number) {
                    // Fallback to call record
                    $customerData['phone_primary'] = $call->from_number;
                    Log::info('RetellDataCollection: Auto-populated phone number from call record', [
                        'correlation_id' => $correlationId,
                        'phone' => $call->from_number,
                        'source' => 'call_record'
                    ]);
                }
            }
            
            // Handle data storage based on whether we have a call record
            if ($call) {
                // Update existing call record with metadata
                $metadata = $call->metadata ?? [];
                $metadata['customer_data_collected'] = true;
                $metadata['customer_data'] = $customerData;
                $metadata['data_collection_type'] = 'call_center';
                $metadata['collection_timestamp'] = now()->toISOString();
                
                // BACKUP: Also store in custom_analysis_data as backup
                $customAnalysisData = $call->custom_analysis_data ?? [];
                $customAnalysisData['customer_data_backup'] = $customerData;
                $customAnalysisData['backup_timestamp'] = now()->toISOString();
                
                $call->update([
                    'metadata' => $metadata,
                    'custom_analysis_data' => $customAnalysisData,
                    'customer_data_backup' => $customerData,  // New dedicated backup field
                    'customer_data_collected_at' => now(),
                    'summary' => $this->generateSummary($customerData),
                    'extracted_name' => $customerData['full_name'] ?? null,
                    'extracted_email' => $customerData['email'] ?? null,
                    'notes' => json_encode($customerData) // Additional backup in notes field
                ]);
                
                Log::info('RetellDataCollection: Updated existing call record with multiple backups', [
                    'call_id' => $callId,
                    'customer_name' => $customerData['full_name'],
                    'backups' => ['metadata', 'custom_analysis_data', 'notes', 'extracted_fields']
                ]);
            } else {
                // Create a new call record for standalone data collection
                // But first make sure we have a valid phone number
                $phoneForRecord = $customerData['phone_primary'];
                if ($phoneForRecord === '{{caller_phone_number}}' || empty($phoneForRecord)) {
                    // Try to get from request
                    $phoneForRecord = $request->input('call.from_number') ?? 
                                      $request->input('from_number') ?? 
                                      'unknown';
                }
                
                try {
                    $call = Call::withoutGlobalScopes()->create([
                        'retell_call_id' => $callId,
                        'from_number' => $phoneForRecord,
                        'to_number' => $request->input('call.to_number') ?? env('DEFAULT_RETELL_PHONE_NUMBER', 'unknown'),
                        'status' => 'completed',
                        'start_timestamp' => now(),
                        'end_timestamp' => now(),
                        'duration_sec' => 0,
                        'summary' => $this->generateSummary($customerData),
                        'metadata' => [
                            'customer_data_collected' => true,
                            'customer_data' => $customerData,
                            'data_collection_type' => 'call_center',
                            'standalone_collection' => true
                        ],
                        // Try to resolve company/branch from phone number or use defaults
                        'company_id' => $this->resolveCompanyId($phoneForRecord),
                        'branch_id' => $this->resolveBranchId($phoneForRecord)
                    ]);
                    
                    Log::info('RetellDataCollection: Created new call record', [
                        'correlation_id' => $correlationId,
                        'call_id' => $callId,
                        'db_id' => $call->id
                    ]);
                } catch (\Exception $e) {
                    // If creation fails (e.g., duplicate entry), try to find the existing call
                    if (str_contains($e->getMessage(), 'Duplicate entry')) {
                        Log::info('RetellDataCollection: Got duplicate entry error, searching for existing call', [
                            'correlation_id' => $correlationId,
                            'call_id' => $callId
                        ]);
                        
                        // Try multiple strategies to find the call
                        $call = Call::withoutGlobalScopes()
                            ->where('retell_call_id', $callId)
                            ->first();
                            
                        // If still not found, try by phone number
                        if (!$call && $phoneForRecord && $phoneForRecord !== 'unknown') {
                            $call = Call::withoutGlobalScopes()
                                ->where('from_number', $phoneForRecord)
                                ->where('created_at', '>=', now()->subHour())
                                ->orderBy('created_at', 'desc')
                                ->first();
                        }
                            
                        if ($call) {
                            Log::info('RetellDataCollection: Found existing call after duplicate error, updating it', [
                                'correlation_id' => $correlationId,
                                'call_id' => $call->retell_call_id,
                                'db_id' => $call->id
                            ]);
                            
                            // Update the existing call with the customer data
                            $metadata = $call->metadata ?? [];
                            $metadata['customer_data_collected'] = true;
                            $metadata['customer_data'] = $customerData;
                            $metadata['data_collection_type'] = 'call_center';
                            $metadata['collection_timestamp'] = now()->toISOString();
                            
                            // BACKUP: Also store in custom_analysis_data as backup
                            $customAnalysisData = $call->custom_analysis_data ?? [];
                            $customAnalysisData['customer_data_backup'] = $customerData;
                            $customAnalysisData['backup_timestamp'] = now()->toISOString();
                            
                            $call->update([
                                'metadata' => $metadata,
                                'custom_analysis_data' => $customAnalysisData,
                                'customer_data_backup' => $customerData,  // New dedicated backup field
                                'customer_data_collected_at' => now(),
                                'summary' => $this->generateSummary($customerData),
                                'extracted_name' => $customerData['full_name'] ?? null,
                                'extracted_email' => $customerData['email'] ?? null,
                                'notes' => json_encode($customerData) // Additional backup in notes field
                            ]);
                        } else {
                            // Log the error but don't throw - we'll handle it gracefully
                            Log::error('RetellDataCollection: Could not find or create call record', [
                                'correlation_id' => $correlationId,
                                'call_id' => $callId,
                                'error' => $e->getMessage()
                            ]);
                            
                            // Return a success response anyway since we collected the data
                            return response()->json([
                                'success' => true,
                                'message' => 'Daten erfolgreich erfasst',
                                'reference_id' => 'pending-' . $callId,
                                'next_steps' => 'Ihre Daten wurden erfasst und werden verarbeitet.'
                            ]);
                        }
                    } else {
                        throw $e; // Re-throw other errors
                    }
                }
                
                if (!isset($call) || !$call) {
                    throw new \Exception('Failed to create or find call record');
                }
                
                Log::info('RetellDataCollection: Created new call record for data collection', [
                    'call_id' => $callId,
                    'customer_name' => $customerData['full_name']
                ]);
            }
            
            // Sende E-Mail-Benachrichtigung
            $this->sendNotification($call, $customerData);
            
            Log::info('RetellDataCollection: Data collected successfully', [
                'correlation_id' => $correlationId,
                'call_id' => $callId,
                'db_id' => $call ? $call->id : null,
                'customer_name' => $customerData['full_name']
            ]);
            
            return response()->json([
                'success' => true,
                'message' => 'Daten erfolgreich erfasst',
                'reference_id' => $call ? $call->id : 'pending-' . $callId,
                'next_steps' => 'Ihre Daten wurden erfasst und an unser Team weitergeleitet.'
            ]);
            
        } catch (\Exception $e) {
            Log::error('RetellDataCollection: Error', [
                'correlation_id' => $correlationId ?? 'unknown',
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Es ist ein Fehler aufgetreten. Bitte versuchen Sie es später erneut.'
            ], 500);
        }
    }
    
    /**
     * Generiere eine Zusammenfassung der gesammelten Daten
     */
    private function generateSummary(array $data): string
    {
        $parts = [];
        
        if (!empty($data['full_name'])) {
            $parts[] = "Anrufer: {$data['full_name']}";
        }
        
        if (!empty($data['company'])) {
            $parts[] = "Firma: {$data['company']}";
        }
        
        if (!empty($data['request'])) {
            $parts[] = "Anliegen: {$data['request']}";
        }
        
        return implode(' | ', $parts) ?: 'Kundendaten erfasst';
    }
    
    /**
     * Sende E-Mail-Benachrichtigung
     */
    private function sendNotification(Call $call, array $customerData): void
    {
        try {
            // Nutze den bestehenden NotificationService
            $branch = $call->branch;
            if (!$branch) {
                Log::warning('RetellDataCollection: No branch found for notification');
                return;
            }
            
            $emailData = [
                'subject' => 'Neuer Anruf - ' . ($customerData['full_name'] ?? 'Unbekannt'),
                'customer_name' => $customerData['full_name'] ?? 'Nicht angegeben',
                'company' => $customerData['company'] ?? 'Nicht angegeben',
                'phone' => $customerData['phone_primary'] ?? $call->from_number,
                'email' => $customerData['email'] ?? 'Nicht angegeben',
                'request' => $customerData['request'] ?? 'Nicht angegeben',
                'notes' => $customerData['notes'] ?? 'Keine',
                'call_time' => $call->started_at?->format('d.m.Y H:i') ?? 'Unbekannt',
                'call_duration' => $call->duration ? gmdate('i:s', $call->duration) : 'Unbekannt'
            ];
            
            // Sende an die konfigurierten E-Mail-Adressen
            $recipients = ['fabian@askproai.de']; // Temporär hardcoded für Tests
            
            foreach ($recipients as $email) {
                $this->notificationService->sendEmail(
                    $email,
                    $emailData['subject'],
                    'emails.customer_data_collected',
                    $emailData
                );
            }
            
            Log::info('RetellDataCollection: Notification sent', [
                'recipients' => $recipients
            ]);
            
        } catch (\Exception $e) {
            // Log error but don't fail the request
            Log::error('RetellDataCollection: Failed to send notification', [
                'error' => $e->getMessage()
            ]);
        }
    }
    
    /**
     * Try to resolve company ID from phone number or use default
     */
    private function resolveCompanyId(?string $phoneNumber): int
    {
        // For Krückeberg, we use a specific company ID
        // This could be made configurable via env variable
        $defaultCompanyId = config('services.retell.default_company_id', 1);
        
        if (!$phoneNumber) {
            return $defaultCompanyId;
        }
        
        // Try to find a phone number record that matches
        $phoneRecord = \App\Models\PhoneNumber::where('number', 'LIKE', '%' . substr($phoneNumber, -8) . '%')
            ->first();
            
        if ($phoneRecord && $phoneRecord->company_id) {
            return $phoneRecord->company_id;
        }
        
        return $defaultCompanyId;
    }
    
    /**
     * Try to resolve branch ID from phone number or use default
     */
    private function resolveBranchId(?string $phoneNumber): int
    {
        // For Krückeberg, we use a specific branch ID
        // This could be made configurable via env variable
        $defaultBranchId = config('services.retell.default_branch_id', 1);
        
        if (!$phoneNumber) {
            return $defaultBranchId;
        }
        
        // Try to find a phone number record that matches
        $phoneRecord = \App\Models\PhoneNumber::where('number', 'LIKE', '%' . substr($phoneNumber, -8) . '%')
            ->first();
            
        if ($phoneRecord && $phoneRecord->branch_id) {
            return $phoneRecord->branch_id;
        }
        
        return $defaultBranchId;
    }
}
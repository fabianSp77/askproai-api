<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessRetellCallEndedJob;
use App\Models\Company;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class RetellWebhookBypassController extends Controller
{
    /**
     * TEMPORÄRER Webhook Bypass - NUR FÜR DEBUGGING!
     * Dieser Endpoint hat KEINE Signatur-Verifikation
     */
    public function handle(Request $request)
    {
        // Log alle Header für Debugging
        Log::warning('🚨 WEBHOOK BYPASS AKTIV - NUR FÜR DEBUGGING!', [
            'headers' => $request->headers->all(),
            'ip' => $request->ip(),
            'body_sample' => substr($request->getContent(), 0, 200)
        ]);
        
        // Prüfe ob von bekannter Retell IP
        $knownRetellIps = [
            '100.20.5.228',
            '34.226.180.161',
            '34.198.47.77',
            '52.203.159.213',
            '52.53.229.199',
            '54.241.134.41',
            '54.183.150.123',
            '152.53.228.178'
        ];
        
        if (!in_array($request->ip(), $knownRetellIps)) {
            Log::warning('Webhook von unbekannter IP', ['ip' => $request->ip()]);
            // Trotzdem fortfahren für Debugging
        }
        
        $data = $request->all();
        
        // Log VOLLSTÄNDIGE Webhook-Daten zum Debuggen
        Log::warning('📋 VOLLSTÄNDIGE WEBHOOK DATEN:', [
            'raw_content' => $request->getContent(),
            'parsed_data' => $data,
            'headers' => $request->headers->all()
        ]);
        
        // Retell v2 sendet möglicherweise das Event anders
        $event = $data['event'] ?? $data['event_type'] ?? null;
        
        // Wenn kein Event, versuche aus der Struktur zu erkennen
        if (!$event && isset($data['call_id']) && isset($data['call_status'])) {
            $event = 'call_' . ($data['call_status'] ?? 'unknown');
        }
        
        Log::info('📞 Retell Webhook empfangen (Bypass)', [
            'event' => $event,
            'call_id' => $data['call']['call_id'] ?? $data['call_id'] ?? 'unknown',
            'headers' => [
                'x-retell-signature' => $request->header('x-retell-signature'),
                'x-retell-timestamp' => $request->header('x-retell-timestamp'),
                'retell-signature' => $request->header('retell-signature'),
                'signature' => $request->header('signature')
            ]
        ]);
        
        // Verarbeite nur call_ended und call_analyzed Events
        if (in_array($event, ['call_ended', 'call_analyzed', 'call_started'])) {
            try {
                // Versuche Company aus Telefonnummer zu ermitteln
                $phoneNumber = $data['to_number'] ?? $data['to'] ?? null;
                $company = null;
                
                if ($phoneNumber) {
                    // Normalisiere die Nummer
                    $phoneNumber = preg_replace('/[^0-9+]/', '', $phoneNumber);
                    
                    // Suche nach der Branch mit dieser Telefonnummer (ohne Tenant Scope)
                    $branch = \App\Models\Branch::withoutGlobalScope(\App\Scopes\TenantScope::class)
                        ->where('phone_number', $phoneNumber)
                        ->orWhere('phone_number', 'LIKE', '%' . substr($phoneNumber, -10) . '%')
                        ->first();
                    
                    if ($branch) {
                        $company = $branch->company;
                        Log::info('✅ Company gefunden über Telefonnummer', [
                            'phone' => $phoneNumber,
                            'branch' => $branch->name,
                            'company' => $company->name
                        ]);
                    }
                }
                
                // Fallback auf erste Company wenn keine gefunden
                if (!$company) {
                    $company = Company::first();
                    Log::warning('⚠️ Keine Company über Telefonnummer gefunden, nutze Fallback', [
                        'phone' => $phoneNumber
                    ]);
                }
                
                // Erstelle Job
                $job = new ProcessRetellCallEndedJob($data);
                if ($company) {
                    $job->setCompanyId($company->id);
                }
                
                // Sofort ausführen für Debugging
                $job->handle();
                
                Log::info('✅ Webhook erfolgreich verarbeitet (Bypass)', [
                    'event' => $event,
                    'call_id' => $data['call']['call_id'] ?? 'unknown'
                ]);
                
                return response()->json([
                    'success' => true,
                    'message' => 'Webhook processed (bypass mode)'
                ]);
                
            } catch (\Exception $e) {
                Log::error('❌ Fehler bei Webhook-Verarbeitung (Bypass)', [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                    'data' => $data
                ]);
                
                return response()->json([
                    'success' => false,
                    'error' => $e->getMessage()
                ], 500);
            }
        }
        
        return response()->json([
            'success' => true,
            'message' => 'Event type not processed: ' . $event
        ]);
    }
}
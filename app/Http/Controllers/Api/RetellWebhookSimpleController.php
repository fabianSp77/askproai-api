<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Call;
use App\Models\Branch;
use App\Models\RetellWebhook;
use App\Scopes\TenantScope;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class RetellWebhookSimpleController extends Controller
{
    /**
     * Vereinfachter Webhook Handler - OHNE Job Queue
     */
    public function handle(Request $request)
    {
        $data = $request->all();
        
        // Log eingehenden Webhook
        Log::info('📞 Retell Webhook Simple empfangen', [
            'event' => $data['event'] ?? $data['event_type'] ?? 'unknown',
            'call_id' => $data['call_id'] ?? $data['call']['call_id'] ?? 'unknown',
            'from' => $data['from_number'] ?? 'unknown',
            'to' => $data['to_number'] ?? 'unknown'
        ]);
        
        // Event-Typ ermitteln
        $event = $data['event'] ?? $data['event_type'] ?? null;
        if (!$event && isset($data['call_status'])) {
            $event = 'call_' . $data['call_status'];
        }
        
        // Nur relevante Events verarbeiten
        if (!in_array($event, ['call_started', 'call_ended', 'call_analyzed'])) {
            return response()->json(['success' => true, 'message' => 'Event ignored']);
        }
        
        try {
            // Call-ID extrahieren
            $callId = $data['call_id'] ?? $data['call']['call_id'] ?? 'retell_' . uniqid();
            
            // Prüfe ob Call bereits existiert
            $existingCall = Call::withoutGlobalScope(TenantScope::class)
                ->where('call_id', $callId)
                ->first();
                
            if ($existingCall) {
                Log::info('Call bereits vorhanden, aktualisiere', ['call_id' => $callId]);
                
                // Bei call_ended: Update des bestehenden Calls
                if ($event === 'call_ended' || $event === 'call_analyzed') {
                    $existingCall->call_status = $data['call_status'] ?? 'ended';
                    $existingCall->end_timestamp = isset($data['end_timestamp']) 
                        ? date('Y-m-d H:i:s', $data['end_timestamp'] / 1000) 
                        : now();
                    $existingCall->duration_sec = $data['duration'] ?? 0;
                    $existingCall->duration_minutes = round(($data['duration'] ?? 0) / 60, 2);
                    $existingCall->transcript = $data['transcript'] ?? $existingCall->transcript;
                    $existingCall->disconnection_reason = $data['disconnection_reason'] ?? null;
                    $existingCall->save();
                    
                    Log::info('✅ Call aktualisiert', ['call_id' => $callId, 'event' => $event]);
                }
                
                return response()->json(['success' => true, 'message' => 'Call updated']);
            }
            
            // Branch über Telefonnummer finden
            $toNumber = $data['to_number'] ?? $data['to'] ?? null;
            if (!$toNumber) {
                throw new \Exception('Keine Zielnummer im Webhook');
            }
            
            $phoneNumber = preg_replace('/[^0-9+]/', '', $toNumber);
            
            // Debug-Log
            Log::info('Suche Branch für Nummer', ['phone' => $phoneNumber]);
            
            // Direkte SQL-Abfrage für bessere Kontrolle
            $branch = \DB::table('branches')
                ->where('phone_number', $phoneNumber)
                ->first();
                
            if (!$branch) {
                // Versuche mit LIKE
                $branch = \DB::table('branches')
                    ->where('phone_number', 'LIKE', '%' . substr($phoneNumber, -10) . '%')
                    ->first();
            }
            
            if ($branch) {
                // Bei mehreren Branches für dieselbe Nummer: erste verwenden
                $allBranches = \DB::table('branches')
                    ->where('phone_number', 'LIKE', '%' . substr($phoneNumber, -10) . '%')
                    ->get();
                    
                if ($allBranches->count() > 1) {
                    Log::warning('Mehrere Branches für Nummer gefunden', [
                        'phone' => $phoneNumber,
                        'count' => $allBranches->count(),
                        'using_id' => $branch->id
                    ]);
                }
                
                // Konvertiere zu Model
                $branch = Branch::find($branch->id);
            }
                
            if (!$branch) {
                // Letzte Chance: Verwende erste Branch mit Company
                Log::warning('Keine Branch für Nummer gefunden, verwende Default', ['phone' => $phoneNumber]);
                
                $branch = \DB::table('branches')
                    ->whereNotNull('company_id')
                    ->orderBy('created_at', 'asc')
                    ->first();
                    
                if ($branch) {
                    $branch = Branch::find($branch->id);
                } else {
                    throw new \Exception("Keine Branch für Nummer gefunden: $phoneNumber");
                }
            }
            
            // Webhook-Record speichern (nur wenn noch nicht vorhanden)
            $existingWebhook = \DB::table('webhook_events')
                ->where('event_id', $callId)
                ->where('event_type', $event)
                ->first();
                
            if (!$existingWebhook) {
                \DB::table('webhook_events')->insert([
                    'event_type' => $event,
                    'event_id' => $callId,
                    'idempotency_key' => $callId . '_' . $event,
                    'payload' => json_encode($data),
                    'provider' => 'retell',
                    'status' => 'processed',
                    'processed_at' => now(),
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
            }
            
            // Sicherstellen dass Branch existiert
            if (!$branch || !$branch->id) {
                Log::error('Branch ist null oder hat keine ID', [
                    'branch' => $branch,
                    'phone' => $phoneNumber
                ]);
                throw new \Exception("Branch-Objekt ist ungültig");
            }
            
            // Call erstellen
            $call = Call::withoutGlobalScope(TenantScope::class)->create([
                'call_id' => $callId,
                'retell_call_id' => $callId,
                'company_id' => $branch->company_id ?? 1,
                'branch_id' => $branch->id,
                'agent_id' => $data['agent_id'] ?? null,
                'from_number' => $data['from_number'] ?? $data['from'] ?? null,
                'to_number' => $toNumber,
                'call_status' => $data['call_status'] ?? 'ended',
                'direction' => $data['direction'] ?? 'inbound',
                'call_type' => $data['call_type'] ?? 'phone_call',
                'start_timestamp' => isset($data['start_timestamp']) 
                    ? date('Y-m-d H:i:s', $data['start_timestamp'] / 1000) 
                    : now()->subSeconds($data['duration'] ?? 0),
                'end_timestamp' => isset($data['end_timestamp']) 
                    ? date('Y-m-d H:i:s', $data['end_timestamp'] / 1000) 
                    : now(),
                'duration_sec' => $data['duration'] ?? 0,
                'duration_minutes' => round(($data['duration'] ?? 0) / 60, 2),
                'transcript' => $data['transcript'] ?? null,
                'raw_data' => json_encode($data),
                'disconnection_reason' => $data['disconnection_reason'] ?? null
            ]);
            
            Log::info('✅ Call erfolgreich erstellt', [
                'call_id' => $call->call_id,
                'company' => $branch->company->name,
                'branch' => $branch->name,
                'duration' => $call->duration_sec
            ]);
            
            return response()->json([
                'success' => true,
                'message' => 'Call processed successfully',
                'call_id' => $call->call_id
            ]);
            
        } catch (\Exception $e) {
            Log::error('❌ Fehler bei Webhook-Verarbeitung', [
                'error' => $e->getMessage(),
                'data' => $data
            ]);
            
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
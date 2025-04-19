<?php // app/Http/Controllers/RetellWebhookController.php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use App\Models\Call;
use App\Services\CalcomService;
use Illuminate\Support\Facades\Validator;
use Throwable;
use Illuminate\Support\Facades\Config;
use App\Mail\ErrorNotificationMail;
use App\Models\Tenant; // Import Tenant
use Illuminate\Support\Facades\App; // Für app() Helper

class RetellWebhookController extends Controller
{
    protected $calcomService;

    public function __construct(CalcomService $calcomService) { $this->calcomService = $calcomService; }

    public function processWebhook(Request $request)
    {
        // Hole aktuellen Tenant aus dem Container (wurde durch Middleware gesetzt)
        // Wir können hier sicher sein, dass er existiert.
        $currentTenant = App::make(Tenant::class);

        $startTime = microtime(true); $data = $request->all(); Log::debug('Webhook Payload received', ['payload_keys' => array_keys($data), 'tenant_id' => $currentTenant->id]); // Logge nur Keys
        $validator = Validator::make($data, [
            'call_id' => 'required|string|max:255', 'status' => 'sometimes|string|max:255', 'phone_number' => 'sometimes|string|nullable|max:255', 'duration' => 'sometimes|integer|nullable', 'call_successful' => 'sometimes|boolean', '_datum__termin' => 'sometimes|required_with:_uhrzeit__termin|date_format:Y-m-d', '_uhrzeit__termin' => 'sometimes|required_with:_datum__termin|date_format:H:i', '_name' => 'sometimes|string|nullable|max:255', '_email' => 'sometimes|email|nullable|max:255',
            // Füge hier ggf. die Validierung für 'agent_id' hinzu, falls diese zur Tenant-ID gehört oder anderweitig wichtig ist
            // 'agent_id' => 'sometimes|string|max:255',
        ]);
        if ($validator->fails()) { Log::error('Webhook Validation Failed', ['errors' => $validator->errors()->toArray(), 'tenant_id' => $currentTenant->id]); return response()->json(['error' => 'Ungültige Eingabedaten', 'details' => $validator->errors()], 422); }

        $validatedData = $validator->validated();
        // Logge nur die call_id, nicht alle validierten Daten
        Log::info('Webhook Validation Passed', ['call_id' => $validatedData['call_id'], 'tenant_id' => $currentTenant->id]);

        try {
            // --- updateOrCreate mit tenant_id als Teil des Suchschlüssels ---
            $call = Call::updateOrCreate(
                [
                    'tenant_id' => $currentTenant->id, // Suche nach Calls dieses Tenants...
                    'call_id' => $validatedData['call_id'] // ...mit dieser externen Call-ID
                ],
                [ // Daten zum Aktualisieren oder Erstellen
                    'call_status' => $validatedData['status'] ?? $data['status'] ?? 'unknown',
                    'phone_number' => $validatedData['phone_number'] ?? $data['phone_number'] ?? null,
                    'call_duration' => $validatedData['duration'] ?? $data['duration'] ?? null,
                    'transcript' => $data['transcript'] ?? null, // Vollständiges Transkript speichern? Überlegen! Ggf. kürzen oder nur Summary.
                    'summary' => $data['summary'] ?? null,
                    'user_sentiment' => $data['user_sentiment'] ?? null,
                    'successful' => $validatedData['call_successful'] ?? $data['call_successful'] ?? false,
                    'disconnect_reason' => $data['disconnect_reason'] ?? null,
                    'raw_data' => json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE), // Speichere den gesamten Payload
                    'name' => $validatedData['_name'] ?? null,
                    'email' => $validatedData['_email'] ?? null,
                    // kunde_id wird hier nicht mehr gesetzt, muss ggf. separat verknüpft werden
                ]
            );
            // --- Ende updateOrCreate ---

            if (isset($validatedData['_datum__termin'], $validatedData['_uhrzeit__termin'])) {
                Log::info('Appointment data found.', ['call_db_id' => $call->id, 'tenant_id' => $currentTenant->id]);
                $this->handleAppointment($call, $validatedData);
            } else {
                Log::info('No appointment data found.', ['call_db_id' => $call->id ?? null, 'retell_call_id' => $validatedData['call_id'], 'tenant_id' => $currentTenant->id]);
            }
            $processingTime = microtime(true) - $startTime;
            Log::info('Webhook processed successfully.', ['call_db_id' => $call->id, 'processing_time_sec' => round($processingTime, 3), 'tenant_id' => $currentTenant->id]);
            return response()->json(['success' => true, 'call_db_id' => $call->id, 'processing_time_sec' => round($processingTime, 3)]);

        } catch (Throwable $e) {
            $adminEmail = Config::get('services.admin.email');
            // Logge den Fehler mit Tenant-Kontext
            Log::error('Webhook Processing Error', [ 'exception' => get_class($e), 'message' => $e->getMessage(), 'file' => $e->getFile(), 'line' => $e->getLine(), 'retell_call_id' => $data['call_id'] ?? 'N/A', 'tenant_id' => $currentTenant->id ?? 'Unknown' ]);
            if ($adminEmail) {
                try {
                    Log::debug('Attempting to send error email via Mailable.', ['adminEmail' => $adminEmail, 'tenant_id' => $currentTenant->id ?? 'Unknown']);
                    $retellCallId = $data['call_id'] ?? null;
                    Mail::to($adminEmail)->send(new ErrorNotificationMail($e, $retellCallId, $data));
                    Log::info('Error notification email initiated for ' . $adminEmail, ['tenant_id' => $currentTenant->id ?? 'Unknown']);
                } catch (Throwable $mailError) { Log::error('Failed to send error email.', [ 'mail_error' => $mailError->getMessage(), 'original_error' => $e->getMessage(), 'tenant_id' => $currentTenant->id ?? 'Unknown' ]); }
            } else { Log::warning('Mail sending skipped (ADMIN_EMAIL not configured).', ['tenant_id' => $currentTenant->id ?? 'Unknown']); }
            return response()->json(['error' => 'Interner Serverfehler bei der Webhook-Verarbeitung.'], 500);
        }
    }

    private function handleAppointment(Call $call, array $validatedData)
    {
         $currentTenant = App::make(Tenant::class); // Hole Tenant für Logging etc.

         // Passe diesen Teil an, falls Cal.com tenant-spezifisch ist!
         $bookingData = [ 'date' => $validatedData['_datum__termin'], 'time' => $validatedData['_uhrzeit__termin'], 'customerName' => $validatedData['_name'] ?? $call->name ?? 'Unbekannt', 'customerEmail' => filter_var($validatedData['_email'] ?? $call->email ?? '', FILTER_VALIDATE_EMAIL) ? ($validatedData['_email'] ?? $call->email) : 'termin+'.$call->id.'@askproai.de', 'phone' => $validatedData['phone_number'] ?? $call->phone_number, 'call_id' => $call->id, 'tenant_id' => $currentTenant->id ];
         try {
             Log::info('Attempting Cal.com booking...', ['call_db_id' => $call->id, 'tenant_id' => $currentTenant->id]);
             $appointmentResponse = $this->calcomService->bookAppointment($bookingData); // Passe ggf. Parameter an
             if ($appointmentResponse['success']) { Log::info('Cal.com booking successful.', [ 'call_db_id' => $call->id, 'cal_id' => $appointmentResponse['appointment_id'], 'tenant_id' => $currentTenant->id ]); }
             else { Log::warning('Cal.com booking failed (Service response).', [ 'call_db_id' => $call->id, 'message' => $appointmentResponse['message'] ?? 'N/A', 'tenant_id' => $currentTenant->id ]); }
         } catch (Throwable $e) { Log::error('Exception during CalcomService->bookAppointment', [ 'error' => $e->getMessage(), 'call_db_id' => $call->id, 'tenant_id' => $currentTenant->id ]); throw $e; }
    }
}

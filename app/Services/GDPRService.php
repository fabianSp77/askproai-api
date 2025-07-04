<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Company;
use App\Models\Appointment;
use App\Models\Call;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use App\Mail\GDPRDataExportMail;
use App\Mail\GDPRDataDeletionMail;
use Carbon\Carbon;
use ZipArchive;

class GDPRService
{
    /**
     * Process data export request
     */
    public function processDataExportRequest(Customer $customer): array
    {
        try {
            // Collect all customer data
            $data = $this->collectCustomerData($customer);
            
            // Generate export file
            $filename = $this->generateExportFile($customer, $data);
            
            // Create secure download link
            $downloadToken = Str::random(64);
            $expiresAt = Carbon::now()->addDays(7);
            
            // Store download info
            DB::table('gdpr_requests')->insert([
                'customer_id' => $customer->id,
                'company_id' => $customer->company_id,
                'type' => 'export',
                'status' => 'completed',
                'token' => $downloadToken,
                'file_path' => $filename,
                'expires_at' => $expiresAt,
                'created_at' => now(),
                'updated_at' => now()
            ]);
            
            // Send email with download link
            $downloadLink = url("/gdpr/download/{$downloadToken}");
            Mail::to($customer->email)->send(new GDPRDataExportMail(
                $customer,
                $customer->company,
                $downloadLink,
                $expiresAt->format('d.m.Y H:i'),
                $customer->preferred_language ?? 'de'
            ));
            
            Log::info('GDPR data export completed', [
                'customer_id' => $customer->id,
                'file' => $filename
            ]);
            
            return [
                'success' => true,
                'download_link' => $downloadLink,
                'expires_at' => $expiresAt
            ];
            
        } catch (\Exception $e) {
            Log::error('GDPR data export failed', [
                'customer_id' => $customer->id,
                'error' => $e->getMessage()
            ]);
            
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Process data deletion request
     */
    public function processDataDeletionRequest(Customer $customer): array
    {
        try {
            // Create deletion request
            $confirmationToken = Str::random(64);
            $expiresAt = Carbon::now()->addDays(3);
            
            DB::table('gdpr_requests')->insert([
                'customer_id' => $customer->id,
                'company_id' => $customer->company_id,
                'type' => 'deletion',
                'status' => 'pending_confirmation',
                'token' => $confirmationToken,
                'expires_at' => $expiresAt,
                'created_at' => now(),
                'updated_at' => now()
            ]);
            
            // Send confirmation email
            $confirmationLink = url("/gdpr/confirm-deletion/{$confirmationToken}");
            Mail::to($customer->email)->send(new GDPRDataDeletionMail(
                $customer,
                $customer->company,
                $confirmationLink,
                $expiresAt->format('d.m.Y H:i'),
                $customer->preferred_language ?? 'de'
            ));
            
            Log::info('GDPR deletion request created', [
                'customer_id' => $customer->id,
                'expires_at' => $expiresAt
            ]);
            
            return [
                'success' => true,
                'confirmation_required' => true,
                'expires_at' => $expiresAt
            ];
            
        } catch (\Exception $e) {
            Log::error('GDPR deletion request failed', [
                'customer_id' => $customer->id,
                'error' => $e->getMessage()
            ]);
            
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Confirm and execute data deletion
     */
    public function confirmDataDeletion(string $token): array
    {
        $request = DB::table('gdpr_requests')
            ->where('token', $token)
            ->where('type', 'deletion')
            ->where('status', 'pending_confirmation')
            ->where('expires_at', '>', now())
            ->first();
            
        if (!$request) {
            return [
                'success' => false,
                'error' => 'Invalid or expired token'
            ];
        }
        
        $customer = Customer::find($request->customer_id);
        if (!$customer) {
            return [
                'success' => false,
                'error' => 'Customer not found'
            ];
        }
        
        DB::beginTransaction();
        try {
            // Anonymize appointments instead of deleting
            Appointment::where('customer_id', $customer->id)
                ->update([
                    'customer_id' => null,
                    'notes' => DB::raw("CONCAT('GDPR-DELETED-', id)"),
                    'updated_at' => now()
                ]);
            
            // Anonymize calls
            Call::where('customer_id', $customer->id)
                ->update([
                    'customer_id' => null,
                    'customer_name' => 'GDPR-DELETED',
                    'transcript' => null,
                    'recording_url' => null,
                    'updated_at' => now()
                ]);
            
            // Delete or anonymize other related data
            DB::table('sms_message_logs')
                ->where('customer_id', $customer->id)
                ->update([
                    'customer_id' => null,
                    'message' => 'GDPR-DELETED',
                    'updated_at' => now()
                ]);
            
            // Finally, anonymize customer record
            $customer->update([
                'name' => 'GDPR-DELETED-' . $customer->id,
                'email' => 'deleted-' . $customer->id . '@gdpr.local',
                'phone' => null,
                'address' => null,
                'birthdate' => null,
                'notes' => 'Account deleted per GDPR request on ' . now()->format('Y-m-d'),
                'sms_opt_in' => false,
                'whatsapp_opt_in' => false,
                'email_opt_in' => false,
                'marketing_consent' => false,
                'push_token' => null,
                'deleted_at' => now()
            ]);
            
            // Update request status
            DB::table('gdpr_requests')
                ->where('id', $request->id)
                ->update([
                    'status' => 'completed',
                    'processed_at' => now(),
                    'updated_at' => now()
                ]);
            
            DB::commit();
            
            Log::info('GDPR data deletion completed', [
                'customer_id' => $customer->id,
                'request_id' => $request->id
            ]);
            
            return [
                'success' => true,
                'message' => 'Data deletion completed'
            ];
            
        } catch (\Exception $e) {
            DB::rollback();
            
            Log::error('GDPR data deletion failed', [
                'customer_id' => $customer->id,
                'error' => $e->getMessage()
            ]);
            
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Collect all customer data
     */
    protected function collectCustomerData(Customer $customer): array
    {
        $data = [
            'personal_information' => [
                'id' => $customer->id,
                'name' => $customer->name,
                'email' => $customer->email,
                'phone' => $customer->phone,
                'address' => $customer->address,
                'birthdate' => $customer->birthdate,
                'preferred_language' => $customer->preferred_language,
                'created_at' => $customer->created_at,
                'updated_at' => $customer->updated_at
            ],
            'consents' => [
                'sms_opt_in' => $customer->sms_opt_in,
                'whatsapp_opt_in' => $customer->whatsapp_opt_in,
                'email_opt_in' => $customer->email_opt_in,
                'marketing_consent' => $customer->marketing_consent,
                'gdpr_consent_date' => $customer->gdpr_consent_date
            ],
            'appointments' => [],
            'calls' => [],
            'messages' => []
        ];
        
        // Collect appointments
        $appointments = Appointment::where('customer_id', $customer->id)
            ->with(['service', 'staff', 'branch'])
            ->get();
            
        foreach ($appointments as $appointment) {
            $data['appointments'][] = [
                'id' => $appointment->id,
                'date' => $appointment->starts_at->format('Y-m-d H:i'),
                'service' => $appointment->service->name ?? 'Unknown',
                'staff' => $appointment->staff ? $appointment->staff->first_name . ' ' . $appointment->staff->last_name : 'Unknown',
                'branch' => $appointment->branch->name ?? 'Unknown',
                'status' => $appointment->status,
                'notes' => $appointment->notes
            ];
        }
        
        // Collect calls
        $calls = Call::where('customer_id', $customer->id)->get();
        foreach ($calls as $call) {
            $data['calls'][] = [
                'id' => $call->id,
                'date' => $call->created_at->format('Y-m-d H:i'),
                'duration' => $call->duration_seconds,
                'type' => $call->type,
                'notes' => $call->notes
            ];
        }
        
        // Collect messages
        $messages = DB::table('sms_message_logs')
            ->where('customer_id', $customer->id)
            ->get();
            
        foreach ($messages as $message) {
            $data['messages'][] = [
                'date' => $message->created_at,
                'channel' => $message->channel,
                'direction' => strpos($message->from, $customer->phone) !== false ? 'outbound' : 'inbound',
                'status' => $message->status
            ];
        }
        
        return $data;
    }
    
    /**
     * Generate export file
     */
    protected function generateExportFile(Customer $customer, array $data): string
    {
        $filename = 'gdpr-export-' . $customer->id . '-' . time() . '.json';
        $path = 'gdpr-exports/' . $filename;
        
        // Store as JSON
        Storage::put($path, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        
        // Create ZIP file with JSON and PDF summary
        $zipFilename = str_replace('.json', '.zip', $filename);
        $zipPath = storage_path('app/gdpr-exports/' . $zipFilename);
        
        $zip = new ZipArchive();
        if ($zip->open($zipPath, ZipArchive::CREATE) === TRUE) {
            // Add JSON file
            $zip->addFromString('data.json', json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            
            // Add README
            $readme = $this->generateReadme($customer, $data);
            $zip->addFromString('README.txt', $readme);
            
            $zip->close();
        }
        
        return 'gdpr-exports/' . $zipFilename;
    }
    
    /**
     * Generate README for export
     */
    protected function generateReadme(Customer $customer, array $data): string
    {
        $lang = $customer->preferred_language ?? 'de';
        
        if ($lang === 'de') {
            return "DATENAUSKUNFT GEMÄSS DSGVO\n" .
                   "==========================\n\n" .
                   "Datum: " . now()->format('d.m.Y H:i') . "\n" .
                   "Kunde: " . $customer->name . "\n\n" .
                   "Diese Datei enthält alle über Sie gespeicherten personenbezogenen Daten.\n\n" .
                   "Inhalt:\n" .
                   "- Persönliche Informationen\n" .
                   "- Einwilligungen\n" .
                   "- Termine (" . count($data['appointments']) . ")\n" .
                   "- Anrufe (" . count($data['calls']) . ")\n" .
                   "- Nachrichten (" . count($data['messages']) . ")\n\n" .
                   "Die Daten sind im JSON-Format gespeichert und können mit jedem Texteditor geöffnet werden.\n\n" .
                   "Bei Fragen wenden Sie sich bitte an: " . $customer->company->email;
        } else {
            return "GDPR DATA EXPORT\n" .
                   "================\n\n" .
                   "Date: " . now()->format('Y-m-d H:i') . "\n" .
                   "Customer: " . $customer->name . "\n\n" .
                   "This file contains all personal data stored about you.\n\n" .
                   "Contents:\n" .
                   "- Personal Information\n" .
                   "- Consents\n" .
                   "- Appointments (" . count($data['appointments']) . ")\n" .
                   "- Calls (" . count($data['calls']) . ")\n" .
                   "- Messages (" . count($data['messages']) . ")\n\n" .
                   "The data is stored in JSON format and can be opened with any text editor.\n\n" .
                   "For questions, please contact: " . $customer->company->email;
        }
    }
}
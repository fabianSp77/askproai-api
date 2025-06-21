<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\GdprRequest;
use App\Models\Appointment;
use App\Models\Call;
use App\Models\CustomerAuth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class GdprService
{
    /**
     * Export all customer data in a machine-readable format
     */
    public function exportCustomerData(Customer $customer): array
    {
        Log::info('Starting GDPR data export for customer', [
            'customer_id' => $customer->id,
            'company_id' => $customer->company_id,
        ]);

        $data = [
            'export_date' => now()->toIso8601String(),
            'customer' => $this->getCustomerPersonalData($customer),
            'appointments' => $this->getAppointmentData($customer),
            'calls' => $this->getCallData($customer),
            'invoices' => $this->getInvoiceData($customer),
            'consents' => $this->getConsentHistory($customer),
            'communications' => $this->getCommunicationHistory($customer),
            'notes' => $this->getCustomerNotes($customer),
        ];

        Log::info('GDPR data export completed', [
            'customer_id' => $customer->id,
            'data_size' => strlen(json_encode($data)),
        ]);

        return $data;
    }

    /**
     * Create a downloadable export file
     */
    public function createExportFile(Customer $customer): string
    {
        $data = $this->exportCustomerData($customer);
        $timestamp = now()->format('Y-m-d_H-i-s');
        $filename = "gdpr_export_{$customer->id}_{$timestamp}";
        
        // Create JSON file
        $jsonPath = "gdpr-exports/{$filename}.json";
        Storage::put($jsonPath, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        
        // Check if ZipArchive is available
        if (class_exists('ZipArchive')) {
            // Create ZIP archive with JSON and any related files
            $zipPath = "gdpr-exports/{$filename}.zip";
            $zip = new \ZipArchive();
            
            if ($zip->open(Storage::path($zipPath), \ZipArchive::CREATE) === true) {
                // Add JSON data
                $zip->addFile(Storage::path($jsonPath), 'data.json');
                
                // Add any uploaded files (e.g., profile pictures, documents)
                $this->addCustomerFiles($zip, $customer);
                
                $zip->close();
                
                // Clean up JSON file
                Storage::delete($jsonPath);
                
                return $zipPath;
            }
        }
        
        // Fallback: Return JSON file if ZIP is not available
        Log::warning('ZipArchive not available, returning JSON file for GDPR export', [
            'customer_id' => $customer->id,
        ]);
        
        return $jsonPath;
    }

    /**
     * Delete all customer data (right to be forgotten)
     */
    public function deleteCustomerData(Customer $customer, bool $anonymize = true): void
    {
        DB::transaction(function () use ($customer, $anonymize) {
            Log::warning('Starting GDPR data deletion', [
                'customer_id' => $customer->id,
                'anonymize' => $anonymize,
            ]);

            if ($anonymize) {
                // Anonymize instead of hard delete for legal/financial records
                $this->anonymizeCustomer($customer);
            } else {
                // Hard delete all related data
                $this->hardDeleteCustomerData($customer);
            }
        });
    }

    /**
     * Get customer personal data
     */
    private function getCustomerPersonalData(Customer $customer): array
    {
        return [
            'id' => $customer->id,
            'first_name' => $customer->first_name,
            'last_name' => $customer->last_name,
            'email' => $customer->email,
            'phone' => $customer->phone,
            'mobile' => $customer->mobile,
            'address' => $customer->address,
            'address2' => $customer->address2,
            'city' => $customer->city,
            'state' => $customer->state,
            'postal_code' => $customer->postal_code,
            'country' => $customer->country,
            'date_of_birth' => $customer->date_of_birth?->toDateString(),
            'gender' => $customer->gender,
            'language' => $customer->language,
            'notes' => $customer->notes,
            'tags' => $customer->tags,
            'created_at' => $customer->created_at->toIso8601String(),
            'updated_at' => $customer->updated_at->toIso8601String(),
        ];
    }

    /**
     * Get appointment data
     */
    private function getAppointmentData(Customer $customer): array
    {
        return $customer->appointments()
            ->with(['service', 'staff', 'branch'])
            ->get()
            ->map(function ($appointment) {
                return [
                    'id' => $appointment->id,
                    'date' => $appointment->date->toDateString(),
                    'start_time' => $appointment->start_time,
                    'end_time' => $appointment->end_time,
                    'status' => $appointment->status,
                    'service' => $appointment->service?->name,
                    'staff' => $appointment->staff?->name,
                    'branch' => $appointment->branch?->name,
                    'price' => $appointment->price,
                    'notes' => $appointment->notes,
                    'created_at' => $appointment->created_at->toIso8601String(),
                ];
            })
            ->toArray();
    }

    /**
     * Get call data
     */
    private function getCallData(Customer $customer): array
    {
        return $customer->calls()
            ->select([
                'id',
                'call_id',
                'from_number',
                'to_number',
                'direction',
                'status',
                'start_timestamp',
                'end_timestamp',
                'duration_minutes',
                'recording_url',
                'transcript',
                'created_at',
            ])
            ->get()
            ->toArray();
    }

    /**
     * Get invoice data
     */
    private function getInvoiceData(Customer $customer): array
    {
        return $customer->invoices()
            ->with('items')
            ->get()
            ->map(function ($invoice) {
                return [
                    'id' => $invoice->id,
                    'invoice_number' => $invoice->invoice_number,
                    'date' => $invoice->date->toDateString(),
                    'due_date' => $invoice->due_date?->toDateString(),
                    'total' => $invoice->total,
                    'tax' => $invoice->tax,
                    'status' => $invoice->status,
                    'items' => $invoice->items->map(function ($item) {
                        return [
                            'description' => $item->description,
                            'quantity' => $item->quantity,
                            'price' => $item->price,
                            'total' => $item->total,
                        ];
                    })->toArray(),
                ];
            })
            ->toArray();
    }

    /**
     * Get consent history
     */
    private function getConsentHistory(Customer $customer): array
    {
        return $customer->cookieConsents()
            ->orderBy('consented_at', 'desc')
            ->get()
            ->map(function ($consent) {
                return [
                    'consented_at' => $consent->consented_at->toIso8601String(),
                    'withdrawn_at' => $consent->withdrawn_at?->toIso8601String(),
                    'necessary_cookies' => $consent->necessary_cookies,
                    'functional_cookies' => $consent->functional_cookies,
                    'analytics_cookies' => $consent->analytics_cookies,
                    'marketing_cookies' => $consent->marketing_cookies,
                    'ip_address' => $consent->ip_address,
                ];
            })
            ->toArray();
    }

    /**
     * Get communication history
     */
    private function getCommunicationHistory(Customer $customer): array
    {
        $communications = [];

        // Email notifications
        if (method_exists($customer, 'notifications')) {
            $communications['notifications'] = $customer->notifications()
                ->select(['id', 'type', 'data', 'read_at', 'created_at'])
                ->get()
                ->toArray();
        }

        // Activity logs
        if (class_exists('Spatie\Activitylog\Models\Activity')) {
            $communications['activity_logs'] = \Spatie\Activitylog\Models\Activity::where('subject_type', Customer::class)
                ->where('subject_id', $customer->id)
                ->select(['description', 'properties', 'created_at'])
                ->get()
                ->toArray();
        }

        return $communications;
    }

    /**
     * Get customer notes from various sources
     */
    private function getCustomerNotes(Customer $customer): array
    {
        $notes = [];

        // Customer notes field
        if ($customer->notes) {
            $notes[] = [
                'type' => 'profile_notes',
                'content' => $customer->notes,
                'created_at' => $customer->created_at->toIso8601String(),
            ];
        }

        // Appointment notes
        $appointmentNotes = $customer->appointments()
            ->whereNotNull('notes')
            ->select(['notes', 'date', 'created_at'])
            ->get()
            ->map(function ($appointment) {
                return [
                    'type' => 'appointment_notes',
                    'content' => $appointment->notes,
                    'date' => $appointment->date->toDateString(),
                    'created_at' => $appointment->created_at->toIso8601String(),
                ];
            })
            ->toArray();

        return array_merge($notes, $appointmentNotes);
    }

    /**
     * Add customer files to ZIP
     */
    private function addCustomerFiles(\ZipArchive $zip, Customer $customer): void
    {
        // Add profile picture if exists
        if ($customer->profile_picture && Storage::exists($customer->profile_picture)) {
            $zip->addFile(
                Storage::path($customer->profile_picture),
                'files/profile_picture_' . basename($customer->profile_picture)
            );
        }

        // Add any other customer files
        // This is where you'd add invoice PDFs, documents, etc.
    }

    /**
     * Anonymize customer data
     */
    private function anonymizeCustomer(Customer $customer): void
    {
        $anonymizedData = [
            'first_name' => 'DELETED',
            'last_name' => 'USER',
            'email' => "deleted.user.{$customer->id}@anonymized.local",
            'phone' => null,
            'mobile' => null,
            'address' => null,
            'address2' => null,
            'city' => null,
            'state' => null,
            'postal_code' => null,
            'country' => null,
            'date_of_birth' => null,
            'gender' => null,
            'notes' => 'User data anonymized due to GDPR request',
            'is_deleted' => true,
            'deleted_at' => now(),
        ];

        $customer->update($anonymizedData);

        // Anonymize related data
        $customer->appointments()->update(['notes' => null]);
        $customer->calls()->update(['transcript' => null, 'recording_url' => null]);
        
        // Delete authentication data
        CustomerAuth::where('customer_id', $customer->id)->delete();
    }

    /**
     * Hard delete customer data
     */
    private function hardDeleteCustomerData(Customer $customer): void
    {
        // Delete in correct order to respect foreign key constraints
        $customer->cookieConsents()->delete();
        $customer->gdprRequests()->delete();
        $customer->appointments()->delete();
        $customer->calls()->delete();
        $customer->invoices()->delete();
        CustomerAuth::where('customer_id', $customer->id)->delete();
        
        // Finally delete the customer
        $customer->delete();
    }
}
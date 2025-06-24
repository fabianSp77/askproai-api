<?php

namespace App\Jobs;

use App\Models\Company;
use App\Models\Appointment;
use App\Services\CalcomV2Service;
use App\Services\MCP\MCPGateway;
use Illuminate\Support\Str;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Filament\Notifications\Notification;
use Filament\Notifications\Actions\Action;
use App\Helpers\SafeQueryHelper;

class SyncCalcomBookingsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected Company $company;
    protected string $apiKey;

    public function __construct(Company $company, string $apiKey)
    {
        $this->company = $company;
        $this->apiKey = $apiKey;
    }

    public function handle()
    {
        Log::info('Starting Cal.com sync for company via MCP', [
            'company_id' => $this->company->id,
            'company_name' => $this->company->name,
            'using_mcp' => true
        ]);

        try {
            // Use MCP Gateway for sync
            $mcpGateway = app(MCPGateway::class);
            
            // Prepare sync request
            $fromDate = Carbon::now()->subMonths(3)->startOfDay();
            $toDate = Carbon::now()->addMonths(1)->endOfDay();
            
            $mcpRequest = [
                'jsonrpc' => '2.0',
                'method' => 'calcom.syncBookings',
                'params' => [
                    'company_id' => $this->company->id,
                    'from_date' => $fromDate->toIso8601String(),
                    'to_date' => $toDate->toIso8601String()
                ],
                'id' => Str::uuid()->toString()
            ];
            
            Log::info('Executing Cal.com sync via MCP', [
                'company_id' => $this->company->id,
                'from_date' => $fromDate->toDateString(),
                'to_date' => $toDate->toDateString()
            ]);
            
            // Execute sync via MCP
            $response = $mcpGateway->process($mcpRequest);
            
            if (isset($response['error'])) {
                throw new \Exception($response['error']['message'] ?? 'MCP sync failed');
            }
            
            $result = $response['result'] ?? [];
            
            if (!($result['success'] ?? false)) {
                throw new \Exception($result['message'] ?? 'Sync failed');
            }
            
            $stats = $result['stats'] ?? [];
            $synced = $stats['synced'] ?? 0;
            $created = $stats['created'] ?? 0;
            $updated = $stats['updated'] ?? 0;
            $errors = $stats['errors'] ?? 0;
            
            // Benachrichtigung senden
            $message = sprintf(
                'Synchronisation abgeschlossen: %d Termine synchronisiert (%d neu, %d aktualisiert)',
                $synced,
                $created,
                $updated
            );
            
            if ($errors > 0) {
                $message .= sprintf(', %d Fehler', $errors);
            }
            
            // Only send notification if we have a user to send to
            $user = $this->company->users()->first();
            if ($user) {
                Notification::make()
                    ->title('Cal.com Synchronisation (via MCP)')
                    ->body($message)
                    ->success()
                    ->sendToDatabase($user);
            }
                
            Log::info('Cal.com sync completed via MCP', [
                'company_id' => $this->company->id,
                'synced' => $synced,
                'created' => $created,
                'updated' => $updated,
                'errors' => $errors,
                'using_mcp' => true
            ]);
            
        } catch (\Exception $e) {
            Log::error('Cal.com sync failed', [
                'company_id' => $this->company->id,
                'error' => $e->getMessage()
            ]);
            
            // Only send notification if we have a user to send to
            $user = $this->company->users()->first();
            if ($user) {
                Notification::make()
                    ->title('Cal.com Synchronisation fehlgeschlagen')
                    ->body('Fehler: ' . $e->getMessage())
                    ->danger()
                    ->sendToDatabase($user);
            }
        }
    }
    
    protected function fetchAllBookings($calcomService, $fromDate, $toDate): array
    {
        $allBookings = [];
        $page = 1;
        $limit = 100;
        
        do {
            $response = $calcomService->getBookings([
                'from' => $fromDate->toIso8601String(),
                'to' => $toDate->toIso8601String(),
                'page' => $page,
                'limit' => $limit
            ]);
            
            if (!$response['success']) {
                throw new \Exception('Failed to fetch bookings: ' . ($response['error'] ?? 'Unknown error'));
            }
            
            $bookings = $response['data']['bookings'] ?? [];
            $allBookings = array_merge($allBookings, $bookings);
            
            $totalPages = $response['data']['total_pages'] ?? 1;
            $page++;
            
        } while ($page <= $totalPages);
        
        return $allBookings;
    }
    
    protected function syncBooking(array $bookingData): string
    {
        // Finde oder erstelle den Termin basierend auf der Cal.com Booking ID
        $appointment = Appointment::where('calcom_v2_booking_id', $bookingData['id'])
            ->orWhere('calcom_booking_id', $bookingData['id'])
            ->first();
            
        $isNew = !$appointment;
        
        if (!$appointment) {
            $appointment = new Appointment();
            $appointment->company_id = $this->company->id;
        }
        
        // Finde die zugehörigen Ressourcen
        $branch = $this->findBranchForBooking($bookingData);
        $staff = $this->findStaffForBooking($bookingData);
        $service = $this->findServiceForBooking($bookingData);
        $customer = $this->findOrCreateCustomer($bookingData);
        
        // Aktualisiere die Appointment-Daten
        // v2 API uses 'start'/'end' instead of 'startTime'/'endTime'
        $startTime = $bookingData['start'] ?? $bookingData['startTime'] ?? null;
        $endTime = $bookingData['end'] ?? $bookingData['endTime'] ?? null;
        
        $appointment->fill([
            'calcom_v2_booking_id' => $bookingData['id'],
            'calcom_booking_id' => $bookingData['id'], // Für Abwärtskompatibilität
            'external_id' => $bookingData['uid'] ?? null,
            'branch_id' => $branch?->id,
            'staff_id' => $staff?->id,
            'service_id' => $service?->id,
            'customer_id' => $customer?->id,
            'starts_at' => $startTime ? Carbon::parse($startTime) : null,
            'ends_at' => $endTime ? Carbon::parse($endTime) : null,
            'status' => $this->mapCalcomStatus($bookingData['status']),
            'notes' => $bookingData['description'] ?? null,
            'price' => $service?->price ?? 0,
            'calcom_event_type_id' => $bookingData['eventTypeId'] ?? $bookingData['eventType']['id'] ?? null,
        ]);
        
        // Zusätzliche Metadaten speichern
        $appointment->meta = array_merge($appointment->meta ?? [], [
            'calcom_sync' => [
                'last_synced_at' => now()->toIso8601String(),
                'booking_status' => $bookingData['status'],
                'reschedule_uid' => $bookingData['rescheduleUid'] ?? null,
                'location' => $bookingData['location'] ?? null,
                'metadata' => $bookingData['metadata'] ?? null,
                'responses' => $bookingData['responses'] ?? null,
            ]
        ]);
        
        $appointment->save();
        
        return $isNew ? 'created' : 'updated';
    }
    
    protected function findBranchForBooking(array $bookingData)
    {
        // Versuche Branch über Metadaten oder Event Type zu finden
        $metadata = $bookingData['metadata'] ?? [];
        if (isset($metadata['branch_id'])) {
            return $this->company->branches()->find($metadata['branch_id']);
        }
        
        // Fallback: Erste Branch der Company
        return $this->company->branches()->first();
    }
    
    protected function findStaffForBooking(array $bookingData)
    {
        // Finde Staff über User/Host Information
        $user = $bookingData['user'] ?? null;
        if ($user) {
            // Suche nach Email oder Cal.com User ID
            return $this->company->staff()
                ->where('email', $user['email'] ?? '')
                ->orWhere('calcom_user_id', $user['id'] ?? '')
                ->first();
        }
        
        return null;
    }
    
    protected function findServiceForBooking(array $bookingData)
    {
        // Finde Service über Event Type
        $eventTypeId = $bookingData['eventTypeId'] ?? null;
        if ($eventTypeId) {
            $eventType = $this->company->calcomEventTypes()
                ->where('calcom_event_type_id', $eventTypeId)
                ->first();
                
            if ($eventType && $eventType->service_id) {
                return $eventType->service;
            }
        }
        
        // Fallback: Versuche über Titel zu matchen
        $title = $bookingData['title'] ?? '';
        if ($title) {
            return $this->company->services()
                ->where(function($q) use ($title) {
                    SafeQueryHelper::whereLike($q, 'name', $title);
                })
                ->first();
        }
        
        return null;
    }
    
    protected function findOrCreateCustomer(array $bookingData)
    {
        $attendees = $bookingData['attendees'] ?? [];
        if (empty($attendees)) {
            return null;
        }
        
        $attendee = $attendees[0]; // Nehme den ersten Teilnehmer
        
        // Suche nach Email oder Telefonnummer
        $customer = $this->company->customers()
            ->where('email', $attendee['email'])
            ->first();
            
        if (!$customer && isset($attendee['phoneNumber'])) {
            $customer = $this->company->customers()
                ->where('phone', $attendee['phoneNumber'])
                ->first();
        }
        
        // Erstelle neuen Kunden falls nicht gefunden
        if (!$customer) {
            $customer = $this->company->customers()->create([
                'name' => $attendee['name'] ?? 'Unbekannt',
                'email' => $attendee['email'],
                'phone' => $attendee['phoneNumber'] ?? null,
                'source' => 'cal.com',
                'notes' => 'Automatisch erstellt durch Cal.com Sync'
            ]);
        }
        
        return $customer;
    }
    
    protected function mapCalcomStatus(string $calcomStatus): string
    {
        return match (strtoupper($calcomStatus)) {
            'ACCEPTED' => 'confirmed',
            'PENDING' => 'pending',
            'CANCELLED' => 'cancelled',
            'REJECTED' => 'cancelled',
            default => 'pending'
        };
    }
}
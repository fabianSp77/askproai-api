<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Appointment;
use App\Models\Customer;
use App\Models\Staff;
use App\Models\CalcomEventType;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ExtractCalcomPayloadData extends Command
{
    protected $signature = 'calcom:extract-payload-data 
                           {--batch=100 : Number of records to process per batch}
                           {--dry-run : Run without saving to database}';

    protected $description = 'Extract Cal.com V2 payload data into dedicated fields for better UI display';

    protected array $stats = [
        'processed' => 0,
        'updated' => 0,
        'attendees_extracted' => 0,
        'locations_extracted' => 0,
        'responses_extracted' => 0,
        'failed' => 0,
    ];

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');
        $batchSize = (int) $this->option('batch');

        $this->info('═══════════════════════════════════════════════════════════════');
        $this->info('  Cal.com V2 Payload Data Extraction');
        $this->info('═══════════════════════════════════════════════════════════════');
        $this->info('');
        $this->info('🔧 Configuration:');
        $this->info('  • Mode: ' . ($dryRun ? 'DRY RUN (no changes)' : 'LIVE'));
        $this->info('  • Batch Size: ' . $batchSize);
        $this->info('');

        $totalAppointments = Appointment::whereNotNull('payload')
            ->where('source', 'cal.com')
            ->count();

        $this->info("📊 Found {$totalAppointments} Cal.com appointments with payload data");
        $this->info('');

        if ($totalAppointments === 0) {
            $this->warn('No appointments to process.');
            return self::SUCCESS;
        }

        $bar = $this->output->createProgressBar($totalAppointments);
        $bar->start();

        Appointment::whereNotNull('payload')
            ->where('source', 'cal.com')
            ->chunk($batchSize, function ($appointments) use ($dryRun, $bar) {
                foreach ($appointments as $appointment) {
                    $this->processAppointment($appointment, $dryRun);
                    $bar->advance();
                }
            });

        $bar->finish();
        $this->newLine(2);

        $this->displaySummary();

        return self::SUCCESS;
    }

    protected function processAppointment(Appointment $appointment, bool $dryRun): void
    {
        $this->stats['processed']++;

        try {
            // Get raw payload from database (bypass model casting)
            $raw = DB::table('appointments')
                ->where('id', $appointment->id)
                ->select('payload')
                ->first();
            
            if (!$raw || !$raw->payload) {
                $this->stats['failed']++;
                return;
            }
            
            // Handle double-encoded JSON (JSON string containing JSON)
            $firstDecode = json_decode($raw->payload);
            
            if (is_string($firstDecode)) {
                // It's double-encoded, decode again
                $payload = json_decode($firstDecode, true);
            } else {
                // Single encoded, convert to array
                $payload = json_decode($raw->payload, true);
            }
            
            if (!$payload || !is_array($payload)) {
                $this->stats['failed']++;
                Log::warning('[ExtractCalcomPayloadData] Failed to decode payload', [
                    'appointment_id' => $appointment->id,
                    'json_error' => json_last_error_msg()
                ]);
                return;
            }

            $updates = [];

            // Extract attendees
            if (isset($payload['attendees']) && is_array($payload['attendees'])) {
                $attendees = [];
                foreach ($payload['attendees'] as $attendee) {
                    $attendees[] = [
                        'name' => $attendee['name'] ?? '',
                        'email' => $attendee['email'] ?? '',
                        'timezone' => $attendee['timeZone'] ?? 'Europe/Berlin',
                        'locale' => $attendee['locale'] ?? 'de',
                    ];
                }
                if (!empty($attendees)) {
                    $updates['attendees'] = $attendees;
                    $this->stats['attendees_extracted']++;
                }
            }

            // Extract location details
            if (isset($payload['location'])) {
                $location = $payload['location'];
                
                // Determine location type
                if (str_contains($location, 'cal.com/video') || str_contains($location, 'meet.google.com') || str_contains($location, 'zoom.us')) {
                    $updates['location_type'] = 'video';
                    $updates['location_value'] = $location;
                } elseif (str_contains($location, 'phone:') || str_contains($location, 'tel:')) {
                    $updates['location_type'] = 'phone';
                    $updates['location_value'] = str_replace(['phone:', 'tel:'], '', $location);
                } elseif (str_contains($location, '@')) {
                    $updates['location_type'] = 'email';
                    $updates['location_value'] = $location;
                } elseif ($location === 'integrations:daily' || str_contains($location, 'integrations:')) {
                    $updates['location_type'] = 'integration';
                    $updates['location_value'] = $location;
                } else {
                    $updates['location_type'] = 'inPerson';
                    $updates['location_value'] = $location;
                }
                
                $this->stats['locations_extracted']++;
            }

            // Extract booking field responses
            if (isset($payload['bookingFieldsResponses']) && is_array($payload['bookingFieldsResponses'])) {
                $responses = [];
                foreach ($payload['bookingFieldsResponses'] as $field => $value) {
                    // Handle array values (like location array)
                    if (is_array($value)) {
                        // For location arrays, try to extract a meaningful value
                        if ($field === 'location' && isset($value['value'])) {
                            $value = $value['value'];
                        } else {
                            $value = json_encode($value);
                        }
                    }
                    $responses[$field] = $value;
                }
                if (!empty($responses)) {
                    $updates['responses'] = $responses;
                    $this->stats['responses_extracted']++;
                }
            }

            // Extract additional metadata
            $metadata = [];
            
            // Add title and description
            if (isset($payload['title'])) {
                $metadata['title'] = $payload['title'];
            }
            if (isset($payload['description']) && !empty($payload['description'])) {
                $metadata['description'] = $payload['description'];
            }
            
            // Add host information
            if (isset($payload['hosts']) && is_array($payload['hosts'])) {
                $metadata['hosts'] = $payload['hosts'];
            }
            
            // Add rating if exists
            if (isset($payload['rating'])) {
                $metadata['rating'] = $payload['rating'];
            }
            
            // Add recurring information
            if (isset($payload['recurringEventId'])) {
                $updates['recurring_event_id'] = $payload['recurringEventId'];
                $updates['is_recurring'] = true;
            }
            
            // Add cancellation/rejection reasons
            if (isset($payload['cancellationReason'])) {
                $updates['cancellation_reason'] = $payload['cancellationReason'];
            }
            if (isset($payload['rejectionReason'])) {
                $updates['rejected_reason'] = $payload['rejectionReason'];
            }
            
            // Store metadata
            if (!empty($metadata)) {
                $updates['booking_metadata'] = $metadata;
            }

            // Update meeting URL if available
            if (isset($payload['meetingUrl']) && !$appointment->meeting_url) {
                $updates['meeting_url'] = $payload['meetingUrl'];
            }

            // Update the appointment
            if (!empty($updates) && !$dryRun) {
                $appointment->update($updates);
                $this->stats['updated']++;
            } elseif (!empty($updates)) {
                $this->stats['updated']++;
            }

        } catch (\Exception $e) {
            $this->stats['failed']++;
            Log::error('[ExtractCalcomPayloadData] Error processing appointment', [
                'appointment_id' => $appointment->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    protected function displaySummary(): void
    {
        $this->info('═══════════════════════════════════════════════════════════════');
        $this->info('  Extraction Summary');
        $this->info('═══════════════════════════════════════════════════════════════');
        $this->info('');
        $this->info('📊 Results:');
        $this->info('  • Processed: ' . $this->stats['processed']);
        $this->info('  • Updated: ' . $this->stats['updated']);
        $this->info('  • Attendees Extracted: ' . $this->stats['attendees_extracted']);
        $this->info('  • Locations Extracted: ' . $this->stats['locations_extracted']);
        $this->info('  • Responses Extracted: ' . $this->stats['responses_extracted']);
        
        if ($this->stats['failed'] > 0) {
            $this->warn('  • Failed: ' . $this->stats['failed']);
        }
        
        $this->info('');
        $this->info('✅ Extraction complete!');
        
        if ($this->option('dry-run')) {
            $this->warn('');
            $this->warn('This was a DRY RUN - no changes were saved to the database.');
            $this->warn('Run without --dry-run to apply changes.');
        }
    }
}
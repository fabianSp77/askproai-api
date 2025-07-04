<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\AppointmentExtractionService;
use App\Models\Call;

class ExtractAppointmentsFromCalls extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'appointments:extract-from-calls 
                            {--dry-run : Show what would be done without creating appointments}
                            {--limit=50 : Number of calls to process}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Extract appointment requests from call transcripts and create appointments';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔍 Extracting appointments from call transcripts...');
        
        $dryRun = $this->option('dry-run');
        $limit = $this->option('limit');
        
        if ($dryRun) {
            $this->warn('DRY RUN MODE - No appointments will be created');
        }

        $extractor = app(AppointmentExtractionService::class);

        // Get calls with transcripts but no appointments
        $calls = Call::withoutGlobalScopes()
            ->whereNotNull('transcript')
            ->whereNull('appointment_id')
            ->where(function($query) {
                $query->where('transcript', 'LIKE', '%termin%')
                      ->orWhere('transcript', 'LIKE', '%appointment%')
                      ->orWhere('transcript', 'LIKE', '%morgen%uhr%')
                      ->orWhere('transcript', 'LIKE', '%buchen%');
            })
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();

        $this->info("Found {$calls->count()} calls to process");

        $created = 0;
        $failed = 0;

        $this->withProgressBar($calls, function ($call) use ($extractor, $dryRun, &$created, &$failed) {
            $this->line("\n");
            $this->info("Processing call {$call->id} from {$call->from_number}");
            
            // Show transcript excerpt
            $this->line("Transcript: " . substr($call->transcript, 0, 200) . "...");
            
            // Extract appointment data
            $appointmentData = $extractor->extractFromTranscript($call->transcript);
            
            if (!$appointmentData) {
                $this->warn("  ❌ No appointment data found");
                $failed++;
                return;
            }
            
            $this->info("  ✅ Extracted appointment data:");
            $this->line("     Date: " . $appointmentData['date']);
            $this->line("     Time: " . $appointmentData['time']);
            $this->line("     Service: " . ($appointmentData['service'] ?? 'N/A'));
            $this->line("     Customer: " . ($appointmentData['customer_name'] ?? 'N/A'));
            $this->line("     Confidence: " . $appointmentData['confidence'] . "%");
            
            if (!$dryRun) {
                $appointment = $extractor->createAppointmentFromExtraction($call, $appointmentData);
                
                if ($appointment) {
                    $this->info("  ✅ Appointment created! ID: {$appointment->id}");
                    $created++;
                } else {
                    $this->error("  ❌ Failed to create appointment");
                    $failed++;
                }
            }
        });

        $this->line("\n");
        $this->info("🎉 Processing complete!");
        $this->info("   Appointments created: $created");
        $this->warn("   Failed: $failed");
        
        if ($dryRun) {
            $this->warn("This was a dry run. Use without --dry-run to create appointments.");
        }
    }
}
<?php

namespace App\Console\Commands;

use App\Models\Call;
use App\Models\Appointment;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ExtractBookingsFromTranscripts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'calls:extract-bookings
                            {--days=7 : Number of days to look back}
                            {--dry-run : Show what would be done without making changes}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Extract booking attempts from call transcripts and identify missed appointments';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $days = $this->option('days');
        $dryRun = $this->option('dry-run');

        $this->info('🔍 Extracting Bookings from Call Transcripts');
        $this->info('==========================================');
        $this->info('Looking back: ' . $days . ' days');
        $this->info('Mode: ' . ($dryRun ? 'DRY RUN' : 'LIVE'));
        $this->newLine();

        // Get calls with transcripts that might contain booking attempts
        $calls = Call::whereNotNull('transcript')
            ->where('transcript', '!=', '')
            ->where('created_at', '>=', now()->subDays($days))
            ->whereNull('converted_appointment_id')
            ->get();

        $this->info('Found ' . $calls->count() . ' calls to analyze');
        $this->newLine();

        $bookingAttempts = 0;
        $results = [];

        foreach ($calls as $call) {
            $bookingInfo = $this->extractBookingInfo($call);

            if ($bookingInfo['is_booking_attempt']) {
                $bookingAttempts++;

                $this->line('📞 Call #' . $call->id . ' (' . $call->created_at->format('Y-m-d H:i') . ')');
                $this->line('  Customer: ' . ($call->customer->name ?? 'Unknown'));
                $this->line('  Keywords found: ' . implode(', ', $bookingInfo['keywords_found']));

                if ($bookingInfo['date_mentioned']) {
                    $this->line('  📅 Date/Time mentioned: ' . $bookingInfo['date_mentioned']);
                }

                if ($bookingInfo['service_mentioned']) {
                    $this->line('  ✂️ Service mentioned: ' . $bookingInfo['service_mentioned']);
                }

                $this->line('  Confidence: ' . $bookingInfo['confidence'] . '%');

                // Show transcript excerpt
                $excerpt = $this->getRelevantExcerpt($call->transcript, $bookingInfo['keywords_found']);
                $this->line('  Excerpt: "' . $excerpt . '"');

                if (!$dryRun && $bookingInfo['confidence'] >= 70) {
                    // Update call to mark booking attempt
                    $call->update([
                        'appointment_made' => true,
                        'analysis' => array_merge(
                            $call->analysis ?? [],
                            ['booking_attempt' => $bookingInfo]
                        ),
                    ]);
                    $this->info('  ✅ Marked as booking attempt');
                }

                $this->newLine();

                $results[] = [
                    'call_id' => $call->id,
                    'customer' => $call->customer->name ?? 'Unknown',
                    'confidence' => $bookingInfo['confidence'],
                    'date' => $call->created_at->format('Y-m-d H:i'),
                ];
            }
        }

        // Summary
        $this->newLine();
        $this->table(
            ['Metric', 'Count'],
            [
                ['Total Calls Analyzed', $calls->count()],
                ['Booking Attempts Found', $bookingAttempts],
                ['Success Rate', $bookingAttempts > 0 ? round(($bookingAttempts / $calls->count()) * 100, 1) . '%' : '0%'],
            ]
        );

        if ($bookingAttempts > 0) {
            $this->newLine();
            $this->warn('⚠️ Found ' . $bookingAttempts . ' booking attempts without appointments!');
            $this->warn('These customers tried to book but no appointment was created.');
            $this->newLine();
            $this->info('Recommended actions:');
            $this->line('1. Configure Retell agent to capture booking details');
            $this->line('2. Implement automated booking creation from transcripts');
            $this->line('3. Follow up with these customers manually');
        }

        return Command::SUCCESS;
    }

    /**
     * Extract booking information from call transcript
     */
    private function extractBookingInfo(Call $call): array
    {
        $transcript = strtolower($call->transcript);

        // Booking keywords (German and English)
        $bookingKeywords = [
            'termin' => 20,
            'appointment' => 20,
            'buchen' => 15,
            'booking' => 15,
            'vereinbaren' => 15,
            'schedule' => 10,
            'zeit' => 5,
            'datum' => 10,
            'morgen' => 10,
            'tomorrow' => 10,
            'nächste woche' => 15,
            'next week' => 15,
            'montag' => 8,
            'dienstag' => 8,
            'mittwoch' => 8,
            'donnerstag' => 8,
            'freitag' => 8,
            'samstag' => 8,
            'uhr' => 8,
        ];

        // Service keywords
        $serviceKeywords = [
            'haarschnitt',
            'färben',
            'tönung',
            'styling',
            'beratung',
            'haircut',
            'color',
            'consultation',
        ];

        $keywordsFound = [];
        $confidence = 0;

        // Check for booking keywords
        foreach ($bookingKeywords as $keyword => $weight) {
            if (str_contains($transcript, $keyword)) {
                $keywordsFound[] = $keyword;
                $confidence += $weight;
            }
        }

        // Check for service mentions
        $serviceMentioned = null;
        foreach ($serviceKeywords as $service) {
            if (str_contains($transcript, $service)) {
                $serviceMentioned = $service;
                $confidence += 10;
                break;
            }
        }

        // Extract date/time mentions (basic pattern matching)
        $dateMentioned = null;
        if (preg_match('/(\d{1,2})\s*(uhr|:00|\.00)/i', $transcript, $matches)) {
            $dateMentioned = $matches[0];
        }

        // Cap confidence at 100
        $confidence = min($confidence, 100);

        return [
            'is_booking_attempt' => $confidence >= 30,
            'confidence' => $confidence,
            'keywords_found' => $keywordsFound,
            'service_mentioned' => $serviceMentioned,
            'date_mentioned' => $dateMentioned,
        ];
    }

    /**
     * Get relevant excerpt from transcript
     */
    private function getRelevantExcerpt(string $transcript, array $keywords): string
    {
        if (empty($keywords)) {
            return substr($transcript, 0, 150);
        }

        // Find first keyword position
        $firstKeyword = $keywords[0];
        $position = stripos($transcript, $firstKeyword);

        if ($position === false) {
            return substr($transcript, 0, 150);
        }

        // Get context around keyword
        $start = max(0, $position - 50);
        $excerpt = substr($transcript, $start, 200);

        // Clean up
        if ($start > 0) {
            $excerpt = '...' . $excerpt;
        }
        if (strlen($transcript) > $start + 200) {
            $excerpt .= '...';
        }

        return $excerpt;
    }
}

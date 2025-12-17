<?php

namespace App\Console\Commands;

use App\Models\Call;
use Illuminate\Console\Command;

class ExtractCustomerNamesCommand extends Command
{
    protected $signature = 'calls:extract-names {--limit=50}';
    protected $description = 'Extract customer names from call transcripts';

    public function handle()
    {
        $this->info('=== EXTRACTING CUSTOMER NAMES FROM TRANSCRIPTS ===');
        $this->newLine();

        // Simple regex patterns for German names
        $patterns = [
            // "Hans Schulze mein Name"
            '/([A-ZÄÖÜ][a-zäöüß]+\s+[A-ZÄÖÜ][a-zäöüß]+)\s+mein\s+Name/iu',

            // "guten Tag, Hans Schulze"
            '/guten\s+Tag,?\s+([A-ZÄÖÜ][a-zäöüß]+\s+[A-ZÄÖÜ][a-zäöüß]+)/iu',

            // "Ja, Hans Schuster, ich"
            '/^[^A-Z]*([A-ZÄÖÜ][a-zäöüß]+\s+[A-ZÄÖÜ][a-zäöüß]+)[,]/um',
        ];

        $limit = $this->option('limit');

        $calls = Call::whereRaw('customer_name IS NULL OR customer_name = ""')
            ->whereNotNull('transcript')
            ->where('created_at', '>=', '2025-11-10')
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();

        $this->info("Calls to process: {$calls->count()}");
        $this->newLine();

        $extracted = 0;
        $failed = 0;

        $progressBar = $this->output->createProgressBar($calls->count());

        foreach ($calls as $call) {
            $name = null;

            // PRIORITY 1: Try analysis first
            if (is_array($call->analysis)) {
                $name = $call->analysis['custom_analysis_data']['caller_full_name'] ??
                       $call->analysis['custom_analysis_data']['patient_full_name'] ??
                       $call->analysis['caller_full_name'] ??
                       $call->analysis['patient_full_name'] ??
                       null;
            }

            // PRIORITY 2: Fallback to transcript extraction
            if (!$name && $call->transcript) {
                foreach ($patterns as $pattern) {
                    if (preg_match($pattern, $call->transcript, $matches)) {
                        $name = trim($matches[1]);

                        // Filter false positives
                        $lower = strtolower($name);
                        $blacklist = [
                            'guten tag', 'ja bitte', 'herren haar', 'ganz müller',
                            'bei friseur', 'hans mein', 'kann schuster', 'bei agent',
                            'schuster ist', 'äh hans', 'an schuster'
                        ];

                        // Also filter names that are likely mistakes
                        $valid = !in_array($lower, $blacklist) &&
                                strlen($name) > 3 &&
                                !str_contains($lower, 'bei ') &&
                                !str_contains($lower, ' mein') &&
                                !str_contains($lower, 'äh ');

                        if ($valid) {
                            break;
                        }
                        $name = null;
                    }
                }
            }

            if ($name && strlen($name) > 3) {
                try {
                    $call->forceFill(['customer_name' => $name])->saveQuietly();
                    $extracted++;
                    $this->line("✅ Call #{$call->id} → \"{$name}\"");
                } catch (\Exception $e) {
                    $this->error("❌ Call #{$call->id}: " . $e->getMessage());
                    $failed++;
                }
            } else {
                $failed++;
            }

            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine(2);

        $this->info("✅ Extracted: {$extracted}");
        $this->warn("❌ Failed: {$failed}");

        return self::SUCCESS;
    }
}

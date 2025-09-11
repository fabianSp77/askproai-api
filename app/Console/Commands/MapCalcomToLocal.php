<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Staff;
use App\Models\Service;
use App\Models\Company;
use App\Models\Branch;
use App\Models\CalcomEventType;
use App\Models\Customer;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class MapCalcomToLocal extends Command
{
    protected $signature = 'calcom:map-entities 
                           {--type= : Entity type to map (users|event-types|teams|all)}
                           {--auto : Automatically apply best matches}
                           {--threshold=70 : Matching confidence threshold (0-100)}';

    protected $description = 'Intelligently map Cal.com entities to local database entities';

    protected int $threshold;
    protected bool $autoApply;
    protected array $mappingStats = [
        'users_mapped' => 0,
        'event_types_mapped' => 0,
        'teams_mapped' => 0,
        'ambiguous' => 0,
        'no_match' => 0,
    ];

    public function handle(): int
    {
        $this->threshold = (int) $this->option('threshold');
        $this->autoApply = $this->option('auto');
        $type = $this->option('type') ?? 'all';

        $this->info('═══════════════════════════════════════════════════════════════');
        $this->info('  Cal.com Entity Mapping Tool');
        $this->info('═══════════════════════════════════════════════════════════════');
        $this->info('');
        $this->info('⚙️  Configuration:');
        $this->info('  • Mapping Type: ' . ucfirst($type));
        $this->info('  • Confidence Threshold: ' . $this->threshold . '%');
        $this->info('  • Auto-Apply: ' . ($this->autoApply ? 'Yes' : 'No (Interactive)'));
        $this->info('');

        switch ($type) {
            case 'users':
                $this->mapUsers();
                break;
            case 'event-types':
                $this->mapEventTypes();
                break;
            case 'teams':
                $this->mapTeams();
                break;
            case 'all':
                $this->mapAll();
                break;
            default:
                $this->error("Unknown mapping type: {$type}");
                return self::FAILURE;
        }

        $this->displayMappingSummary();
        return self::SUCCESS;
    }

    protected function mapAll(): void
    {
        $this->mapTeams();
        $this->mapUsers();
        $this->mapEventTypes();
    }

    protected function mapUsers(): void
    {
        $this->info('👤 Mapping Cal.com Users to Staff Members...');
        $this->newLine();

        $calcomUsers = DB::table('calcom_users')
            ->whereNull('staff_id')
            ->get();

        if ($calcomUsers->isEmpty()) {
            $this->info('  ✓ All users already mapped or no users found');
            return;
        }

        $this->info("  Found {$calcomUsers->count()} unmapped users");
        $bar = $this->output->createProgressBar($calcomUsers->count());
        $bar->start();

        foreach ($calcomUsers as $calcomUser) {
            $this->mapUser($calcomUser);
            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);
    }

    protected function mapUser($calcomUser): void
    {
        // Try to find matching staff member
        $candidates = $this->findStaffCandidates($calcomUser);

        if ($candidates->isEmpty()) {
            $this->mappingStats['no_match']++;
            return;
        }

        $bestMatch = $candidates->first();
        $confidence = $this->calculateUserMatchConfidence($calcomUser, $bestMatch);

        if ($confidence >= $this->threshold) {
            if ($this->autoApply || $this->confirmMapping(
                "Cal.com User: {$calcomUser->name} ({$calcomUser->email})",
                "Staff: {$bestMatch->name} ({$bestMatch->email})",
                $confidence
            )) {
                DB::table('calcom_users')
                    ->where('id', $calcomUser->id)
                    ->update([
                        'staff_id' => $bestMatch->id,
                        'company_id' => $bestMatch->company_id,
                        'updated_at' => now()
                    ]);

                $this->mappingStats['users_mapped']++;
            }
        } else if ($candidates->count() > 1) {
            $this->mappingStats['ambiguous']++;
            
            if (!$this->autoApply) {
                $this->handleAmbiguousMapping($calcomUser, $candidates, 'staff');
            }
        } else {
            $this->mappingStats['no_match']++;
        }
    }

    protected function mapEventTypes(): void
    {
        $this->info('📅 Mapping Cal.com Event Types to Services...');
        $this->newLine();

        $eventTypes = CalcomEventType::whereNull('service_id')->get();

        if ($eventTypes->isEmpty()) {
            $this->info('  ✓ All event types already mapped or none found');
            return;
        }

        $this->info("  Found {$eventTypes->count()} unmapped event types");
        $bar = $this->output->createProgressBar($eventTypes->count());
        $bar->start();

        foreach ($eventTypes as $eventType) {
            $this->mapEventType($eventType);
            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);
    }

    protected function mapEventType(CalcomEventType $eventType): void
    {
        // Try to find matching service
        $candidates = $this->findServiceCandidates($eventType);

        if ($candidates->isEmpty()) {
            // Optionally create new service
            if (!$this->autoApply && $this->confirm("No matching service found for '{$eventType->name}'. Create new service?")) {
                $service = $this->createServiceFromEventType($eventType);
                $eventType->update(['service_id' => $service->id]);
                $this->mappingStats['event_types_mapped']++;
            } else {
                $this->mappingStats['no_match']++;
            }
            return;
        }

        $bestMatch = $candidates->first();
        $confidence = $this->calculateServiceMatchConfidence($eventType, $bestMatch);

        if ($confidence >= $this->threshold) {
            if ($this->autoApply || $this->confirmMapping(
                "Event Type: {$eventType->name} ({$eventType->duration_minutes} min)",
                "Service: {$bestMatch->name} ({$bestMatch->duration_minutes} min)",
                $confidence
            )) {
                $eventType->update(['service_id' => $bestMatch->id]);
                $this->mappingStats['event_types_mapped']++;
            }
        } else {
            $this->mappingStats['no_match']++;
        }
    }

    protected function mapTeams(): void
    {
        $this->info('👥 Mapping Cal.com Teams to Companies/Branches...');
        $this->newLine();

        $teams = DB::table('calcom_teams')
            ->whereNull('company_id')
            ->get();

        if ($teams->isEmpty()) {
            $this->info('  ✓ All teams already mapped or none found');
            return;
        }

        $this->info("  Found {$teams->count()} unmapped teams");
        $bar = $this->output->createProgressBar($teams->count());
        $bar->start();

        foreach ($teams as $team) {
            $this->mapTeam($team);
            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);
    }

    protected function mapTeam($team): void
    {
        // Try to find matching company
        $candidates = $this->findCompanyCandidates($team);

        if ($candidates->isEmpty()) {
            // Use default company
            $defaultCompany = Company::first();
            if ($defaultCompany) {
                DB::table('calcom_teams')
                    ->where('id', $team->id)
                    ->update([
                        'company_id' => $defaultCompany->id,
                        'updated_at' => now()
                    ]);
                $this->mappingStats['teams_mapped']++;
            } else {
                $this->mappingStats['no_match']++;
            }
            return;
        }

        $bestMatch = $candidates->first();
        $confidence = $this->calculateTeamMatchConfidence($team, $bestMatch);

        if ($confidence >= $this->threshold) {
            if ($this->autoApply || $this->confirmMapping(
                "Team: {$team->name}",
                "Company: {$bestMatch->name}",
                $confidence
            )) {
                // Also try to find matching branch
                $branch = $this->findBranchForTeam($team, $bestMatch);
                
                DB::table('calcom_teams')
                    ->where('id', $team->id)
                    ->update([
                        'company_id' => $bestMatch->id,
                        'branch_id' => $branch?->id,
                        'updated_at' => now()
                    ]);

                $this->mappingStats['teams_mapped']++;
            }
        } else {
            $this->mappingStats['no_match']++;
        }
    }

    protected function findStaffCandidates($calcomUser)
    {
        $query = Staff::query();

        // Exact email match
        if ($calcomUser->email) {
            $exactMatch = $query->where('email', $calcomUser->email)->first();
            if ($exactMatch) {
                return collect([$exactMatch]);
            }
        }

        // Name similarity
        $candidates = Staff::all()->filter(function ($staff) use ($calcomUser) {
            $nameSimilarity = similar_text(
                strtolower($calcomUser->name),
                strtolower($staff->name),
                $percent
            );
            return $percent > 60;
        });

        return $candidates->sortByDesc(function ($staff) use ($calcomUser) {
            return $this->calculateUserMatchConfidence($calcomUser, $staff);
        });
    }

    protected function findServiceCandidates(CalcomEventType $eventType)
    {
        $query = Service::query();

        // Check for exact name match
        $exactMatch = $query->where('name', $eventType->name)->first();
        if ($exactMatch) {
            return collect([$exactMatch]);
        }

        // Find by similar name and duration
        $candidates = Service::all()->filter(function ($service) use ($eventType) {
            $nameSimilarity = similar_text(
                strtolower($eventType->name),
                strtolower($service->name),
                $percent
            );
            
            $durationMatch = abs($service->duration_minutes - $eventType->duration_minutes) <= 15;
            
            return $percent > 50 || $durationMatch;
        });

        return $candidates->sortByDesc(function ($service) use ($eventType) {
            return $this->calculateServiceMatchConfidence($eventType, $service);
        });
    }

    protected function findCompanyCandidates($team)
    {
        // Try exact name match
        $exactMatch = Company::where('name', $team->name)->first();
        if ($exactMatch) {
            return collect([$exactMatch]);
        }

        // Try partial match
        $candidates = Company::where('name', 'LIKE', '%' . $team->name . '%')
            ->orWhere('name', 'LIKE', '%' . Str::singular($team->name) . '%')
            ->get();

        if ($candidates->isEmpty()) {
            // Check slug similarity
            $candidates = Company::all()->filter(function ($company) use ($team) {
                similar_text(
                    strtolower($team->slug ?? ''),
                    strtolower(Str::slug($company->name)),
                    $percent
                );
                return $percent > 60;
            });
        }

        return $candidates;
    }

    protected function findBranchForTeam($team, Company $company): ?Branch
    {
        // Try to match team name to branch
        $branch = Branch::where('company_id', $company->id)
            ->where(function ($query) use ($team) {
                $query->where('name', 'LIKE', '%' . $team->name . '%')
                    ->orWhere('slug', $team->slug);
            })
            ->first();

        // Fallback to first branch of company
        if (!$branch) {
            $branch = Branch::where('company_id', $company->id)->first();
        }

        return $branch;
    }

    protected function calculateUserMatchConfidence($calcomUser, Staff $staff): float
    {
        $confidence = 0;
        $weights = [
            'email' => 40,
            'name' => 30,
            'company' => 20,
            'timezone' => 10,
        ];

        // Email match
        if ($calcomUser->email === $staff->email) {
            $confidence += $weights['email'];
        }

        // Name similarity
        similar_text(
            strtolower($calcomUser->name),
            strtolower($staff->name),
            $nameSimilarity
        );
        $confidence += ($nameSimilarity / 100) * $weights['name'];

        // Company match (if user has company_id)
        if ($calcomUser->company_id && $calcomUser->company_id === $staff->company_id) {
            $confidence += $weights['company'];
        }

        // Timezone match
        if (isset($calcomUser->timezone) && $staff->timezone === $calcomUser->timezone) {
            $confidence += $weights['timezone'];
        }

        return min(100, $confidence);
    }

    protected function calculateServiceMatchConfidence(CalcomEventType $eventType, Service $service): float
    {
        $confidence = 0;
        $weights = [
            'name' => 40,
            'duration' => 30,
            'price' => 20,
            'company' => 10,
        ];

        // Name similarity
        similar_text(
            strtolower($eventType->name),
            strtolower($service->name),
            $nameSimilarity
        );
        $confidence += ($nameSimilarity / 100) * $weights['name'];

        // Duration match (within 15 minutes)
        $durationDiff = abs($service->duration_minutes - $eventType->duration_minutes);
        if ($durationDiff == 0) {
            $confidence += $weights['duration'];
        } elseif ($durationDiff <= 15) {
            $confidence += $weights['duration'] * 0.5;
        }

        // Price similarity (if both have prices)
        if ($eventType->price && $service->price_cents) {
            $priceDiff = abs(($service->price_cents / 100) - $eventType->price);
            if ($priceDiff < 10) {
                $confidence += $weights['price'];
            } elseif ($priceDiff < 20) {
                $confidence += $weights['price'] * 0.5;
            }
        }

        // Company match
        if ($eventType->company_id && $eventType->company_id === $service->company_id) {
            $confidence += $weights['company'];
        }

        return min(100, $confidence);
    }

    protected function calculateTeamMatchConfidence($team, Company $company): float
    {
        $confidence = 0;

        // Name similarity
        similar_text(
            strtolower($team->name),
            strtolower($company->name),
            $nameSimilarity
        );
        $confidence += $nameSimilarity;

        // Slug similarity
        if (isset($team->slug)) {
            similar_text(
                strtolower($team->slug),
                strtolower(Str::slug($company->name)),
                $slugSimilarity
            );
            $confidence = max($confidence, $slugSimilarity);
        }

        return min(100, $confidence);
    }

    protected function confirmMapping(string $from, string $to, float $confidence): bool
    {
        $this->newLine();
        $this->info("  Suggested Mapping (Confidence: " . round($confidence) . "%):");
        $this->line("    From: {$from}");
        $this->line("    To:   {$to}");
        
        return $this->confirm('  Apply this mapping?', $confidence >= 80);
    }

    protected function handleAmbiguousMapping($entity, $candidates, string $type): void
    {
        $this->newLine();
        $this->warn("  Multiple potential matches found:");
        $this->line("  Entity: {$entity->name}");
        
        $options = $candidates->take(5)->map(function ($candidate, $index) {
            return ($index + 1) . ". {$candidate->name}";
        })->toArray();
        
        $options[] = 'Skip';
        
        $choice = $this->choice('  Select best match:', $options, count($options) - 1);
        
        if ($choice !== 'Skip') {
            $index = (int) explode('.', $choice)[0] - 1;
            $selected = $candidates->values()[$index];
            
            switch ($type) {
                case 'staff':
                    DB::table('calcom_users')
                        ->where('id', $entity->id)
                        ->update(['staff_id' => $selected->id]);
                    $this->mappingStats['users_mapped']++;
                    break;
            }
        }
    }

    protected function createServiceFromEventType(CalcomEventType $eventType): Service
    {
        $company = Company::find($eventType->company_id) ?? Company::first();
        
        return Service::create([
            'company_id' => $company->id,
            'name' => $eventType->name,
            'description' => $eventType->description,
            'duration_minutes' => $eventType->duration_minutes,
            'price_cents' => ($eventType->price ?? 0) * 100,
            'is_active' => $eventType->is_active,
            'buffer_time_before' => $eventType->buffer_before,
            'buffer_time_after' => $eventType->buffer_after,
            'max_advance_days' => $eventType->booking_future_limit,
            'min_advance_minutes' => $eventType->minimum_booking_notice,
        ]);
    }

    protected function displayMappingSummary(): void
    {
        $this->newLine();
        $this->info('═══════════════════════════════════════════════════════════════');
        $this->info('  Mapping Summary');
        $this->info('═══════════════════════════════════════════════════════════════');
        $this->info('');
        $this->info('  📊 Results:');
        $this->info('     • Users Mapped:       ' . $this->mappingStats['users_mapped']);
        $this->info('     • Event Types Mapped: ' . $this->mappingStats['event_types_mapped']);
        $this->info('     • Teams Mapped:       ' . $this->mappingStats['teams_mapped']);
        $this->info('     • Ambiguous Matches:  ' . $this->mappingStats['ambiguous']);
        $this->info('     • No Matches Found:   ' . $this->mappingStats['no_match']);
        $this->info('');
        
        $total = array_sum(array_slice($this->mappingStats, 0, 3));
        if ($total > 0) {
            $this->info('  ✅ Successfully mapped ' . $total . ' entities!');
        } else {
            $this->warn('  ⚠️  No new mappings created.');
        }
        
        $this->info('═══════════════════════════════════════════════════════════════');
    }
}
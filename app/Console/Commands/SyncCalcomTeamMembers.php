<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Company;
use App\Models\CalcomTeamMember;
use App\Services\CalcomV2Service;
use App\Services\CalcomHostMappingService;
use Illuminate\Support\Facades\Log;

class SyncCalcomTeamMembers extends Command
{
    protected $signature = 'calcom:sync-team-members {--company=}';
    protected $description = 'Sync Cal.com team members to local database and link with staff';

    public function handle(): int
    {
        $this->info('🔄 Starting Cal.com Team Member Sync...');

        try {
            $calcomService = app(CalcomV2Service::class);
            $hostMappingService = app(CalcomHostMappingService::class);

            // Get companies to sync
            $companies = $this->getCompaniesToSync();

            if ($companies->isEmpty()) {
                $this->warn('⚠️  No companies with Cal.com team ID found');
                return self::SUCCESS;
            }

            $totalMembers = 0;
            $totalLinked = 0;

            foreach ($companies as $company) {
                $this->line("\n📌 Processing: {$company->name} (Team ID: {$company->calcom_team_id})");

                try {
                    // Fetch team members from Cal.com
                    $response = $calcomService->fetchTeamMembers($company->calcom_team_id);

                    if (!$response->successful()) {
                        $this->error("  ❌ Failed to fetch team members: {$response->status()}");
                        Log::error('[CalcomTeamMemberSync] API error', [
                            'company_id' => $company->id,
                            'team_id' => $company->calcom_team_id,
                            'status' => $response->status(),
                        ]);
                        continue;
                    }

                    $members = $response->json()['members'] ?? [];

                    if (empty($members)) {
                        $this->warn("  ⚠️  No team members found");
                        continue;
                    }

                    $this->line("  Found: " . count($members) . " members");

                    // Sync each member
                    foreach ($members as $member) {
                        try {
                            $teamMember = CalcomTeamMember::updateOrCreate(
                                [
                                    'company_id' => $company->id,
                                    'calcom_team_id' => $company->calcom_team_id,
                                    'calcom_user_id' => $member['userId'],
                                ],
                                [
                                    'email' => $member['email'],
                                    'name' => $member['name'],
                                    'username' => $member['username'] ?? null,
                                    'role' => $member['role'] ?? 'member',
                                    'accepted' => $member['accepted'] ?? true,
                                    'is_active' => true,
                                    'last_synced_at' => now(),
                                ]
                            );

                            $totalMembers++;
                            $this->line("  ✅ {$member['name']} ({$member['email']})");

                            // Try to link with local staff
                            $linked = $hostMappingService->linkStaffToTeamMember($company->id, $member);
                            if ($linked) {
                                $totalLinked++;
                                $this->line("     → Linked to Staff Member");
                            }

                        } catch (\Exception $e) {
                            $this->error("  ❌ Error syncing member {$member['email']}: {$e->getMessage()}");
                            Log::error('[CalcomTeamMemberSync] Member sync error', [
                                'company_id' => $company->id,
                                'member' => $member,
                                'error' => $e->getMessage(),
                            ]);
                        }
                    }

                } catch (\Exception $e) {
                    $this->error("  ❌ Error processing company: {$e->getMessage()}");
                    Log::error('[CalcomTeamMemberSync] Company sync error', [
                        'company_id' => $company->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            $this->info("\n✅ Sync Complete!");
            $this->info("📊 Summary:");
            $this->info("   • Total members synced: {$totalMembers}");
            $this->info("   • Staff linked: {$totalLinked}");

            return self::SUCCESS;

        } catch (\Exception $e) {
            $this->error("❌ Fatal error: {$e->getMessage()}");
            Log::error('[CalcomTeamMemberSync] Fatal error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return self::FAILURE;
        }
    }

    protected function getCompaniesToSync()
    {
        if ($companyId = $this->option('company')) {
            return Company::where('id', $companyId)
                ->whereNotNull('calcom_team_id')
                ->get();
        }

        return Company::whereNotNull('calcom_team_id')
            ->where('deleted_at', null)
            ->get();
    }
}

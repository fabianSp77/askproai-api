<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\GitHubNotionIntegrationService;

class GitHubNotionCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'github:notion 
                            {action : Action to perform (sync-issues, sync-prs, sync-releases, status, configure)}
                            {--owner= : GitHub repository owner}
                            {--repo= : GitHub repository name}
                            {--database= : Notion database ID}
                            {--parent= : Notion parent page ID}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Manage GitHub-Notion integration';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $integration = app(GitHubNotionIntegrationService::class);
        $action = $this->argument('action');

        try {
            switch ($action) {
                case 'sync-issues':
                    return $this->syncIssues($integration);
                
                case 'sync-prs':
                    return $this->syncPRs($integration);
                
                case 'sync-releases':
                    return $this->syncReleases($integration);
                
                case 'status':
                    return $this->showStatus($integration);
                
                case 'configure':
                    return $this->configureIntegration($integration);
                
                default:
                    $this->error("Unknown action: {$action}");
                    $this->info('Available actions:');
                    $this->line(' - sync-issues   : Sync GitHub issues to Notion tasks');
                    $this->line(' - sync-prs      : Sync GitHub PRs to Notion');
                    $this->line(' - sync-releases : Sync GitHub releases to Notion docs');
                    $this->line(' - status        : Show sync status');
                    $this->line(' - configure     : Configure repository sync');
                    return 1;
            }
        } catch (\Exception $e) {
            $this->error('Error: ' . $e->getMessage());
            return 1;
        }
    }

    protected function syncIssues(GitHubNotionIntegrationService $integration): int
    {
        $owner = $this->option('owner');
        $repo = $this->option('repo');
        $database = $this->option('database');

        if (!$owner || !$repo || !$database) {
            $this->error('Required options: --owner, --repo, --database');
            $this->line('Example: php artisan github:notion sync-issues --owner=laravel --repo=framework --database=notion_db_id');
            return 1;
        }

        $this->info("Syncing issues from {$owner}/{$repo}...");
        
        $result = $integration->syncIssuesToTasks($owner, $repo, $database);
        
        if ($result['success']) {
            $this->info("✅ Successfully synced {$result['synced']}/{$result['total']} issues");
            
            if (!empty($result['errors'])) {
                $this->newLine();
                $this->warn('Some issues failed:');
                foreach ($result['errors'] as $error) {
                    $this->line(" - {$error}");
                }
            }
            
            return 0;
        }
        
        $this->error("❌ Sync failed: " . $result['error']);
        return 1;
    }

    protected function syncPRs(GitHubNotionIntegrationService $integration): int
    {
        $owner = $this->option('owner');
        $repo = $this->option('repo');
        $database = $this->option('database');

        if (!$owner || !$repo || !$database) {
            $this->error('Required options: --owner, --repo, --database');
            return 1;
        }

        $this->info("Syncing pull requests from {$owner}/{$repo}...");
        
        $result = $integration->syncPRsToReviews($owner, $repo, $database);
        
        if ($result['success']) {
            $this->info("✅ Successfully synced {$result['synced']}/{$result['total']} pull requests");
            
            if (!empty($result['errors'])) {
                $this->newLine();
                $this->warn('Some PRs failed:');
                foreach ($result['errors'] as $error) {
                    $this->line(" - {$error}");
                }
            }
            
            return 0;
        }
        
        $this->error("❌ Sync failed: " . $result['error']);
        return 1;
    }

    protected function syncReleases(GitHubNotionIntegrationService $integration): int
    {
        $owner = $this->option('owner');
        $repo = $this->option('repo');
        $parent = $this->option('parent');

        if (!$owner || !$repo || !$parent) {
            $this->error('Required options: --owner, --repo, --parent');
            return 1;
        }

        $this->info("Syncing releases from {$owner}/{$repo}...");
        
        $result = $integration->syncReleasesToDocs($owner, $repo, $parent);
        
        if ($result['success']) {
            $this->info("✅ Successfully synced {$result['synced']}/{$result['total']} releases");
            return 0;
        }
        
        $this->error("❌ Sync failed: " . $result['error']);
        return 1;
    }

    protected function showStatus(GitHubNotionIntegrationService $integration): int
    {
        $this->info("📊 GitHub-Notion Sync Status");
        $this->newLine();
        
        $result = $integration->getSyncStatus();
        
        if (!$result['success']) {
            $this->error($result['error']);
            return 1;
        }
        
        if (empty($result['syncs'])) {
            $this->warn('No sync history found.');
            $this->line('Run a sync command to get started.');
            return 0;
        }
        
        $headers = ['Type', 'Repository', 'Synced', 'Total', 'Errors', 'Time'];
        $rows = [];
        
        foreach (array_slice($result['syncs'], 0, 10) as $sync) {
            $rows[] = [
                $sync['type'] ?? 'unknown',
                $sync['repo'] ?? 'N/A',
                $sync['synced'] ?? 0,
                $sync['total'] ?? 0,
                count($sync['errors'] ?? []),
                $sync['timestamp'] ?? 'N/A'
            ];
        }
        
        $this->table($headers, $rows);
        
        if (count($result['syncs']) > 10) {
            $this->newLine();
            $this->line('Showing 10 most recent syncs out of ' . count($result['syncs']) . ' total.');
        }
        
        return 0;
    }

    protected function configureIntegration(GitHubNotionIntegrationService $integration): int
    {
        $this->info('🔧 GitHub-Notion Integration Configuration');
        $this->newLine();
        
        $owner = $this->ask('GitHub repository owner');
        $repo = $this->ask('GitHub repository name');
        
        $this->info("Configuring integration for {$owner}/{$repo}");
        $this->newLine();
        
        $mappings = [
            'repository' => "{$owner}/{$repo}"
        ];
        
        if ($this->confirm('Sync GitHub issues to Notion tasks?')) {
            $mappings['issues_database'] = $this->ask('Notion database ID for issues');
        }
        
        if ($this->confirm('Sync GitHub PRs to Notion?')) {
            $mappings['prs_database'] = $this->ask('Notion database ID for pull requests');
        }
        
        if ($this->confirm('Sync GitHub releases to Notion docs?')) {
            $mappings['releases_parent'] = $this->ask('Notion parent page ID for releases');
        }
        
        // Save configuration
        $integration->configureMappings($mappings);
        
        $this->newLine();
        $this->info('✅ Configuration saved!');
        $this->newLine();
        $this->line('You can now run sync commands:');
        
        if (isset($mappings['issues_database'])) {
            $this->line("  php artisan github:notion sync-issues --owner={$owner} --repo={$repo} --database={$mappings['issues_database']}");
        }
        
        if (isset($mappings['prs_database'])) {
            $this->line("  php artisan github:notion sync-prs --owner={$owner} --repo={$repo} --database={$mappings['prs_database']}");
        }
        
        if (isset($mappings['releases_parent'])) {
            $this->line("  php artisan github:notion sync-releases --owner={$owner} --repo={$repo} --parent={$mappings['releases_parent']}");
        }
        
        return 0;
    }
}
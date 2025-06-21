<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\MCP\Discovery\MCPDiscoveryService;

class MCPDiscoverCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mcp:discover
                            {--source= : Specific source to check (anthropic, github, npm, community)}
                            {--install : Automatically install highly relevant MCPs}
                            {--dry-run : Show what would be discovered without saving}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Discover new MCPs from various sources and evaluate their relevance';

    /**
     * Execute the console command.
     */
    public function handle(MCPDiscoveryService $discoveryService): int
    {
        $this->info('🔍 Starting MCP Discovery...');
        
        $startTime = microtime(true);
        
        try {
            // Run discovery
            $discoveries = $discoveryService->discoverNewMCPs();
            
            if ($this->option('dry-run')) {
                $this->info('🔬 Dry run mode - no changes will be saved');
            }
            
            // Display results
            $this->displayDiscoveries($discoveries);
            
            // Show catalog statistics
            $catalog = $discoveryService->getCatalog();
            $this->displayStatistics($catalog['statistics'] ?? []);
            
            // Install if requested
            if ($this->option('install') && !$this->option('dry-run')) {
                $this->installHighlyRelevantMCPs($discoveries);
            }
            
            $duration = round(microtime(true) - $startTime, 2);
            $this->info("✅ Discovery completed in {$duration} seconds");
            
            return Command::SUCCESS;
            
        } catch (\Exception $e) {
            $this->error('❌ Discovery failed: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
    
    /**
     * Display discovered MCPs
     */
    protected function displayDiscoveries(array $discoveries): void
    {
        if (empty($discoveries)) {
            $this->info('No new MCPs discovered.');
            return;
        }
        
        $this->info(sprintf('Found %d relevant MCPs:', count($discoveries)));
        $this->newLine();
        
        $headers = ['Name', 'Source', 'Relevance', 'Categories', 'Reasons'];
        $rows = [];
        
        foreach ($discoveries as $mcp) {
            $relevance = round(($mcp['relevance_score'] ?? 0) * 100) . '%';
            $categories = implode(', ', array_slice($mcp['categories'] ?? [], 0, 3));
            $reasons = implode("\n", array_slice($mcp['relevance_reasons'] ?? [], 0, 2));
            
            $rows[] = [
                $mcp['name'],
                $mcp['source'],
                $relevance,
                $categories,
                $reasons
            ];
        }
        
        $this->table($headers, $rows);
    }
    
    /**
     * Display catalog statistics
     */
    protected function displayStatistics(array $statistics): void
    {
        $this->newLine();
        $this->info('📊 Catalog Statistics:');
        
        // By category
        if (!empty($statistics['by_category'])) {
            $this->info('Top Categories:');
            foreach (array_slice($statistics['by_category'], 0, 5) as $category => $count) {
                $this->line("  - {$category}: {$count} MCPs");
            }
        }
        
        // By relevance
        if (!empty($statistics['by_relevance'])) {
            $this->newLine();
            $this->info('Relevance Distribution:');
            $this->line("  - High: {$statistics['by_relevance']['high']} MCPs");
            $this->line("  - Medium: {$statistics['by_relevance']['medium']} MCPs");
            $this->line("  - Low: {$statistics['by_relevance']['low']} MCPs");
        }
        
        // New discoveries
        if (isset($statistics['new_this_week'])) {
            $this->newLine();
            $this->info("New this week: {$statistics['new_this_week']} MCPs");
        }
    }
    
    /**
     * Install highly relevant MCPs
     */
    protected function installHighlyRelevantMCPs(array $discoveries): void
    {
        $highRelevance = array_filter($discoveries, function ($mcp) {
            return ($mcp['relevance_score'] ?? 0) >= 0.8;
        });
        
        if (empty($highRelevance)) {
            $this->info('No MCPs with high enough relevance for auto-installation.');
            return;
        }
        
        $this->info(sprintf('Found %d MCPs with high relevance for installation:', count($highRelevance)));
        
        foreach ($highRelevance as $mcp) {
            if ($this->confirm("Install {$mcp['name']}?")) {
                $this->installMCP($mcp);
            }
        }
    }
    
    /**
     * Install a specific MCP
     */
    protected function installMCP(array $mcp): void
    {
        $this->info("Installing {$mcp['name']}...");
        
        // Implementation would depend on MCP type and source
        // This is a placeholder for the actual installation logic
        
        $this->warn('MCP installation not yet implemented');
    }
}
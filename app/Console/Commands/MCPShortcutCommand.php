<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\MCP\MCPOrchestrator;
use App\Services\MCPAutoDiscoveryService;
use App\Services\MemoryBankAutomationService;
use App\Services\GitHubNotionIntegrationService;
use Illuminate\Support\Facades\Cache;

class MCPShortcutCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mcp {action} {--server=} {--tool=} {--params=} {--discover} {--list-all}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Quick MCP server shortcuts for common operations';

    protected MCPOrchestrator $orchestrator;
    protected MCPAutoDiscoveryService $discovery;
    protected MemoryBankAutomationService $memory;

    /**
     * Execute the console command.
     */
    public function handle(
        MCPOrchestrator $orchestrator,
        MCPAutoDiscoveryService $discovery,
        MemoryBankAutomationService $memory
    ): int {
        $this->orchestrator = $orchestrator;
        $this->discovery = $discovery;
        $this->memory = $memory;
        
        $action = $this->argument('action');
        
        // Check for configured shortcuts first
        $shortcuts = config('mcp-shortcuts.shortcuts', []);
        $aliases = config('mcp-shortcuts.aliases', []);
        
        // Resolve alias if exists
        if (isset($aliases[$action])) {
            $action = $aliases[$action];
        }
        
        // Handle configured shortcuts
        if (isset($shortcuts[$action])) {
            return $this->handleConfiguredShortcut($action, $shortcuts[$action]);
        }
        
        // Handle built-in shortcuts
        switch ($action) {
            // Quick appointment booking
            case 'book':
            case 'appointment':
                return $this->handleAppointmentBooking();
                
            // Quick call import
            case 'calls':
            case 'import-calls':
                return $this->handleCallImport();
                
            // Quick customer lookup
            case 'customer':
            case 'find-customer':
                return $this->handleCustomerLookup();
                
            // Quick sync operations
            case 'sync':
                return $this->handleSync();
                
            // Memory operations
            case 'remember':
            case 'recall':
                return $this->handleMemory($action);
                
            // GitHub-Notion sync
            case 'gh-notion':
                return $this->handleGitHubNotionSync();
                
            // Execute arbitrary MCP command
            case 'exec':
            case 'run':
                return $this->handleExecute();
                
            // List available shortcuts
            case 'list':
            case 'help':
                return $this->showShortcuts();
                
            // Discover best server for task
            case 'discover':
                return $this->handleDiscovery();
                
            default:
                $this->error("Unknown action: {$action}");
                $this->info('Run "php artisan mcp list" to see available shortcuts');
                return 1;
        }
    }
    
    /**
     * Handle configured shortcut from config file
     */
    protected function handleConfiguredShortcut(string $name, array $config): int
    {
        $description = $config['description'] ?? $name;
        $this->info("🚀 {$description}");
        
        // Handle command shortcuts
        if (isset($config['command'])) {
            $this->info("Executing: {$config['command']}");
            $this->call($config['command']);
            return 0;
        }
        
        // Handle multi-step shortcuts
        if (isset($config['multi'])) {
            foreach ($config['multi'] as $step) {
                $result = $this->orchestrator->execute(
                    $step['server'],
                    $step['tool'],
                    $step['params'] ?? []
                );
                
                if (!($result['success'] ?? false)) {
                    $this->error("Step failed: {$step['server']}::{$step['tool']}");
                    return 1;
                }
            }
            $this->info('✅ All steps completed successfully!');
            return 0;
        }
        
        // Handle single MCP shortcuts
        $server = $config['server'] ?? null;
        $tool = $config['tool'] ?? null;
        
        if (!$server || !$tool) {
            $this->error('Invalid shortcut configuration');
            return 1;
        }
        
        // Collect parameters
        $params = $config['defaults'] ?? [];
        $globalDefaults = config('mcp-shortcuts.defaults', []);
        $params = array_merge($globalDefaults, $params);
        
        // Handle prompts
        if (isset($config['prompts'])) {
            foreach ($config['prompts'] as $key => $prompt) {
                $default = $params[$key] ?? null;
                $value = $this->ask($prompt, $default);
                if ($value !== null) {
                    $params[$key] = $value;
                }
            }
        }
        
        // Handle special query parameter
        if (isset($config['query'])) {
            $params['query'] = $config['query'];
        }
        
        // Execute
        try {
            $result = $this->orchestrator->execute($server, $tool, $params);
            
            if ($result['success'] ?? false) {
                $this->info('✅ ' . ($result['message'] ?? 'Operation completed successfully!'));
                
                // Display results if available
                if (!empty($result['data'])) {
                    $this->line(json_encode($result['data'], JSON_PRETTY_PRINT));
                }
            } else {
                $this->error('Operation failed: ' . ($result['error'] ?? 'Unknown error'));
            }
            
            return 0;
        } catch (\Exception $e) {
            $this->error('Error: ' . $e->getMessage());
            return 1;
        }
    }
    
    /**
     * Handle appointment booking shortcut
     */
    protected function handleAppointmentBooking(): int
    {
        $this->info('🗓️ Quick Appointment Booking');
        
        $customerPhone = $this->ask('Customer phone number');
        $service = $this->ask('Service type');
        $date = $this->ask('Preferred date (YYYY-MM-DD)', now()->addDay()->format('Y-m-d'));
        $time = $this->ask('Preferred time (HH:MM)', '14:00');
        
        $this->info('Booking appointment...');
        
        try {
            $result = $this->orchestrator->execute('appointment', 'create_appointment', [
                'customer_phone' => $customerPhone,
                'service' => $service,
                'date' => $date,
                'time' => $time,
                'source' => 'mcp_shortcut'
            ]);
            
            if ($result['success'] ?? false) {
                $this->info('✅ Appointment booked successfully!');
                $this->table(
                    ['Field', 'Value'],
                    [
                        ['Appointment ID', $result['data']['appointment_id'] ?? 'N/A'],
                        ['Customer', $result['data']['customer_name'] ?? $customerPhone],
                        ['Date/Time', "{$date} {$time}"],
                        ['Service', $service],
                        ['Status', $result['data']['status'] ?? 'scheduled'],
                    ]
                );
            } else {
                $this->error('Failed to book appointment: ' . ($result['error'] ?? 'Unknown error'));
            }
            
            return 0;
        } catch (\Exception $e) {
            $this->error('Error: ' . $e->getMessage());
            return 1;
        }
    }
    
    /**
     * Handle call import shortcut
     */
    protected function handleCallImport(): int
    {
        $this->info('📞 Importing Recent Calls');
        
        $limit = $this->ask('How many calls to import?', '50');
        
        $this->info('Fetching calls from Retell.ai...');
        
        try {
            $result = $this->orchestrator->execute('retell', 'fetch_calls', [
                'limit' => (int) $limit,
                'order' => 'desc'
            ]);
            
            if ($result['success'] ?? false) {
                $count = $result['data']['imported_count'] ?? 0;
                $this->info("✅ Successfully imported {$count} calls!");
                
                if (!empty($result['data']['calls'])) {
                    $this->info('Recent calls:');
                    foreach (array_slice($result['data']['calls'], 0, 5) as $call) {
                        $this->line("  - {$call['from_number']} → {$call['to_number']} ({$call['duration']}s)");
                    }
                }
            } else {
                $this->error('Failed to import calls: ' . ($result['error'] ?? 'Unknown error'));
            }
            
            return 0;
        } catch (\Exception $e) {
            $this->error('Error: ' . $e->getMessage());
            return 1;
        }
    }
    
    /**
     * Handle customer lookup shortcut
     */
    protected function handleCustomerLookup(): int
    {
        $this->info('👤 Customer Lookup');
        
        $search = $this->ask('Enter phone number or name');
        
        $this->info('Searching...');
        
        try {
            $result = $this->orchestrator->execute('customer', 'search_customers', [
                'query' => $search,
                'limit' => 10
            ]);
            
            if ($result['success'] ?? false && !empty($result['data']['customers'])) {
                $this->info('Found customers:');
                
                $customers = collect($result['data']['customers'])->map(function ($customer) {
                    return [
                        $customer['id'] ?? 'N/A',
                        $customer['name'] ?? 'N/A',
                        $customer['phone'] ?? 'N/A',
                        $customer['email'] ?? 'N/A',
                        $customer['appointment_count'] ?? 0,
                    ];
                })->toArray();
                
                $this->table(
                    ['ID', 'Name', 'Phone', 'Email', 'Appointments'],
                    $customers
                );
            } else {
                $this->warn('No customers found matching: ' . $search);
            }
            
            return 0;
        } catch (\Exception $e) {
            $this->error('Error: ' . $e->getMessage());
            return 1;
        }
    }
    
    /**
     * Handle sync operations
     */
    protected function handleSync(): int
    {
        $type = $this->choice(
            'What would you like to sync?',
            ['cal.com', 'retell.ai', 'github-notion', 'all'],
            0
        );
        
        $this->info("Syncing {$type}...");
        
        try {
            switch ($type) {
                case 'cal.com':
                    $this->call('calcom:sync');
                    break;
                    
                case 'retell.ai':
                    $this->call('retell:fetch-calls', ['--limit' => 100]);
                    break;
                    
                case 'github-notion':
                    $this->call('github:notion', ['action' => 'sync-issues']);
                    break;
                    
                case 'all':
                    $this->call('calcom:sync');
                    $this->call('retell:fetch-calls', ['--limit' => 100]);
                    $this->call('github:notion', ['action' => 'sync-issues']);
                    break;
            }
            
            $this->info('✅ Sync completed!');
            return 0;
            
        } catch (\Exception $e) {
            $this->error('Sync failed: ' . $e->getMessage());
            return 1;
        }
    }
    
    /**
     * Handle memory operations
     */
    protected function handleMemory(string $action): int
    {
        if ($action === 'remember') {
            $this->info('💾 Save to Memory Bank');
            
            $type = $this->choice('Memory type', ['note', 'task', 'context', 'reminder'], 0);
            $content = $this->ask('What should I remember?');
            $tags = $this->ask('Tags (comma-separated)', '');
            
            try {
                $result = $this->memory->remember(
                    "{$type}_{$content}",
                    [
                        'type' => $type,
                        'content' => $content,
                        'tags' => array_map('trim', explode(',', $tags)),
                        'source' => 'mcp_shortcut',
                        'timestamp' => now()->toDateTimeString()
                    ],
                    'user_memory',
                    array_map('trim', explode(',', $tags))
                );
                
                $this->info('✅ Saved to memory!');
                $this->line("Memory ID: {$result['id']}");
                
                return 0;
            } catch (\Exception $e) {
                $this->error('Failed to save: ' . $e->getMessage());
                return 1;
            }
        } else { // recall
            $this->info('🔍 Search Memory Bank');
            
            $query = $this->ask('Search query');
            $type = $this->ask('Memory type (leave empty for all)', '');
            
            try {
                $results = $this->memory->search($query, $type ?: null);
                
                if (!empty($results['data']['results'])) {
                    $this->info('Found memories:');
                    
                    foreach ($results['data']['results'] as $memory) {
                        $data = $memory['value']['data'] ?? $memory['value'];
                        $this->line('');
                        $type = $data['type'] ?? 'memory';
                        $content = $data['content'] ?? 'N/A';
                        $tags = implode(', ', $data['tags'] ?? []);
                        $timestamp = $memory['value']['timestamp'] ?? 'N/A';
                        
                        $this->line("📌 {$type}: {$content}");
                        $this->line("   Tags: {$tags}");
                        $this->line("   Created: {$timestamp}");
                    }
                } else {
                    $this->warn('No memories found for: ' . $query);
                }
                
                return 0;
            } catch (\Exception $e) {
                $this->error('Search failed: ' . $e->getMessage());
                return 1;
            }
        }
    }
    
    /**
     * Handle GitHub-Notion sync
     */
    protected function handleGitHubNotionSync(): int
    {
        $this->info('🔄 GitHub-Notion Integration');
        
        $action = $this->choice(
            'What would you like to sync?',
            ['issues', 'pull-requests', 'releases', 'everything'],
            0
        );
        
        $this->info("Syncing GitHub {$action} to Notion...");
        
        try {
            $integration = app(GitHubNotionIntegrationService::class);
            
            switch ($action) {
                case 'issues':
                    $this->call('github:notion', ['action' => 'sync-issues']);
                    break;
                    
                case 'pull-requests':
                    $this->call('github:notion', ['action' => 'sync-prs']);
                    break;
                    
                case 'releases':
                    $this->call('github:notion', ['action' => 'sync-releases']);
                    break;
                    
                case 'everything':
                    $this->call('github:notion', ['action' => 'sync-issues']);
                    $this->call('github:notion', ['action' => 'sync-prs']);
                    $this->call('github:notion', ['action' => 'sync-releases']);
                    break;
            }
            
            $this->info('✅ GitHub-Notion sync completed!');
            return 0;
            
        } catch (\Exception $e) {
            $this->error('Sync failed: ' . $e->getMessage());
            return 1;
        }
    }
    
    /**
     * Handle arbitrary MCP execution
     */
    protected function handleExecute(): int
    {
        $server = $this->option('server') ?: $this->ask('Server name');
        $tool = $this->option('tool') ?: $this->ask('Tool name');
        $params = $this->option('params');
        
        if ($params) {
            $params = json_decode($params, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                $this->error('Invalid JSON parameters');
                return 1;
            }
        } else {
            $this->info('Enter parameters (JSON format):');
            $params = json_decode($this->ask('Parameters', '{}'), true);
        }
        
        $this->info("Executing {$server}::{$tool}...");
        
        try {
            $result = $this->orchestrator->execute($server, $tool, $params);
            
            if ($result['success'] ?? false) {
                $this->info('✅ Execution successful!');
                $this->line('Result:');
                $this->line(json_encode($result['data'] ?? $result, JSON_PRETTY_PRINT));
            } else {
                $this->error('Execution failed: ' . ($result['error'] ?? 'Unknown error'));
            }
            
            return 0;
        } catch (\Exception $e) {
            $this->error('Error: ' . $e->getMessage());
            return 1;
        }
    }
    
    /**
     * Handle task discovery
     */
    protected function handleDiscovery(): int
    {
        $this->info('🔍 MCP Server Discovery');
        
        $task = $this->ask('Describe what you want to do');
        
        $this->info('Analyzing task...');
        
        try {
            $discovery = $this->discovery->discoverBestServer($task);
            
            if ($discovery['success']) {
                $this->info("✅ Best server: {$discovery['server']} (confidence: {$discovery['confidence']})");
                
                if (!empty($discovery['capabilities'])) {
                    $this->info('Available tools:');
                    foreach ($discovery['capabilities'] as $tool) {
                        $this->line("  - {$tool}");
                    }
                }
                
                if (!empty($discovery['alternatives'])) {
                    $this->info('Alternative servers: ' . implode(', ', $discovery['alternatives']));
                }
                
                if ($this->confirm('Execute this task?')) {
                    $params = [];
                    if ($this->confirm('Do you need to provide parameters?')) {
                        $params = json_decode($this->ask('Parameters (JSON)', '{}'), true);
                    }
                    
                    $result = $this->discovery->executeTask($task, $params);
                    
                    if ($result['success']) {
                        $this->info('✅ Task executed successfully!');
                        $this->line(json_encode($result['result'] ?? [], JSON_PRETTY_PRINT));
                    } else {
                        $this->error('Task failed: ' . $result['error']);
                    }
                }
            } else {
                $this->error('No suitable server found for: ' . $task);
                
                $recommendations = $this->discovery->getRecommendations($task);
                if (!empty($recommendations)) {
                    $this->info('Possible servers:');
                    foreach ($recommendations as $rec) {
                        $this->line("  - {$rec['server']} ({$rec['confidence']} confidence)");
                    }
                }
            }
            
            return 0;
        } catch (\Exception $e) {
            $this->error('Discovery failed: ' . $e->getMessage());
            return 1;
        }
    }
    
    /**
     * Show available shortcuts
     */
    protected function showShortcuts(): int
    {
        $this->info('🚀 MCP Command Shortcuts');
        $this->line('');
        
        // Show configured shortcuts by group
        if ($this->option('list-all')) {
            $groups = config('mcp-shortcuts.groups', []);
            $shortcuts = config('mcp-shortcuts.shortcuts', []);
            
            foreach ($groups as $groupKey => $group) {
                $this->info($group['label'] . ':');
                
                $groupShortcuts = [];
                foreach ($group['shortcuts'] as $shortcutKey) {
                    if (isset($shortcuts[$shortcutKey])) {
                        $groupShortcuts[] = [
                            $shortcutKey,
                            $shortcuts[$shortcutKey]['description'] ?? 'No description'
                        ];
                    }
                }
                
                $this->table(['Shortcut', 'Description'], $groupShortcuts);
                $this->line('');
            }
            
            // Show aliases
            $aliases = config('mcp-shortcuts.aliases', []);
            if (!empty($aliases)) {
                $this->info('Aliases:');
                $aliasTable = [];
                foreach ($aliases as $alias => $target) {
                    $aliasTable[] = [$alias, "→ {$target}"];
                }
                $this->table(['Alias', 'Target'], $aliasTable);
            }
        } else {
            // Show built-in shortcuts
            $shortcuts = [
                ['book, appointment', 'Quick appointment booking'],
                ['calls, import-calls', 'Import recent calls from Retell.ai'],
                ['customer, find-customer', 'Search for customers'],
                ['sync', 'Sync data from external services'],
                ['remember', 'Save something to Memory Bank'],
                ['recall', 'Search Memory Bank'],
                ['gh-notion', 'Sync GitHub to Notion'],
                ['exec, run', 'Execute arbitrary MCP command'],
                ['discover', 'Find best MCP server for a task'],
                ['list, help', 'Show this help message'],
            ];
            
            $this->table(['Shortcut', 'Description'], $shortcuts);
            
            $this->line('');
            $this->info('💡 Use --list-all to see all configured shortcuts and aliases');
        }
        
        $this->line('');
        $this->info('Examples:');
        $this->line('  php artisan mcp book                    # Book an appointment');
        $this->line('  php artisan mcp calls                   # Import recent calls');
        $this->line('  php artisan mcp customer                # Search customers');
        $this->line('  php artisan mcp remember                # Save to memory');
        $this->line('  php artisan mcp discover                # Find best server for task');
        $this->line('  php artisan mcp exec --server=calcom --tool=list_calendars');
        $this->line('');
        $this->line('  php artisan mcp b                       # Alias for "book"');
        $this->line('  php artisan mcp daily-report            # Run daily report');
        $this->line('  php artisan mcp list --list-all         # Show all shortcuts');
        
        return 0;
    }
}
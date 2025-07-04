<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\MCP\MCPAutoDiscoveryService;
use App\Services\MCP\MCPGateway;

class MCPDiscover extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mcp:discover 
                            {task : Natural language description of the task}
                            {--execute : Execute the task with the discovered server}
                            {--json : Output as JSON}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Discover the best MCP server for a given task';

    protected MCPAutoDiscoveryService $discovery;
    protected MCPGateway $gateway;

    public function __construct(
        MCPAutoDiscoveryService $discovery,
        MCPGateway $gateway
    ) {
        parent::__construct();
        $this->discovery = $discovery;
        $this->gateway = $gateway;
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $task = $this->argument('task');
        
        $this->info("🔍 Discovering MCP server for: $task");
        $this->line('');

        // Discover the best server
        $result = $this->discovery->discoverForTask($task);

        if ($this->option('json')) {
            $this->line(json_encode($result, JSON_PRETTY_PRINT));
            return 0;
        }

        // Display discovery results
        $this->displayDiscoveryResults($result);

        // Execute if requested
        if ($this->option('execute')) {
            $this->executeTask($task, $result);
        }

        return 0;
    }

    /**
     * Display discovery results
     */
    protected function displayDiscoveryResults(array $result): void
    {
        // Selected server
        $this->info('Selected MCP Server:');
        $this->line("  Server: {$result['server']}");
        $this->line("  Confidence: " . ($result['confidence'] * 100) . "%");
        
        if (isset($result['auto_selected']) && $result['auto_selected']) {
            $this->line("  Reason: {$result['reason']}");
        }

        // Capabilities
        if (!empty($result['capabilities']['methods'])) {
            $this->line('');
            $this->info('Available Methods:');
            foreach ($result['capabilities']['methods'] as $method => $description) {
                $this->line("  • $method");
                if ($description) {
                    $this->line("    $description");
                }
            }
        }

        // Alternatives
        if (!empty($result['alternatives'])) {
            $this->line('');
            $this->info('Alternative Servers:');
            foreach ($result['alternatives'] as $alt) {
                $this->line("  • $alt");
            }
        }

        // Correlation ID for tracking
        $this->line('');
        $this->line("Correlation ID: {$result['correlation_id']}");
    }

    /**
     * Execute the task
     */
    protected function executeTask(string $task, array $discovery): void
    {
        $this->line('');
        $this->info('🚀 Executing task...');

        // Get parameters if needed
        $params = [];
        if ($this->confirm('Do you need to provide parameters?')) {
            $params = $this->getTaskParameters();
        }

        try {
            // Execute via discovery service
            $response = $this->discovery->executeTask($task, $params);

            $this->line('');
            $this->info('✅ Task executed successfully!');
            
            if (isset($response['result'])) {
                $this->line('');
                $this->line('Result:');
                $this->line(json_encode($response['result'], JSON_PRETTY_PRINT));
            }

        } catch (\Exception $e) {
            $this->error('❌ Task execution failed: ' . $e->getMessage());
        }
    }

    /**
     * Get task parameters interactively
     */
    protected function getTaskParameters(): array
    {
        $params = [];
        
        while (true) {
            $key = $this->ask('Parameter name (or press enter to finish)');
            
            if (empty($key)) {
                break;
            }
            
            $value = $this->ask("Value for '$key'");
            
            // Try to parse JSON values
            $jsonValue = json_decode($value, true);
            if (json_last_error() === JSON_ERROR_NONE && !is_string($jsonValue)) {
                $params[$key] = $jsonValue;
            } else {
                $params[$key] = $value;
            }
        }
        
        return $params;
    }
}
<?php

namespace App\Jobs;

use App\Models\CommandExecution;
use App\Services\MCP\MCPAutoDiscoveryService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;
use Throwable;

class ExecuteCommandJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected CommandExecution $execution;

    /**
     * Create a new job instance.
     */
    public function __construct(CommandExecution $execution)
    {
        $this->execution = $execution;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            // Mark as running
            $this->execution->markAsRunning();
            
            // Get the command template
            $command = $this->execution->commandTemplate;
            
            // Process the command template with parameters
            $finalCommand = $this->processCommandTemplate(
                $command->command_template,
                $this->execution->parameters ?? []
            );
            
            // Check if this is an MCP command
            if (str_starts_with($finalCommand, 'mcp:')) {
                $output = $this->executeMCPCommand($finalCommand);
            } else {
                $output = $this->executeShellCommand($finalCommand);
            }
            
            // Mark as completed
            $this->execution->markAsCompleted($output);
            
            Log::info('Command executed successfully', [
                'execution_id' => $this->execution->id,
                'command_id' => $command->id,
                'correlation_id' => $this->execution->correlation_id
            ]);
            
        } catch (Throwable $e) {
            // Mark as failed
            $this->execution->markAsFailed($e->getMessage());
            
            Log::error('Command execution failed', [
                'execution_id' => $this->execution->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            throw $e;
        }
    }

    /**
     * Process command template with parameters
     */
    protected function processCommandTemplate(string $template, array $parameters): string
    {
        // Replace parameter placeholders
        foreach ($parameters as $key => $value) {
            $template = str_replace("{{{$key}}}", $value, $template);
            $template = str_replace("{{$key}}", $value, $template);
        }
        
        return $template;
    }

    /**
     * Execute MCP command
     */
    protected function executeMCPCommand(string $command): array
    {
        // Remove 'mcp:' prefix
        $mcpCommand = substr($command, 4);
        
        // Parse MCP command format: mcp:service.method(params)
        if (preg_match('/^([a-zA-Z0-9_]+)\.([a-zA-Z0-9_]+)\((.*)\)$/', $mcpCommand, $matches)) {
            $service = $matches[1];
            $method = $matches[2];
            $params = json_decode('[' . $matches[3] . ']', true) ?: [];
            
            // Use MCP Auto Discovery to find the right server
            $discovery = app(MCPAutoDiscoveryService::class);
            $mcpInfo = $discovery->discoverForTask("Execute {$service}.{$method}", [
                'service' => $service,
                'method' => $method
            ]);
            
            if ($mcpInfo['server']) {
                // Execute via the discovered MCP server
                $result = $mcpInfo['server']->execute($method, $params);
                return [
                    'success' => true,
                    'result' => $result,
                    'mcp_server' => get_class($mcpInfo['server'])
                ];
            }
        }
        
        throw new \Exception("Invalid MCP command format: {$command}");
    }

    /**
     * Execute shell command
     */
    protected function executeShellCommand(string $command): array
    {
        // Security check - only allow safe commands
        $allowedCommands = [
            'php artisan',
            'composer',
            'npm',
            'git status',
            'git log',
            'git diff',
            'date',
            'echo',
            'pwd',
            'ls -la'
        ];
        
        $isAllowed = false;
        foreach ($allowedCommands as $allowed) {
            if (str_starts_with($command, $allowed)) {
                $isAllowed = true;
                break;
            }
        }
        
        if (!$isAllowed) {
            throw new \Exception("Command not allowed for security reasons: {$command}");
        }
        
        // Execute the command
        $result = Process::run($command);
        
        return [
            'success' => $result->successful(),
            'output' => $result->output(),
            'errorOutput' => $result->errorOutput(),
            'exitCode' => $result->exitCode()
        ];
    }

    /**
     * Handle job failure
     */
    public function failed(Throwable $exception): void
    {
        Log::error('ExecuteCommandJob failed completely', [
            'execution_id' => $this->execution->id,
            'exception' => $exception->getMessage()
        ]);
    }
}
<?php

namespace App\Console\Commands;

use App\Services\MCP\DebugMCPServer;
use Illuminate\Console\Command;

class MCPDebugCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mcp:debug 
                            {tool : The debug tool to execute}
                            {--params= : JSON-encoded parameters}
                            {--json : Output result as JSON}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Execute MCP Debug Server tools for debugging authentication, sessions, routes, and middleware';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $tool = $this->argument('tool');
        $params = $this->option('params') ? json_decode($this->option('params'), true) : [];
        $jsonOutput = $this->option('json');

        // Get the debug server
        $debugServer = app(DebugMCPServer::class);

        // List available tools if requested
        if ($tool === 'list') {
            $this->listTools($debugServer, $jsonOutput);

            return Command::SUCCESS;
        }

        // Execute the tool
        try {
            $result = $debugServer->executeTool($tool, $params);

            if ($jsonOutput) {
                $this->line(json_encode($result, JSON_PRETTY_PRINT));
            } else {
                $this->displayResult($tool, $result);
            }

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error('Error executing tool: ' . $e->getMessage());

            return Command::FAILURE;
        }
    }

    /**
     * List available tools.
     */
    protected function listTools(DebugMCPServer $server, bool $jsonOutput): void
    {
        $tools = $server->getTools();

        if ($jsonOutput) {
            $this->line(json_encode($tools, JSON_PRETTY_PRINT));

            return;
        }

        $this->info('Available Debug Tools:');
        $this->newLine();

        foreach ($tools as $tool) {
            $this->comment($tool['name']);
            $this->line('  ' . $tool['description']);

            if (isset($tool['inputSchema']['properties']) && ! empty($tool['inputSchema']['properties'])) {
                $this->line('  Parameters:');
                foreach ($tool['inputSchema']['properties'] as $param => $schema) {
                    $required = isset($tool['inputSchema']['required']) && in_array($param, $tool['inputSchema']['required']);
                    $this->line(sprintf(
                        '    - %s (%s)%s: %s',
                        $param,
                        $schema['type'] ?? 'string',
                        $required ? ' [required]' : '',
                        $schema['description'] ?? ''
                    ));
                }
            }
            $this->newLine();
        }
    }

    /**
     * Display result in a formatted way.
     */
    protected function displayResult(string $tool, array $result): void
    {
        $this->info("Result from tool: $tool");
        $this->newLine();

        switch ($tool) {
            case 'debug_auth_state':
                $this->displayAuthState($result);

                break;
            case 'trace_request_flow':
                $this->displayRequestFlow($result);

                break;
            case 'debug_route_conflicts':
                $this->displayRouteConflicts($result);

                break;
            case 'monitor_middleware':
                $this->displayMiddleware($result);

                break;
            case 'debug_session':
                $this->displaySession($result);

                break;
            case 'analyze_auth_flow':
                $this->displayAuthFlow($result);

                break;
            case 'list_routes':
                $this->displayRoutes($result);

                break;
            default:
                // Default display for other tools
                $this->displayArray($result);
        }
    }

    /**
     * Display auth state.
     */
    protected function displayAuthState(array $result): void
    {
        foreach ($result as $guard => $state) {
            if ($guard === 'sanctum_tokens') {
                $this->info("Active Sanctum Tokens: $state");

                continue;
            }

            $this->comment("Guard: $guard");

            if (isset($state['error'])) {
                $this->error('  Error: ' . $state['error']);

                continue;
            }

            $authenticated = $state['authenticated'] ?? false;
            $this->line('  Authenticated: ' . ($authenticated ? 'Yes' : 'No'));

            if ($authenticated && isset($state['user'])) {
                $this->line('  User ID: ' . $state['user']['id']);
                $this->line('  User Email: ' . ($state['user']['email'] ?? 'N/A'));
                $this->line('  User Type: ' . $state['user']['type']);
                $this->line('  Company ID: ' . ($state['user']['company_id'] ?? 'N/A'));
            }

            $this->line('  Session ID: ' . ($state['session_id'] ?? 'N/A'));
            $this->newLine();
        }
    }

    /**
     * Display request flow.
     */
    protected function displayRequestFlow(array $result): void
    {
        $this->info('Request Details:');
        $this->line('  Method: ' . $result['request']['method']);
        $this->line('  URI: ' . $result['request']['uri']);

        if (! empty($result['request']['headers'])) {
            $this->line('  Headers:');
            foreach ($result['request']['headers'] as $key => $value) {
                $this->line("    $key: $value");
            }
        }

        if (isset($result['route'])) {
            $this->newLine();
            $this->info('Matched Route:');
            $this->line('  URI Pattern: ' . $result['route']['uri']);
            $this->line('  Name: ' . ($result['route']['name'] ?? 'N/A'));
            $this->line('  Controller: ' . ($result['route']['controller'] ?? 'N/A'));
            $this->line('  Method: ' . ($result['route']['method'] ?? 'N/A'));
        }

        if (! empty($result['middleware'])) {
            $this->newLine();
            $this->info('Middleware Stack:');
            foreach ($result['middleware'] as $index => $middleware) {
                $this->line(sprintf('  %d. %s', $index + 1, $middleware['class']));
                if (! empty($middleware['parameters'])) {
                    $this->line('     Parameters: ' . implode(', ', $middleware['parameters']));
                }
            }
        }

        if (! empty($result['errors'])) {
            $this->newLine();
            $this->error('Errors:');
            foreach ($result['errors'] as $error) {
                $this->line('  - ' . $error);
            }
        }
    }

    /**
     * Display route conflicts.
     */
    protected function displayRouteConflicts(array $result): void
    {
        $this->info('Route Analysis:');
        $this->line('Total routes: ' . $result['total_routes']);

        if (! empty($result['conflicts'])) {
            $this->newLine();
            $this->error('Route Conflicts Found:');
            foreach ($result['conflicts'] as $key => $routes) {
                $this->comment("Conflict: $key");
                foreach ($routes as $index => $route) {
                    $this->line(sprintf('  %d. Name: %s', $index + 1, $route['name'] ?? 'N/A'));
                    $this->line('     Action: ' . $route['action']);
                }
            }
        } else {
            $this->success('No route conflicts found!');
        }
    }

    /**
     * Display middleware analysis.
     */
    protected function displayMiddleware(array $result): void
    {
        if (isset($result['error'])) {
            $this->error($result['error']);

            return;
        }

        $this->info('Route Information:');
        $this->line('  URI: ' . $result['route']['uri']);
        $this->line('  Name: ' . ($result['route']['name'] ?? 'N/A'));
        $this->line('  Action: ' . $result['route']['action']);

        $this->newLine();
        $this->info('Middleware Execution Order:');
        foreach ($result['middleware'] as $middleware) {
            $this->line(sprintf('  %d. %s', $middleware['order'], $middleware['class']));
            if ($middleware['alias']) {
                $this->line('     Alias: ' . $middleware['alias']);
            }
            if (! empty($middleware['parameters'])) {
                $this->line('     Parameters: ' . implode(', ', $middleware['parameters']));
            }
        }

        if (isset($result['global_middleware'])) {
            $this->newLine();
            $this->info('Global Middleware:');
            if (isset($result['global_middleware']['global'])) {
                foreach ($result['global_middleware']['global'] as $middleware) {
                    $this->line('  - ' . $middleware);
                }
            }
        }
    }

    /**
     * Display session debug info.
     */
    protected function displaySession(array $result): void
    {
        $this->info('Session Configuration:');
        $this->line('  Driver: ' . $result['session_driver']);
        $this->line('  Lifetime: ' . $result['session_lifetime'] . ' minutes');
        $this->line('  Domain: ' . ($result['session_domain'] ?? 'default'));
        $this->line('  Path: ' . $result['session_path']);
        $this->line('  Secure: ' . ($result['session_secure'] ? 'Yes' : 'No'));
        $this->line('  Same Site: ' . ($result['session_same_site'] ?? 'default'));

        $this->newLine();
        $this->info('Session State:');
        $this->line('  Session ID: ' . $result['session_id']);
        $this->line('  Current Session ID: ' . $result['current_session_id']);
        $this->line('  CSRF Token: ' . substr($result['csrf_token'] ?? '', 0, 16) . '...');

        if (isset($result['auth_session_key'])) {
            $this->line('  Auth Session Key: ' . $result['auth_session_key']);
            $this->line('  Auth Session Value: ' . ($result['auth_session_value'] ?? 'null'));
        }

        if (isset($result['session_file_exists'])) {
            $this->newLine();
            $this->info('Session File:');
            $this->line('  Exists: ' . ($result['session_file_exists'] ? 'Yes' : 'No'));
            if ($result['session_file_exists']) {
                $this->line('  Size: ' . $result['session_file_size'] . ' bytes');
                $this->line('  Modified: ' . $result['session_file_modified']);
            }
        }
    }

    /**
     * Display auth flow analysis.
     */
    protected function displayAuthFlow(array $result): void
    {
        $this->info('Authentication Flow Analysis:');
        $this->line('  User Type: ' . $result['user_type']);
        $this->line('  Email: ' . $result['email']);
        $this->line('  Table: ' . $result['table']);
        $this->line('  Guard: ' . $result['guard']);

        $this->newLine();
        $this->info('User Status:');
        $this->line('  User Found: ' . ($result['user_found'] ? 'Yes' : 'No'));
        $this->line('  Authentication Possible: ' . ($result['authentication_possible'] ? 'Yes' : 'No'));

        if ($result['user_found'] && isset($result['user'])) {
            $this->newLine();
            $this->info('User Details:');
            foreach ($result['user'] as $key => $value) {
                $this->line("  $key: " . (is_bool($value) ? ($value ? 'true' : 'false') : $value));
            }
        }

        if (isset($result['company'])) {
            $this->newLine();
            $this->info('Company Details:');
            foreach ($result['company'] as $key => $value) {
                $this->line("  $key: " . (is_bool($value) ? ($value ? 'true' : 'false') : $value));
            }
        }

        if (! empty($result['issues'])) {
            $this->newLine();
            $this->error('Issues Found:');
            foreach ($result['issues'] as $issue) {
                $this->line('  - ' . $issue);
            }
        }

        if (! empty($result['recommendations'])) {
            $this->newLine();
            $this->comment('Recommendations:');
            foreach ($result['recommendations'] as $recommendation) {
                $this->line('  - ' . $recommendation);
            }
        }
    }

    /**
     * Display routes.
     */
    protected function displayRoutes(array $result): void
    {
        $this->info('Routes Summary:');
        $this->line('Total routes: ' . $result['total']);

        if (empty($result['routes'])) {
            $this->comment('No routes found matching the criteria.');

            return;
        }

        $this->newLine();
        $this->table(
            ['Method', 'URI', 'Name', 'Action'],
            array_map(function ($route) {
                return [
                    implode('|', $route['methods']),
                    $route['uri'],
                    $route['name'] ?? '',
                    $this->truncateAction($route['action']),
                ];
            }, array_slice($result['routes'], 0, 50)) // Limit to 50 routes for display
        );

        if (count($result['routes']) > 50) {
            $this->comment('... and ' . (count($result['routes']) - 50) . ' more routes');
        }
    }

    /**
     * Display generic array.
     */
    protected function displayArray(array $data, string $prefix = ''): void
    {
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $this->comment($prefix . $key . ':');
                $this->displayArray($value, $prefix . '  ');
            } elseif (is_bool($value)) {
                $this->line($prefix . $key . ': ' . ($value ? 'true' : 'false'));
            } elseif (is_null($value)) {
                $this->line($prefix . $key . ': null');
            } else {
                $this->line($prefix . $key . ': ' . $value);
            }
        }
    }

    /**
     * Truncate action name for display.
     */
    protected function truncateAction(string $action): string
    {
        if (strlen($action) > 50) {
            return substr($action, 0, 47) . '...';
        }

        return $action;
    }
}

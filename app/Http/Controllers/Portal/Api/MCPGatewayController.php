<?php

namespace App\Http\Controllers\Portal\Api;

use App\Http\Controllers\Controller;
use App\Traits\UsesMCPServers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * MCPGatewayController - Unified API Gateway for MCP Services
 * 
 * This controller provides a single endpoint that can dynamically route
 * requests to different MCP servers based on the requested resource.
 * 
 * Usage:
 * POST /api/v2/mcp/execute
 * {
 *   "server": "billing",
 *   "method": "getBillingOverview",
 *   "params": {...}
 * }
 * 
 * Or with auto-discovery:
 * POST /api/v2/mcp/discover
 * {
 *   "task": "get customer billing information",
 *   "params": {...}
 * }
 */
class MCPGatewayController extends Controller
{
    use UsesMCPServers;

    /**
     * Available MCP servers and their descriptions
     */
    protected array $availableServers = [
        'billing' => 'Billing and payment management',
        'customer' => 'Customer data and management',
        'appointment' => 'Appointment scheduling and management',
        'team' => 'Team member management',
        'call' => 'Call tracking and management',
        'analytics' => 'Analytics and reporting',
        'calcom' => 'Calendar integration',
        'retell' => 'AI phone service integration',
        'stripe' => 'Payment processing',
        'webhook' => 'Webhook management',
        'queue' => 'Job queue management',
        'knowledge' => 'Knowledge base',
        'company' => 'Company management',
        'branch' => 'Branch/location management',
        'sentry' => 'Error tracking'
    ];

    public function __construct()
    {
        // Enable all MCP servers for gateway
        $this->setMCPPreferences(array_fill_keys(array_keys($this->availableServers), true));
    }

    /**
     * Execute a specific MCP method
     */
    public function execute(Request $request)
    {
        $request->validate([
            'server' => 'required|string',
            'method' => 'required|string',
            'params' => 'sometimes|array'
        ]);

        $user = $this->getAuthenticatedUser();
        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $server = $request->input('server');
        $method = $request->input('method');
        $params = $request->input('params', []);

        // Inject common parameters
        $params = $this->injectCommonParams($params, $user);

        try {
            // Log the request
            Log::channel('mcp')->info('MCP Gateway Execute', [
                'user_id' => $user->id ?? null,
                'server' => $server,
                'method' => $method,
                'correlation_id' => $params['correlation_id'] ?? null
            ]);

            // Execute via direct MCP call
            $result = $this->callMCPServer($server, $method, $params);

            return response()->json([
                'success' => true,
                'server' => $server,
                'method' => $method,
                'result' => $result
            ]);

        } catch (\Exception $e) {
            Log::error('MCP Gateway Error', [
                'server' => $server,
                'method' => $method,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
                'server' => $server,
                'method' => $method
            ], 500);
        }
    }

    /**
     * Discover and execute the best MCP server for a task
     */
    public function discover(Request $request)
    {
        $request->validate([
            'task' => 'required|string',
            'params' => 'sometimes|array',
            'context' => 'sometimes|array'
        ]);

        $user = $this->getAuthenticatedUser();
        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $task = $request->input('task');
        $params = $request->input('params', []);
        $context = $request->input('context', []);

        // Inject common parameters
        $params = $this->injectCommonParams($params, $user);

        try {
            // Log the discovery request
            Log::channel('mcp')->info('MCP Gateway Discover', [
                'user_id' => $user->id ?? null,
                'task' => $task,
                'correlation_id' => $params['correlation_id'] ?? null
            ]);

            // Execute via auto-discovery
            $result = $this->executeMCPTask($task, $params, ['context' => $context]);

            // Get discovery information
            $discovery = $this->getMCPCallHistory();
            $lastDiscovery = collect($discovery)->firstWhere('type', 'discovery');

            return response()->json([
                'success' => true,
                'task' => $task,
                'discovered_server' => $lastDiscovery['data']['server'] ?? 'unknown',
                'confidence' => $lastDiscovery['data']['confidence'] ?? 0,
                'result' => $result
            ]);

        } catch (\Exception $e) {
            Log::error('MCP Gateway Discovery Error', [
                'task' => $task,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
                'task' => $task
            ], 500);
        }
    }

    /**
     * List available MCP servers and their methods
     */
    public function listServers(Request $request)
    {
        $user = $this->getAuthenticatedUser();
        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        // Get detailed server information
        $servers = [];
        foreach ($this->availableServers as $name => $description) {
            $servers[] = [
                'name' => $name,
                'description' => $description,
                'status' => $this->checkServerStatus($name)
            ];
        }

        return response()->json([
            'servers' => $servers,
            'total' => count($servers)
        ]);
    }

    /**
     * Get server methods and documentation
     */
    public function serverInfo(Request $request, string $server)
    {
        $user = $this->getAuthenticatedUser();
        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        if (!isset($this->availableServers[$server])) {
            return response()->json(['error' => 'Server not found'], 404);
        }

        try {
            // Get server tools/methods
            $result = $this->callMCPServer($server, 'getTools', []);

            return response()->json([
                'server' => $server,
                'description' => $this->availableServers[$server],
                'methods' => $result,
                'status' => $this->checkServerStatus($server)
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'server' => $server,
                'description' => $this->availableServers[$server],
                'status' => 'error',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Batch execute multiple MCP calls
     */
    public function batch(Request $request)
    {
        $request->validate([
            'requests' => 'required|array|min:1|max:10',
            'requests.*.server' => 'required|string',
            'requests.*.method' => 'required|string',
            'requests.*.params' => 'sometimes|array'
        ]);

        $user = $this->getAuthenticatedUser();
        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $requests = $request->input('requests');
        $results = [];
        $correlationId = Str::uuid()->toString();

        // Execute all requests
        foreach ($requests as $index => $req) {
            $params = $req['params'] ?? [];
            $params = $this->injectCommonParams($params, $user);
            $params['batch_correlation_id'] = $correlationId;
            $params['batch_index'] = $index;

            try {
                $result = $this->callMCPServer($req['server'], $req['method'], $params);
                $results[] = [
                    'index' => $index,
                    'success' => true,
                    'server' => $req['server'],
                    'method' => $req['method'],
                    'result' => $result
                ];
            } catch (\Exception $e) {
                $results[] = [
                    'index' => $index,
                    'success' => false,
                    'server' => $req['server'],
                    'method' => $req['method'],
                    'error' => $e->getMessage()
                ];
            }
        }

        return response()->json([
            'batch_id' => $correlationId,
            'total' => count($requests),
            'successful' => collect($results)->where('success', true)->count(),
            'failed' => collect($results)->where('success', false)->count(),
            'results' => $results
        ]);
    }

    /**
     * Get authenticated user from portal or web guard
     */
    protected function getAuthenticatedUser()
    {
        return Auth::guard('portal')->user() ?: Auth::guard('web')->user();
    }

    /**
     * Inject common parameters
     */
    protected function injectCommonParams(array $params, $user): array
    {
        // Add correlation ID if not present
        if (!isset($params['correlation_id'])) {
            $params['correlation_id'] = Str::uuid()->toString();
        }

        // Add company ID if not present
        if (!isset($params['company_id'])) {
            if (session('is_admin_viewing')) {
                $params['company_id'] = session('admin_impersonation.company_id');
            } else {
                $params['company_id'] = $user->company_id ?? null;
            }
        }

        // Add user context
        if (!isset($params['user_id'])) {
            $params['user_id'] = $user->id ?? null;
        }

        return $params;
    }

    /**
     * Check if server is available
     */
    protected function checkServerStatus(string $server): string
    {
        try {
            // Try to get server info
            $this->callMCPServer($server, 'getTools', []);
            return 'active';
        } catch (\Exception $e) {
            // Check if it's a method not found error
            if (str_contains($e->getMessage(), 'getTools')) {
                // Server exists but doesn't have getTools method
                return 'active';
            }
            return 'error';
        }
    }
}
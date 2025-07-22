<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\MCP\RetellAIBridgeMCPServer;
use App\Models\RetellAICallCampaign;
use App\Models\Call;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\Rule;

class RetellMCPController extends Controller
{
    protected RetellAIBridgeMCPServer $bridgeServer;

    public function __construct(RetellAIBridgeMCPServer $bridgeServer)
    {
        $this->bridgeServer = $bridgeServer;
        $this->middleware('auth:sanctum');
    }

    /**
     * Initiate an outbound AI call
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function initiateCall(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'to_number' => 'required|string',
            'agent_id' => 'required|string',
            'from_number' => 'nullable|string',
            'purpose' => 'nullable|string',
            'customer_id' => 'nullable|exists:customers,id',
            'dynamic_variables' => 'nullable|array',
            'campaign_id' => 'nullable|exists:retell_ai_call_campaigns,id',
        ]);

        try {
            $params = array_merge($validated, [
                'company_id' => $request->user()->company_id,
            ]);

            $result = $this->bridgeServer->createOutboundCall($params);

            return response()->json($result, 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get call status
     * 
     * @param string $callId
     * @return JsonResponse
     */
    public function getCallStatus(string $callId): JsonResponse
    {
        $call = Call::where('id', $callId)
            ->where('company_id', auth()->user()->company_id)
            ->first();

        if (!$call) {
            return response()->json([
                'success' => false,
                'error' => 'Call not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'call' => [
                'id' => $call->id,
                'retell_call_id' => $call->retell_call_id,
                'status' => $call->status,
                'direction' => $call->direction,
                'from_number' => $call->from_number,
                'to_number' => $call->to_number,
                'duration' => $call->duration_sec,
                'created_at' => $call->created_at,
                'transcript' => $call->transcript,
                'recording_url' => $call->recording_url,
                'metadata' => $call->metadata,
            ],
        ]);
    }

    /**
     * Create a call campaign
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function createCampaign(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'agent_id' => 'required|string',
            'target_type' => ['required', Rule::in(['all_customers', 'inactive_customers', 'custom_list'])],
            'target_criteria' => 'nullable|array',
            'schedule_type' => ['nullable', Rule::in(['immediate', 'scheduled', 'recurring'])],
            'scheduled_at' => 'nullable|date|after:now',
            'dynamic_variables' => 'nullable|array',
        ]);

        try {
            $params = array_merge($validated, [
                'company_id' => $request->user()->company_id,
            ]);

            $result = $this->bridgeServer->createCallCampaign($params);

            return response()->json($result, 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Start a campaign
     * 
     * @param string $campaignId
     * @return JsonResponse
     */
    public function startCampaign(string $campaignId): JsonResponse
    {
        $campaign = RetellAICallCampaign::where('id', $campaignId)
            ->where('company_id', auth()->user()->company_id)
            ->first();

        if (!$campaign) {
            return response()->json([
                'success' => false,
                'error' => 'Campaign not found',
            ], 404);
        }

        try {
            $result = $this->bridgeServer->startCampaign(['campaign_id' => $campaignId]);

            return response()->json($result);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Pause a campaign
     * 
     * @param string $campaignId
     * @return JsonResponse
     */
    public function pauseCampaign(string $campaignId): JsonResponse
    {
        $campaign = RetellAICallCampaign::where('id', $campaignId)
            ->where('company_id', auth()->user()->company_id)
            ->first();

        if (!$campaign) {
            return response()->json([
                'success' => false,
                'error' => 'Campaign not found',
            ], 404);
        }

        if (!$campaign->canPause()) {
            return response()->json([
                'success' => false,
                'error' => 'Campaign cannot be paused in current state',
            ], 400);
        }

        $campaign->update(['status' => 'paused']);

        return response()->json([
            'success' => true,
            'message' => 'Campaign paused successfully',
            'campaign_id' => $campaign->id,
            'status' => 'paused',
        ]);
    }

    /**
     * Resume a campaign
     * 
     * @param string $campaignId
     * @return JsonResponse
     */
    public function resumeCampaign(string $campaignId): JsonResponse
    {
        $campaign = RetellAICallCampaign::where('id', $campaignId)
            ->where('company_id', auth()->user()->company_id)
            ->first();

        if (!$campaign) {
            return response()->json([
                'success' => false,
                'error' => 'Campaign not found',
            ], 404);
        }

        if (!$campaign->canResume()) {
            return response()->json([
                'success' => false,
                'error' => 'Campaign cannot be resumed in current state',
            ], 400);
        }

        $campaign->update(['status' => 'running']);
        
        // Re-dispatch the job
        \App\Jobs\ProcessRetellAICampaignJob::dispatch($campaign)->onQueue('campaigns');

        return response()->json([
            'success' => true,
            'message' => 'Campaign resumed successfully',
            'campaign_id' => $campaign->id,
            'status' => 'running',
        ]);
    }

    /**
     * Get campaign details
     * 
     * @param string $campaignId
     * @return JsonResponse
     */
    public function getCampaign(string $campaignId): JsonResponse
    {
        $campaign = RetellAICallCampaign::with(['creator', 'calls'])
            ->where('id', $campaignId)
            ->where('company_id', auth()->user()->company_id)
            ->first();

        if (!$campaign) {
            return response()->json([
                'success' => false,
                'error' => 'Campaign not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'campaign' => [
                'id' => $campaign->id,
                'name' => $campaign->name,
                'description' => $campaign->description,
                'status' => $campaign->status,
                'target_type' => $campaign->target_type,
                'total_targets' => $campaign->total_targets,
                'calls_completed' => $campaign->calls_completed,
                'calls_failed' => $campaign->calls_failed,
                'completion_percentage' => $campaign->completion_percentage,
                'success_rate' => $campaign->success_rate,
                'started_at' => $campaign->started_at,
                'completed_at' => $campaign->completed_at,
                'created_by' => $campaign->creator ? [
                    'id' => $campaign->creator->id,
                    'name' => $campaign->creator->name,
                ] : null,
                'created_at' => $campaign->created_at,
                'updated_at' => $campaign->updated_at,
            ],
        ]);
    }

    /**
     * List campaigns
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function listCampaigns(Request $request): JsonResponse
    {
        $query = RetellAICallCampaign::where('company_id', auth()->user()->company_id);

        // Apply filters
        if ($request->has('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->has('active')) {
            $query->active();
        }

        // Pagination
        $perPage = $request->input('per_page', 15);
        $campaigns = $query->orderBy('created_at', 'desc')->paginate($perPage);

        return response()->json([
            'success' => true,
            'campaigns' => $campaigns->items(),
            'pagination' => [
                'total' => $campaigns->total(),
                'per_page' => $campaigns->perPage(),
                'current_page' => $campaigns->currentPage(),
                'last_page' => $campaigns->lastPage(),
            ],
        ]);
    }

    /**
     * Test voice configuration
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function testVoice(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'agent_id' => 'required|string',
            'test_number' => 'required|string',
            'test_scenario' => 'nullable|string',
        ]);

        try {
            $params = array_merge($validated, [
                'company_id' => $request->user()->company_id,
            ]);

            $result = $this->bridgeServer->testVoiceConfiguration($params);

            return response()->json($result, 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get available tools from MCP server
     * 
     * @return JsonResponse
     */
    public function getAvailableTools(): JsonResponse
    {
        try {
            $tools = $this->bridgeServer->getAvailableTools();

            return response()->json($tools);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Health check for MCP server
     * 
     * @return JsonResponse
     */
    public function healthCheck(): JsonResponse
    {
        try {
            $health = $this->bridgeServer->healthCheck();

            return response()->json($health);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'unhealthy',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Webhook endpoint for call status updates from external MCP
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function callCreatedWebhook(Request $request): JsonResponse
    {
        // Validate webhook token if configured
        $token = $request->bearerToken();
        if ($token !== config('services.retell_mcp.token')) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $callId = $request->input('callId');
        $params = $request->input('params');
        $timestamp = $request->input('timestamp');

        // Log the webhook
        \Log::info('Retell MCP call created webhook', [
            'call_id' => $callId,
            'params' => $params,
            'timestamp' => $timestamp,
        ]);

        // You can process additional logic here if needed

        return response()->json(['success' => true]);
    }
}
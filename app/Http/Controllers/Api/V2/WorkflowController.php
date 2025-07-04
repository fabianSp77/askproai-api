<?php

namespace App\Http\Controllers\Api\V2;

use App\Http\Controllers\Controller;
use App\Models\CommandWorkflow;
use App\Models\WorkflowExecution;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class WorkflowController extends Controller
{
    /**
     * Display a listing of workflows
     */
    public function index(Request $request): JsonResponse
    {
        $query = CommandWorkflow::query()
            ->where(function ($q) {
                $q->where('company_id', Auth::user()->company_id)
                  ->orWhere('is_public', true);
            })
            ->with(['creator', 'company']);

        // Filter by active status
        if ($request->has('active')) {
            $query->where('is_active', $request->boolean('active'));
        }

        // Sort
        if ($request->get('sort') === 'popular') {
            $query->orderBy('usage_count', 'desc');
        } else {
            $query->latest();
        }

        $workflows = $query->paginate($request->get('per_page', 20));

        return response()->json($workflows);
    }

    /**
     * Store a newly created workflow
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string',
            'description' => 'nullable|string',
            'icon' => 'nullable|string',
            'is_public' => 'boolean',
            'is_active' => 'boolean',
            'config' => 'nullable|array',
            'commands' => 'required|array',
            'commands.*.command_id' => 'required|exists:command_templates,id',
            'commands.*.order' => 'required|integer',
            'commands.*.config' => 'nullable|array',
            'commands.*.condition' => 'nullable|string',
        ]);

        DB::beginTransaction();
        try {
            $workflow = CommandWorkflow::create([
                'name' => $validated['name'],
                'description' => $validated['description'] ?? null,
                'icon' => $validated['icon'] ?? null,
                'company_id' => Auth::user()->company_id,
                'created_by' => Auth::id(),
                'is_public' => $validated['is_public'] ?? false,
                'is_active' => $validated['is_active'] ?? true,
                'config' => $validated['config'] ?? null,
            ]);

            // Add commands to workflow
            foreach ($validated['commands'] as $commandData) {
                $workflow->commands()->attach($commandData['command_id'], [
                    'order' => $commandData['order'],
                    'config' => isset($commandData['config']) ? json_encode($commandData['config']) : null,
                    'condition' => $commandData['condition'] ?? null,
                ]);
            }

            DB::commit();

            return response()->json([
                'message' => 'Workflow created successfully',
                'workflow' => $workflow->load(['creator', 'company', 'commands'])
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Failed to create workflow',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified workflow
     */
    public function show(string $id): JsonResponse
    {
        $workflow = CommandWorkflow::query()
            ->where(function ($q) {
                $q->where('company_id', Auth::user()->company_id)
                  ->orWhere('is_public', true);
            })
            ->with(['creator', 'company', 'commands', 'executions' => function ($query) {
                $query->latest()->limit(10);
            }])
            ->findOrFail($id);

        return response()->json($workflow);
    }

    /**
     * Update the specified workflow
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $workflow = CommandWorkflow::query()
            ->where('company_id', Auth::user()->company_id)
            ->findOrFail($id);

        $validated = $request->validate([
            'name' => 'string',
            'description' => 'nullable|string',
            'icon' => 'nullable|string',
            'is_public' => 'boolean',
            'is_active' => 'boolean',
            'config' => 'nullable|array',
            'schedule' => 'nullable|array',
        ]);

        $workflow->update($validated);

        return response()->json([
            'message' => 'Workflow updated successfully',
            'workflow' => $workflow->fresh(['creator', 'company'])
        ]);
    }

    /**
     * Remove the specified workflow
     */
    public function destroy(string $id): JsonResponse
    {
        $workflow = CommandWorkflow::query()
            ->where('company_id', Auth::user()->company_id)
            ->findOrFail($id);

        $workflow->delete();

        return response()->json([
            'message' => 'Workflow deleted successfully'
        ]);
    }

    /**
     * Execute a workflow
     */
    public function execute(Request $request, string $id): JsonResponse
    {
        $workflow = CommandWorkflow::query()
            ->where(function ($q) {
                $q->where('company_id', Auth::user()->company_id)
                  ->orWhere('is_public', true);
            })
            ->findOrFail($id);

        if (!$workflow->is_active) {
            return response()->json([
                'message' => 'Workflow is not active'
            ], 422);
        }

        $validated = $request->validate([
            'parameters' => 'nullable|array'
        ]);

        $execution = $workflow->execute(Auth::user(), $validated['parameters'] ?? []);

        return response()->json([
            'message' => 'Workflow execution started',
            'execution_id' => $execution->id,
            'total_steps' => $workflow->commands()->count()
        ], 202);
    }

    /**
     * Toggle favorite status
     */
    public function toggleFavorite(string $id): JsonResponse
    {
        $workflow = CommandWorkflow::query()
            ->where(function ($q) {
                $q->where('company_id', Auth::user()->company_id)
                  ->orWhere('is_public', true);
            })
            ->findOrFail($id);

        $user = Auth::user();
        
        if ($user->favoriteWorkflows()->where('command_workflow_id', $id)->exists()) {
            $user->favoriteWorkflows()->detach($id);
            $message = 'Workflow removed from favorites';
        } else {
            $user->favoriteWorkflows()->attach($id);
            $message = 'Workflow added to favorites';
        }

        return response()->json([
            'message' => $message,
            'is_favorite' => $user->favoriteWorkflows()->where('command_workflow_id', $id)->exists()
        ]);
    }
}
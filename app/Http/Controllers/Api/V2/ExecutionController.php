<?php

namespace App\Http\Controllers\Api\V2;

use App\Http\Controllers\Controller;
use App\Models\CommandExecution;
use App\Models\WorkflowExecution;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class ExecutionController extends Controller
{
    /**
     * Get command executions for the current user
     */
    public function commandExecutions(Request $request): JsonResponse
    {
        $query = CommandExecution::query()
            ->where('user_id', Auth::id())
            ->with(['commandTemplate', 'workflowExecution'])
            ->latest();

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->get('status'));
        }

        // Filter by command
        if ($request->has('command_id')) {
            $query->where('command_template_id', $request->get('command_id'));
        }

        // Filter by date range
        if ($request->has('from')) {
            $query->where('created_at', '>=', $request->get('from'));
        }
        if ($request->has('to')) {
            $query->where('created_at', '<=', $request->get('to'));
        }

        $executions = $query->paginate($request->get('per_page', 20));

        return response()->json($executions);
    }

    /**
     * Get workflow executions for the current user
     */
    public function workflowExecutions(Request $request): JsonResponse
    {
        $query = WorkflowExecution::query()
            ->where('user_id', Auth::id())
            ->with(['workflow', 'commandExecutions'])
            ->latest();

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->get('status'));
        }

        // Filter by workflow
        if ($request->has('workflow_id')) {
            $query->where('command_workflow_id', $request->get('workflow_id'));
        }

        // Filter by date range
        if ($request->has('from')) {
            $query->where('created_at', '>=', $request->get('from'));
        }
        if ($request->has('to')) {
            $query->where('created_at', '<=', $request->get('to'));
        }

        $executions = $query->paginate($request->get('per_page', 20));

        return response()->json($executions);
    }

    /**
     * Get details of a specific command execution
     */
    public function commandExecutionDetails(string $id): JsonResponse
    {
        $execution = CommandExecution::query()
            ->where('user_id', Auth::id())
            ->with(['commandTemplate', 'workflowExecution', 'user'])
            ->findOrFail($id);

        return response()->json($execution);
    }

    /**
     * Get details of a specific workflow execution
     */
    public function workflowExecutionDetails(string $id): JsonResponse
    {
        $execution = WorkflowExecution::query()
            ->where('user_id', Auth::id())
            ->with([
                'workflow.commands',
                'commandExecutions.commandTemplate',
                'user'
            ])
            ->findOrFail($id);

        // Add progress information
        $execution->progress_percentage = $execution->getProgressPercentage();

        return response()->json($execution);
    }

    /**
     * Get execution statistics
     */
    public function statistics(Request $request): JsonResponse
    {
        $userId = Auth::id();
        
        // Command execution stats
        $commandStats = CommandExecution::query()
            ->where('user_id', $userId)
            ->selectRaw('
                COUNT(*) as total_executions,
                SUM(CASE WHEN status = "success" THEN 1 ELSE 0 END) as successful_executions,
                SUM(CASE WHEN status = "failed" THEN 1 ELSE 0 END) as failed_executions,
                AVG(execution_time_ms) as avg_execution_time,
                MAX(execution_time_ms) as max_execution_time
            ')
            ->first();

        // Workflow execution stats
        $workflowStats = WorkflowExecution::query()
            ->where('user_id', $userId)
            ->selectRaw('
                COUNT(*) as total_executions,
                SUM(CASE WHEN status = "success" THEN 1 ELSE 0 END) as successful_executions,
                SUM(CASE WHEN status = "failed" THEN 1 ELSE 0 END) as failed_executions,
                AVG(execution_time_ms) as avg_execution_time
            ')
            ->first();

        // Most used commands
        $topCommands = CommandExecution::query()
            ->where('user_id', $userId)
            ->join('command_templates', 'command_executions.command_template_id', '=', 'command_templates.id')
            ->selectRaw('
                command_templates.id,
                command_templates.title,
                command_templates.icon,
                COUNT(*) as execution_count,
                AVG(command_executions.execution_time_ms) as avg_time
            ')
            ->groupBy('command_templates.id', 'command_templates.title', 'command_templates.icon')
            ->orderBy('execution_count', 'desc')
            ->limit(5)
            ->get();

        // Recent activity
        $recentActivity = CommandExecution::query()
            ->where('user_id', $userId)
            ->with(['commandTemplate:id,title,icon'])
            ->latest()
            ->limit(10)
            ->get(['id', 'command_template_id', 'status', 'created_at', 'execution_time_ms']);

        return response()->json([
            'command_stats' => $commandStats,
            'workflow_stats' => $workflowStats,
            'top_commands' => $topCommands,
            'recent_activity' => $recentActivity
        ]);
    }
}
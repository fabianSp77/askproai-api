<?php

namespace App\Http\Controllers\Api\V2;

use App\Http\Controllers\Controller;
use App\Models\CommandTemplate;
use App\Models\CommandExecution;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class CommandController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        $query = CommandTemplate::query()
            ->forCompany(Auth::user()->company_id)
            ->with(['creator', 'company']);

        // Search functionality
        if ($request->has('search')) {
            $query->search($request->get('search'));
        }

        // Category filter
        if ($request->has('category')) {
            $query->where('category', $request->get('category'));
        }

        // Sort by popularity
        if ($request->get('sort') === 'popular') {
            $query->orderBy('usage_count', 'desc');
        } else {
            $query->latest();
        }

        $commands = $query->paginate($request->get('per_page', 20));

        return response()->json($commands);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|unique:command_templates',
            'title' => 'required|string',
            'icon' => 'nullable|string',
            'category' => 'required|string',
            'description' => 'nullable|string',
            'command_template' => 'required|string',
            'parameters' => 'nullable|array',
            'nlp_keywords' => 'nullable|array',
            'shortcut' => 'nullable|string',
            'is_public' => 'boolean',
            'is_premium' => 'boolean',
        ]);

        $command = CommandTemplate::create([
            ...$validated,
            'created_by' => Auth::id(),
            'company_id' => Auth::user()->company_id,
        ]);

        return response()->json([
            'message' => 'Command created successfully',
            'command' => $command->load(['creator', 'company'])
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id): JsonResponse
    {
        $command = CommandTemplate::query()
            ->forCompany(Auth::user()->company_id)
            ->with(['creator', 'company', 'executions' => function ($query) {
                $query->latest()->limit(10);
            }])
            ->findOrFail($id);

        return response()->json($command);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $command = CommandTemplate::query()
            ->where('company_id', Auth::user()->company_id)
            ->findOrFail($id);

        $validated = $request->validate([
            'title' => 'string',
            'icon' => 'nullable|string',
            'category' => 'string',
            'description' => 'nullable|string',
            'command_template' => 'string',
            'parameters' => 'nullable|array',
            'nlp_keywords' => 'nullable|array',
            'shortcut' => 'nullable|string',
            'is_public' => 'boolean',
            'is_premium' => 'boolean',
        ]);

        $command->update($validated);

        return response()->json([
            'message' => 'Command updated successfully',
            'command' => $command->fresh(['creator', 'company'])
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id): JsonResponse
    {
        $command = CommandTemplate::query()
            ->where('company_id', Auth::user()->company_id)
            ->findOrFail($id);

        $command->delete();

        return response()->json([
            'message' => 'Command deleted successfully'
        ]);
    }

    /**
     * Execute a command
     */
    public function execute(Request $request, string $id): JsonResponse
    {
        $command = CommandTemplate::query()
            ->forCompany(Auth::user()->company_id)
            ->findOrFail($id);

        $validated = $request->validate([
            'parameters' => 'nullable|array'
        ]);

        // Create execution record
        $execution = CommandExecution::create([
            'command_template_id' => $command->id,
            'user_id' => Auth::id(),
            'company_id' => Auth::user()->company_id,
            'parameters' => $validated['parameters'] ?? [],
            'status' => CommandExecution::STATUS_PENDING,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'correlation_id' => uniqid('cmd_', true),
        ]);

        // Dispatch job to execute command
        \App\Jobs\ExecuteCommandJob::dispatch($execution);

        return response()->json([
            'message' => 'Command execution started',
            'execution_id' => $execution->id,
            'correlation_id' => $execution->correlation_id
        ], 202);
    }

    /**
     * Toggle favorite status
     */
    public function toggleFavorite(string $id): JsonResponse
    {
        $command = CommandTemplate::query()
            ->forCompany(Auth::user()->company_id)
            ->findOrFail($id);

        $user = Auth::user();
        
        if ($user->favoriteCommands()->where('command_template_id', $id)->exists()) {
            $user->favoriteCommands()->detach($id);
            $message = 'Command removed from favorites';
        } else {
            $user->favoriteCommands()->attach($id);
            $message = 'Command added to favorites';
        }

        return response()->json([
            'message' => $message,
            'is_favorite' => $user->favoriteCommands()->where('command_template_id', $id)->exists()
        ]);
    }

    /**
     * Get command categories
     */
    public function categories(): JsonResponse
    {
        $categories = CommandTemplate::query()
            ->forCompany(Auth::user()->company_id)
            ->select('category')
            ->distinct()
            ->pluck('category');

        return response()->json($categories);
    }

    /**
     * Search commands using NLP
     */
    public function search(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'query' => 'required|string|min:2'
        ]);

        $query = $validated['query'];
        
        // Simple NLP search implementation
        $commands = CommandTemplate::query()
            ->forCompany(Auth::user()->company_id)
            ->where(function ($q) use ($query) {
                $q->where('title', 'like', "%{$query}%")
                  ->orWhere('description', 'like', "%{$query}%")
                  ->orWhereJsonContains('nlp_keywords', $query);
                  
                // Search for similar words
                $words = explode(' ', strtolower($query));
                foreach ($words as $word) {
                    if (strlen($word) > 2) {
                        $q->orWhereJsonContains('nlp_keywords', $word);
                    }
                }
            })
            ->with(['creator', 'company'])
            ->orderBy('usage_count', 'desc')
            ->limit(10)
            ->get();

        return response()->json($commands);
    }
}
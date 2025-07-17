<?php

namespace App\Http\Controllers\Portal\Api;

use App\Models\Company;
use App\Models\CompanyGoal;
use App\Services\GoalService;
use App\Services\GoalCalculationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class GoalApiController extends BaseApiController
{
    protected $goalService;
    protected $calculationService;

    public function __construct(GoalService $goalService, GoalCalculationService $calculationService)
    {
        $this->goalService = $goalService;
        $this->calculationService = $calculationService;
    }

    /**
     * Get all goals for the company
     */
    public function index(Request $request)
    {
        try {
            $company = $request->user()->company;
            
            $goals = $company->goals()
                ->with(['metrics', 'funnelSteps'])
                ->when($request->active !== null, function ($query) use ($request) {
                    return $query->where('is_active', $request->active);
                })
                ->when($request->current, function ($query) {
                    return $query->current();
                })
                ->orderBy('created_at', 'desc')
                ->get();

            // Add progress data for each goal
            $goalsWithProgress = $goals->map(function ($goal) {
                $progress = $this->goalService->getGoalProgress($goal);
                return array_merge($goal->toArray(), [
                    'progress' => $progress,
                    'days_remaining' => $goal->days_remaining,
                    'is_expired' => $goal->is_expired,
                ]);
            });

            return response()->json([
                'success' => true,
                'data' => $goalsWithProgress,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to fetch goals', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Fehler beim Abrufen der Ziele',
            ], 500);
        }
    }

    /**
     * Get goal templates
     */
    public function templates()
    {
        try {
            $templates = $this->goalService->getGoalTemplates();

            return response()->json([
                'success' => true,
                'data' => $templates,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to fetch goal templates', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Fehler beim Abrufen der Vorlagen',
            ], 500);
        }
    }

    /**
     * Get a specific goal
     */
    public function show(Request $request, CompanyGoal $goal)
    {
        try {
            // Ensure goal belongs to user's company
            if ($goal->company_id !== $request->user()->company_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Ziel nicht gefunden',
                ], 404);
            }

            $goal->load(['metrics', 'funnelSteps']);
            $progress = $this->goalService->getGoalProgress($goal);
            $projections = $this->calculationService->calculateProjections($goal);

            return response()->json([
                'success' => true,
                'data' => [
                    'goal' => $goal,
                    'progress' => $progress,
                    'projections' => $projections,
                    'days_remaining' => $goal->days_remaining,
                    'is_expired' => $goal->is_expired,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to fetch goal details', [
                'goal_id' => $goal->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Fehler beim Abrufen der Zieldetails',
            ], 500);
        }
    }

    /**
     * Create a new goal
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'template_type' => 'nullable|string|in:' . implode(',', [
                CompanyGoal::TEMPLATE_MAX_APPOINTMENTS,
                CompanyGoal::TEMPLATE_DATA_COLLECTION,
                CompanyGoal::TEMPLATE_REVENUE_OPTIMIZATION,
                CompanyGoal::TEMPLATE_CUSTOM,
            ]),
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after:start_date',
            'metrics' => 'nullable|array',
            'metrics.*.metric_type' => 'required_with:metrics|string',
            'metrics.*.metric_name' => 'required_with:metrics|string',
            'metrics.*.target_value' => 'required_with:metrics|numeric|min:0',
            'metrics.*.target_unit' => 'required_with:metrics|string',
            'metrics.*.weight' => 'nullable|numeric|min:0|max:10',
            'funnel_steps' => 'nullable|array',
            'funnel_steps.*.step_name' => 'required_with:funnel_steps|string',
            'funnel_steps.*.step_type' => 'required_with:funnel_steps|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validierung fehlgeschlagen',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $company = $request->user()->company;
            $goal = $this->goalService->createGoal($company, $request->all());

            return response()->json([
                'success' => true,
                'message' => 'Ziel erfolgreich erstellt',
                'data' => $goal,
            ], 201);
        } catch (\Exception $e) {
            Log::error('Failed to create goal', [
                'error' => $e->getMessage(),
                'data' => $request->all(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Fehler beim Erstellen des Ziels',
            ], 500);
        }
    }

    /**
     * Update a goal
     */
    public function update(Request $request, CompanyGoal $goal)
    {
        // Ensure goal belongs to user's company
        if ($goal->company_id !== $request->user()->company_id) {
            return response()->json([
                'success' => false,
                'message' => 'Ziel nicht gefunden',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'string|max:255',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
            'start_date' => 'date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'metrics' => 'nullable|array',
            'metrics.*.id' => 'nullable|integer|exists:goal_metrics,id',
            'metrics.*.metric_type' => 'required_with:metrics|string',
            'metrics.*.metric_name' => 'required_with:metrics|string',
            'metrics.*.target_value' => 'required_with:metrics|numeric|min:0',
            'metrics.*.target_unit' => 'required_with:metrics|string',
            'metrics.*.weight' => 'nullable|numeric|min:0|max:10',
            'funnel_steps' => 'nullable|array',
            'funnel_steps.*.id' => 'nullable|integer|exists:goal_funnel_steps,id',
            'funnel_steps.*.step_name' => 'required_with:funnel_steps|string',
            'funnel_steps.*.step_type' => 'required_with:funnel_steps|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validierung fehlgeschlagen',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $goal = $this->goalService->updateGoal($goal, $request->all());

            return response()->json([
                'success' => true,
                'message' => 'Ziel erfolgreich aktualisiert',
                'data' => $goal,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to update goal', [
                'goal_id' => $goal->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Fehler beim Aktualisieren des Ziels',
            ], 500);
        }
    }

    /**
     * Delete a goal
     */
    public function destroy(Request $request, CompanyGoal $goal)
    {
        // Ensure goal belongs to user's company
        if ($goal->company_id !== $request->user()->company_id) {
            return response()->json([
                'success' => false,
                'message' => 'Ziel nicht gefunden',
            ], 404);
        }

        try {
            $this->goalService->deleteGoal($goal);

            return response()->json([
                'success' => true,
                'message' => 'Ziel erfolgreich gelöscht',
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to delete goal', [
                'goal_id' => $goal->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Fehler beim Löschen des Ziels',
            ], 500);
        }
    }

    /**
     * Duplicate a goal
     */
    public function duplicate(Request $request, CompanyGoal $goal)
    {
        // Ensure goal belongs to user's company
        if ($goal->company_id !== $request->user()->company_id) {
            return response()->json([
                'success' => false,
                'message' => 'Ziel nicht gefunden',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'nullable|string|max:255',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validierung fehlgeschlagen',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $overrides = array_filter($request->only(['name', 'start_date', 'end_date']));
            
            // Default name if not provided
            if (!isset($overrides['name'])) {
                $overrides['name'] = $goal->name . ' (Kopie)';
            }

            $newGoal = $this->goalService->duplicateGoal($goal, $overrides);

            return response()->json([
                'success' => true,
                'message' => 'Ziel erfolgreich dupliziert',
                'data' => $newGoal,
            ], 201);
        } catch (\Exception $e) {
            Log::error('Failed to duplicate goal', [
                'goal_id' => $goal->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Fehler beim Duplizieren des Ziels',
            ], 500);
        }
    }

    /**
     * Toggle goal active status
     */
    public function toggleActive(Request $request, CompanyGoal $goal)
    {
        // Ensure goal belongs to user's company
        if ($goal->company_id !== $request->user()->company_id) {
            return response()->json([
                'success' => false,
                'message' => 'Ziel nicht gefunden',
            ], 404);
        }

        try {
            $goal->is_active = !$goal->is_active;
            $goal->save();

            return response()->json([
                'success' => true,
                'message' => $goal->is_active ? 'Ziel aktiviert' : 'Ziel deaktiviert',
                'data' => [
                    'id' => $goal->id,
                    'is_active' => $goal->is_active,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to toggle goal status', [
                'goal_id' => $goal->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Fehler beim Ändern des Zielstatus',
            ], 500);
        }
    }
}
<?php

namespace App\Services;

use App\Models\Company;
use App\Models\CompanyGoal;
use App\Models\GoalMetric;
use App\Models\GoalFunnelStep;
use App\Models\GoalAchievement;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class GoalService
{
    /**
     * Create a new goal for a company
     */
    public function createGoal(Company $company, array $data): CompanyGoal
    {
        return DB::transaction(function () use ($company, $data) {
            // Create the goal
            $goal = $company->goals()->create([
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
                'is_active' => $data['is_active'] ?? true,
                'start_date' => $data['start_date'],
                'end_date' => $data['end_date'],
                'template_type' => $data['template_type'] ?? CompanyGoal::TEMPLATE_CUSTOM,
                'configuration' => $data['configuration'] ?? [],
            ]);

            // If using a template, create default metrics and funnel steps
            if ($goal->template_type !== CompanyGoal::TEMPLATE_CUSTOM) {
                $goal->createDefaultMetrics();
                $goal->createDefaultFunnelSteps();
            }

            // Create custom metrics if provided
            if (isset($data['metrics']) && is_array($data['metrics'])) {
                foreach ($data['metrics'] as $metricData) {
                    $this->createMetric($goal, $metricData);
                }
            }

            // Create custom funnel steps if provided
            if (isset($data['funnel_steps']) && is_array($data['funnel_steps'])) {
                foreach ($data['funnel_steps'] as $index => $stepData) {
                    $this->createFunnelStep($goal, array_merge($stepData, ['step_order' => $index + 1]));
                }
            }

            return $goal->load(['metrics', 'funnelSteps']);
        });
    }

    /**
     * Update an existing goal
     */
    public function updateGoal(CompanyGoal $goal, array $data): CompanyGoal
    {
        return DB::transaction(function () use ($goal, $data) {
            $goal->update([
                'name' => $data['name'] ?? $goal->name,
                'description' => $data['description'] ?? $goal->description,
                'is_active' => $data['is_active'] ?? $goal->is_active,
                'start_date' => $data['start_date'] ?? $goal->start_date,
                'end_date' => $data['end_date'] ?? $goal->end_date,
                'configuration' => $data['configuration'] ?? $goal->configuration,
            ]);

            // Update metrics if provided
            if (isset($data['metrics']) && is_array($data['metrics'])) {
                // Delete existing metrics not in the update
                $metricIds = collect($data['metrics'])->pluck('id')->filter();
                $goal->metrics()->whereNotIn('id', $metricIds)->delete();

                // Update or create metrics
                foreach ($data['metrics'] as $metricData) {
                    if (isset($metricData['id'])) {
                        $metric = $goal->metrics()->find($metricData['id']);
                        if ($metric) {
                            $this->updateMetric($metric, $metricData);
                        }
                    } else {
                        $this->createMetric($goal, $metricData);
                    }
                }
            }

            // Update funnel steps if provided
            if (isset($data['funnel_steps']) && is_array($data['funnel_steps'])) {
                // Delete existing steps not in the update
                $stepIds = collect($data['funnel_steps'])->pluck('id')->filter();
                $goal->funnelSteps()->whereNotIn('id', $stepIds)->delete();

                // Update or create steps
                foreach ($data['funnel_steps'] as $index => $stepData) {
                    $stepData['step_order'] = $index + 1;
                    
                    if (isset($stepData['id'])) {
                        $step = $goal->funnelSteps()->find($stepData['id']);
                        if ($step) {
                            $this->updateFunnelStep($step, $stepData);
                        }
                    } else {
                        $this->createFunnelStep($goal, $stepData);
                    }
                }
            }

            return $goal->load(['metrics', 'funnelSteps']);
        });
    }

    /**
     * Delete a goal
     */
    public function deleteGoal(CompanyGoal $goal): bool
    {
        return DB::transaction(function () use ($goal) {
            // Achievements will be deleted by cascade
            return $goal->delete();
        });
    }

    /**
     * Create a metric for a goal
     */
    public function createMetric(CompanyGoal $goal, array $data): GoalMetric
    {
        return $goal->metrics()->create([
            'metric_type' => $data['metric_type'],
            'metric_name' => $data['metric_name'],
            'description' => $data['description'] ?? null,
            'target_value' => $data['target_value'],
            'target_unit' => $data['target_unit'],
            'weight' => $data['weight'] ?? 1.0,
            'calculation_method' => $data['calculation_method'] ?? null,
            'conditions' => $data['conditions'] ?? null,
            'comparison_operator' => $data['comparison_operator'] ?? GoalMetric::OPERATOR_GTE,
            'is_primary' => $data['is_primary'] ?? false,
        ]);
    }

    /**
     * Update a metric
     */
    public function updateMetric(GoalMetric $metric, array $data): GoalMetric
    {
        $metric->update([
            'metric_type' => $data['metric_type'] ?? $metric->metric_type,
            'metric_name' => $data['metric_name'] ?? $metric->metric_name,
            'description' => $data['description'] ?? $metric->description,
            'target_value' => $data['target_value'] ?? $metric->target_value,
            'target_unit' => $data['target_unit'] ?? $metric->target_unit,
            'weight' => $data['weight'] ?? $metric->weight,
            'calculation_method' => $data['calculation_method'] ?? $metric->calculation_method,
            'conditions' => $data['conditions'] ?? $metric->conditions,
            'comparison_operator' => $data['comparison_operator'] ?? $metric->comparison_operator,
            'is_primary' => $data['is_primary'] ?? $metric->is_primary,
        ]);

        return $metric;
    }

    /**
     * Create a funnel step for a goal
     */
    public function createFunnelStep(CompanyGoal $goal, array $data): GoalFunnelStep
    {
        return $goal->funnelSteps()->create([
            'step_order' => $data['step_order'],
            'step_name' => $data['step_name'],
            'description' => $data['description'] ?? null,
            'step_type' => $data['step_type'],
            'required_fields' => $data['required_fields'] ?? null,
            'conditions' => $data['conditions'] ?? null,
            'expected_conversion_rate' => $data['expected_conversion_rate'] ?? null,
            'is_optional' => $data['is_optional'] ?? false,
        ]);
    }

    /**
     * Update a funnel step
     */
    public function updateFunnelStep(GoalFunnelStep $step, array $data): GoalFunnelStep
    {
        $step->update([
            'step_order' => $data['step_order'] ?? $step->step_order,
            'step_name' => $data['step_name'] ?? $step->step_name,
            'description' => $data['description'] ?? $step->description,
            'step_type' => $data['step_type'] ?? $step->step_type,
            'required_fields' => $data['required_fields'] ?? $step->required_fields,
            'conditions' => $data['conditions'] ?? $step->conditions,
            'expected_conversion_rate' => $data['expected_conversion_rate'] ?? $step->expected_conversion_rate,
            'is_optional' => $data['is_optional'] ?? $step->is_optional,
        ]);

        return $step;
    }

    /**
     * Get active goals for a company
     */
    public function getActiveGoals(Company $company)
    {
        return $company->goals()
            ->active()
            ->current()
            ->with(['metrics', 'funnelSteps'])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Get goal achievement data for a specific period
     */
    public function getGoalAchievements(CompanyGoal $goal, $periodType = GoalAchievement::PERIOD_DAILY, $startDate = null, $endDate = null)
    {
        $query = $goal->achievements()
            ->with('goalMetric')
            ->where('period_type', $periodType);

        if ($startDate) {
            $query->where('period_start', '>=', $startDate);
        }

        if ($endDate) {
            $query->where('period_end', '<=', $endDate);
        }

        return $query->orderBy('period_start', 'desc')->get();
    }

    /**
     * Calculate and record achievement for a goal
     */
    public function recordAchievement(CompanyGoal $goal, $periodType = GoalAchievement::PERIOD_DAILY, $date = null)
    {
        try {
            $achievement = GoalAchievement::recordAchievement($goal, $periodType, $date);
            
            Log::info('Goal achievement recorded', [
                'goal_id' => $goal->id,
                'period_type' => $periodType,
                'achievement_percentage' => $achievement->achievement_percentage,
            ]);

            return $achievement;
        } catch (\Exception $e) {
            Log::error('Failed to record goal achievement', [
                'goal_id' => $goal->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Get funnel data for a goal
     */
    public function getFunnelData(CompanyGoal $goal, $startDate = null, $endDate = null)
    {
        $funnelData = [];
        $previousStepCount = null;
        
        // Ensure funnel steps are loaded with their goal relationship
        $goal->load(['funnelSteps']);

        foreach ($goal->funnelSteps as $step) {
            $stepCount = $step->getStepCount($startDate, $endDate);
            $conversionRate = $step->getConversionRate($previousStepCount);

            $funnelData[] = [
                'step' => $step,
                'count' => $stepCount,
                'conversion_rate' => $conversionRate,
                'percentage' => $previousStepCount ? ($stepCount / $previousStepCount * 100) : 100,
            ];

            $previousStepCount = $stepCount;
        }

        return $funnelData;
    }

    /**
     * Get goal progress summary
     */
    public function getGoalProgress(CompanyGoal $goal)
    {
        $progress = [
            'goal' => $goal,
            'metrics' => [],
            'overall_achievement' => 0,
            'funnel' => $this->getFunnelData($goal),
        ];

        $totalAchievement = 0;
        $totalWeight = 0;

        foreach ($goal->metrics as $metric) {
            $currentValue = $metric->getCurrentValue();
            $achievementPercentage = $metric->getAchievementPercentage($currentValue);

            $progress['metrics'][] = [
                'metric' => $metric,
                'current_value' => $currentValue,
                'formatted_value' => $metric->formatValue($currentValue),
                'target_value' => $metric->target_value,
                'formatted_target' => $metric->formatValue($metric->target_value),
                'achievement_percentage' => $achievementPercentage,
                'is_primary' => $metric->is_primary,
            ];

            $totalAchievement += $achievementPercentage * $metric->weight;
            $totalWeight += $metric->weight;
        }

        $progress['overall_achievement'] = $totalWeight > 0 ? $totalAchievement / $totalWeight : 0;

        return $progress;
    }

    /**
     * Get goal templates
     */
    public function getGoalTemplates()
    {
        return [
            [
                'id' => 'max_appointments',
                'type' => CompanyGoal::TEMPLATE_MAX_APPOINTMENTS,
                'name' => 'Maximale Termine',
                'description' => 'Optimieren Sie Ihre Terminbuchungen und Konversionsraten',
                'default_duration' => 30, // days
                'metrics' => [
                    [
                        'metric_type' => GoalMetric::TYPE_APPOINTMENTS_BOOKED,
                        'metric_name' => 'Termine gebucht',
                        'target_unit' => GoalMetric::UNIT_COUNT,
                        'suggested_target' => 100,
                        'is_primary' => true,
                    ],
                    [
                        'metric_type' => GoalMetric::TYPE_CONVERSION_RATE,
                        'metric_name' => 'Konversionsrate Anruf zu Termin',
                        'target_unit' => GoalMetric::UNIT_PERCENTAGE,
                        'suggested_target' => 30,
                    ],
                ],
                'funnel_steps' => [
                    ['name' => 'Anruf erhalten', 'type' => GoalFunnelStep::TYPE_CALL_RECEIVED],
                    ['name' => 'Anruf angenommen', 'type' => GoalFunnelStep::TYPE_CALL_ANSWERED],
                    ['name' => 'Termin angefragt', 'type' => GoalFunnelStep::TYPE_APPOINTMENT_REQUESTED],
                    ['name' => 'Termin vereinbart', 'type' => GoalFunnelStep::TYPE_APPOINTMENT_SCHEDULED],
                ],
            ],
            [
                'id' => 'data_collection',
                'type' => CompanyGoal::TEMPLATE_DATA_COLLECTION,
                'name' => 'Datensammlung Fokus',
                'description' => 'Vollständige Kundendaten für besseren Service sammeln',
                'default_duration' => 30,
                'metrics' => [
                    [
                        'metric_type' => GoalMetric::TYPE_DATA_COLLECTED,
                        'metric_name' => 'Vollständige Kundendaten',
                        'target_unit' => GoalMetric::UNIT_PERCENTAGE,
                        'suggested_target' => 80,
                        'is_primary' => true,
                    ],
                    [
                        'metric_type' => GoalMetric::TYPE_CALLS_ANSWERED,
                        'metric_name' => 'Anrufe beantwortet',
                        'target_unit' => GoalMetric::UNIT_COUNT,
                        'suggested_target' => 200,
                    ],
                ],
                'funnel_steps' => [
                    ['name' => 'Anruf erhalten', 'type' => GoalFunnelStep::TYPE_CALL_RECEIVED],
                    ['name' => 'Name erfasst', 'type' => GoalFunnelStep::TYPE_DATA_CAPTURED],
                    ['name' => 'Email erfasst', 'type' => GoalFunnelStep::TYPE_EMAIL_CAPTURED],
                    ['name' => 'Telefon erfasst', 'type' => GoalFunnelStep::TYPE_PHONE_CAPTURED],
                    ['name' => 'Adresse erfasst', 'type' => GoalFunnelStep::TYPE_ADDRESS_CAPTURED],
                ],
            ],
            [
                'id' => 'revenue_optimization',
                'type' => CompanyGoal::TEMPLATE_REVENUE_OPTIMIZATION,
                'name' => 'Umsatz-Optimierung',
                'description' => 'Maximieren Sie Ihren Umsatz durch optimierte Prozesse',
                'default_duration' => 90,
                'metrics' => [
                    [
                        'metric_type' => GoalMetric::TYPE_REVENUE_GENERATED,
                        'metric_name' => 'Generierter Umsatz',
                        'target_unit' => GoalMetric::UNIT_CURRENCY,
                        'suggested_target' => 10000,
                        'is_primary' => true,
                    ],
                    [
                        'metric_type' => GoalMetric::TYPE_APPOINTMENTS_COMPLETED,
                        'metric_name' => 'Durchgeführte Termine',
                        'target_unit' => GoalMetric::UNIT_COUNT,
                        'suggested_target' => 50,
                    ],
                ],
                'funnel_steps' => [
                    ['name' => 'Anruf erhalten', 'type' => GoalFunnelStep::TYPE_CALL_RECEIVED],
                    ['name' => 'Termin vereinbart', 'type' => GoalFunnelStep::TYPE_APPOINTMENT_SCHEDULED],
                    ['name' => 'Termin durchgeführt', 'type' => GoalFunnelStep::TYPE_APPOINTMENT_COMPLETED],
                    ['name' => 'Zahlung erhalten', 'type' => GoalFunnelStep::TYPE_PAYMENT_RECEIVED],
                ],
            ],
            [
                'id' => 'data_forwarding_focus',
                'type' => 'data_forwarding_focus',
                'name' => 'Datenerfassung & Weiterleitung',
                'description' => 'Maximale Datenerfassung mit Zustimmung und erfolgreicher Weiterleitung',
                'default_duration' => 30,
                'metrics' => [
                    [
                        'metric_type' => GoalMetric::TYPE_CALLS_RECEIVED,
                        'metric_name' => 'Anrufe angeboten',
                        'target_unit' => GoalMetric::UNIT_COUNT,
                        'suggested_target' => 1000,
                    ],
                    [
                        'metric_type' => GoalMetric::TYPE_CALLS_ANSWERED,
                        'metric_name' => 'Anrufe angenommen',
                        'target_unit' => GoalMetric::UNIT_COUNT,
                        'suggested_target' => 900,
                    ],
                    [
                        'metric_type' => GoalMetric::TYPE_DATA_WITH_CONSENT,
                        'metric_name' => 'Daten mit Zustimmung erfasst',
                        'target_unit' => GoalMetric::UNIT_COUNT,
                        'suggested_target' => 675,
                        'is_primary' => true,
                    ],
                    [
                        'metric_type' => GoalMetric::TYPE_DATA_FORWARDED,
                        'metric_name' => 'Erfolgreich weitergeleitet',
                        'target_unit' => GoalMetric::UNIT_COUNT,
                        'suggested_target' => 640,
                    ],
                ],
                'funnel_steps' => [
                    ['name' => 'Anruf angeboten', 'type' => GoalFunnelStep::TYPE_CALL_RECEIVED],
                    ['name' => 'Anruf angenommen', 'type' => GoalFunnelStep::TYPE_CALL_ANSWERED],
                    ['name' => 'Zustimmung erhalten', 'type' => GoalFunnelStep::TYPE_CONSENT_GIVEN],
                    ['name' => 'Daten weitergeleitet', 'type' => GoalFunnelStep::TYPE_DATA_FORWARDED],
                ],
            ],
        ];
    }

    /**
     * Duplicate a goal
     */
    public function duplicateGoal(CompanyGoal $goal, array $overrides = []): CompanyGoal
    {
        $data = array_merge($goal->toArray(), $overrides);
        
        // Remove fields that shouldn't be duplicated
        unset($data['id'], $data['created_at'], $data['updated_at']);
        
        // Load relationships
        $goal->load(['metrics', 'funnelSteps']);
        
        // Prepare metrics and funnel steps
        $data['metrics'] = $goal->metrics->map(function ($metric) {
            $metricData = $metric->toArray();
            unset($metricData['id'], $metricData['company_goal_id'], $metricData['created_at'], $metricData['updated_at']);
            return $metricData;
        })->toArray();
        
        $data['funnel_steps'] = $goal->funnelSteps->map(function ($step) {
            $stepData = $step->toArray();
            unset($stepData['id'], $stepData['company_goal_id'], $stepData['created_at'], $stepData['updated_at']);
            return $stepData;
        })->toArray();
        
        return $this->createGoal($goal->company, $data);
    }
}
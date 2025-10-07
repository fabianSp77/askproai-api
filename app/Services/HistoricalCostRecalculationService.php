<?php

namespace App\Services;

use App\Models\Call;
use App\Models\CurrencyExchangeRate;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class HistoricalCostRecalculationService
{
    private string $batchId;
    private ExchangeRateService $exchangeService;

    public function __construct()
    {
        $this->batchId = 'batch_' . now()->format('Ymd_His');
        $this->exchangeService = app(ExchangeRateService::class);
    }

    /**
     * Recalculate costs for a single call
     *
     * @param Call $call
     * @param bool $dryRun If true, don't write to database
     * @return array Migration result
     */
    public function recalculateCallCost(Call $call, bool $dryRun = false): array
    {
        // 1. Validate prerequisites
        if (empty($call->cost_breakdown)) {
            return $this->result('skipped', 'cost_breakdown_null', $call);
        }

        try {
            $costBreakdown = is_string($call->cost_breakdown)
                ? json_decode($call->cost_breakdown, true)
                : $call->cost_breakdown;

            if (!is_array($costBreakdown)) {
                return $this->result('error', 'invalid_json', $call, error: 'cost_breakdown is not valid JSON');
            }

            // 2. Extract combined_cost safely
            $combinedCostUsd = $this->extractCombinedCost($costBreakdown);

            if ($combinedCostUsd === null) {
                return $this->result('skipped', 'combined_cost_missing', $call);
            }

            // 3. Validate reasonable range (outlier detection)
            // Increased threshold to $50 as longer calls naturally cost more
            if ($combinedCostUsd > 50.0) {
                Log::warning("Outlier detected - cost > $50", [
                    'call_id' => $call->id,
                    'combined_cost' => $combinedCostUsd
                ]);
                return $this->result('flagged', 'outlier_cost', $call, [
                    'suggested_cost_usd' => $combinedCostUsd
                ]);
            }

            if ($combinedCostUsd < 0) {
                return $this->result('error', 'negative_cost', $call, error: 'Negative cost detected');
            }

            // 4. Determine correct exchange rate
            $exchangeRate = $this->determineExchangeRate($call);

            // 5. Calculate EUR amount
            $costEurCents = (int) round($combinedCostUsd * $exchangeRate * 100);

            // 6. Prepare update data
            $oldValues = [
                'retell_cost_usd' => $call->retell_cost_usd,
                'retell_cost_eur_cents' => $call->retell_cost_eur_cents,
                'base_cost' => $call->base_cost,
                'exchange_rate_used' => $call->exchange_rate_used,
            ];

            $newValues = [
                'retell_cost_usd' => $combinedCostUsd,
                'retell_cost_eur_cents' => $costEurCents,
                'base_cost' => $costEurCents, // base_cost = EUR cents
                'exchange_rate_used' => $exchangeRate,
                'total_external_cost_eur_cents' => $costEurCents,
            ];

            // Calculate delta for reporting
            $delta = [
                'usd' => $newValues['retell_cost_usd'] - ($oldValues['retell_cost_usd'] ?? 0),
                'eur_cents' => $newValues['retell_cost_eur_cents'] - ($oldValues['retell_cost_eur_cents'] ?? 0),
            ];

            // 7. Execute update (skip if dry-run)
            if (!$dryRun) {
                DB::transaction(function () use ($call, $oldValues, $newValues, $costBreakdown) {
                    // Update call
                    $call->update($newValues);

                    // Log to audit table
                    DB::table('call_cost_migration_log')->insert([
                        'call_id' => $call->id,
                        'migration_batch' => $this->batchId,
                        'old_retell_cost_usd' => $oldValues['retell_cost_usd'],
                        'old_retell_cost_eur_cents' => $oldValues['retell_cost_eur_cents'],
                        'old_base_cost' => $oldValues['base_cost'],
                        'old_exchange_rate_used' => $oldValues['exchange_rate_used'],
                        'new_retell_cost_usd' => $newValues['retell_cost_usd'],
                        'new_retell_cost_eur_cents' => $newValues['retell_cost_eur_cents'],
                        'new_base_cost' => $newValues['base_cost'],
                        'new_exchange_rate_used' => $newValues['exchange_rate_used'],
                        'cost_breakdown_source' => json_encode($costBreakdown),
                        'migration_reason' => 'historical_cost_correction',
                        'status' => 'success',
                        'migrated_at' => now(),
                    ]);
                });

                Log::info('Call cost recalculated', [
                    'call_id' => $call->id,
                    'old_usd' => $oldValues['retell_cost_usd'],
                    'new_usd' => $newValues['retell_cost_usd'],
                    'delta_eur_cents' => $delta['eur_cents']
                ]);
            }

            return $this->result('success', 'recalculated', $call, [
                'old_values' => $oldValues,
                'new_values' => $newValues,
                'delta' => $delta
            ]);

        } catch (\Exception $e) {
            Log::error("Migration error for call {$call->id}", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            // Log error to audit table even on failure
            if (!$dryRun) {
                try {
                    DB::table('call_cost_migration_log')->insert([
                        'call_id' => $call->id,
                        'migration_batch' => $this->batchId,
                        'migration_reason' => 'historical_cost_correction',
                        'status' => 'error',
                        'error_message' => $e->getMessage(),
                        'migrated_at' => now(),
                    ]);
                } catch (\Exception $logError) {
                    // Fail silently on audit log error
                }
            }

            return $this->result('error', 'exception', $call, error: $e->getMessage());
        }
    }

    /**
     * Extract combined_cost from cost_breakdown JSON
     *
     * @param array $costBreakdown
     * @return float|null
     */
    private function extractCombinedCost(array $costBreakdown): ?float
    {
        // Primary: direct combined_cost field
        // CRITICAL: Retell stores combined_cost in CENTS, not DOLLARS
        if (isset($costBreakdown['combined_cost'])) {
            return (float) $costBreakdown['combined_cost'] / 100;
        }

        // Fallback: Old structure with base.retell_api (in EUR cents)
        if (isset($costBreakdown['base']['retell_api'])) {
            // Convert EUR cents to USD
            $eurCents = (float) $costBreakdown['base']['retell_api'];
            $exchangeRate = CurrencyExchangeRate::getCurrentRate('USD', 'EUR') ?? 0.92;
            // EUR cents → EUR → USD
            return ($eurCents / 100) / $exchangeRate;
        }

        // Fallback: Calculate from components if available
        if (isset($costBreakdown['llm_cost'], $costBreakdown['voice_cost'])) {
            return (float) $costBreakdown['llm_cost'] + (float) $costBreakdown['voice_cost'];
        }

        return null;
    }

    /**
     * Determine correct exchange rate for the call
     *
     * @param Call $call
     * @return float
     */
    private function determineExchangeRate(Call $call): float
    {
        // 1. Use existing exchange_rate_used if present and reasonable
        if ($call->exchange_rate_used && $call->exchange_rate_used > 0.5 && $call->exchange_rate_used < 1.5) {
            return (float) $call->exchange_rate_used;
        }

        // 2. Fetch historical rate for call creation date
        try {
            $historicalRate = $this->exchangeService->getHistoricalRate('USD', 'EUR', $call->created_at);

            if ($historicalRate) {
                return $historicalRate;
            }
        } catch (\Exception $e) {
            Log::debug("Could not fetch historical rate for call {$call->id}", [
                'date' => $call->created_at,
                'error' => $e->getMessage()
            ]);
        }

        // 3. Fallback: current rate
        return CurrencyExchangeRate::getCurrentRate('USD', 'EUR') ?? 0.92;
    }

    /**
     * Get batch ID for this migration run
     *
     * @return string
     */
    public function getBatchId(): string
    {
        return $this->batchId;
    }

    /**
     * Helper to create result array
     *
     * @param string $status
     * @param string $reason
     * @param Call $call
     * @param array $data
     * @param string|null $error
     * @return array
     */
    private function result(string $status, string $reason, Call $call, array $data = [], ?string $error = null): array
    {
        return array_merge([
            'status' => $status,
            'reason' => $reason,
            'call_id' => $call->id,
            'error' => $error,
        ], $data);
    }
}

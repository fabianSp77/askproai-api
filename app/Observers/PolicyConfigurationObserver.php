<?php

namespace App\Observers;

use App\Events\ConfigurationCreated;
use App\Events\ConfigurationDeleted;
use App\Events\ConfigurationUpdated;
use App\Models\PolicyConfiguration;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class PolicyConfigurationObserver
{
    /**
     * Handle the PolicyConfiguration "creating" event.
     */
    public function creating(PolicyConfiguration $policyConfiguration): void
    {
        $this->validatePolicyConfig($policyConfiguration);
        $this->sanitizeConfigValues($policyConfiguration);

        // Ensure company_id is set from auth user if not already set
        if (!$policyConfiguration->company_id && auth()->check()) {
            $policyConfiguration->company_id = auth()->user()->company_id;
        }
    }

    /**
     * Handle the PolicyConfiguration "created" event.
     */
    public function created(PolicyConfiguration $policyConfiguration): void
    {
        try {
            ConfigurationCreated::dispatch(
                (string) $policyConfiguration->company_id,
                PolicyConfiguration::class,
                $policyConfiguration->id,
                [
                    'policy_type' => $policyConfiguration->policy_type,
                    'configurable_type' => $policyConfiguration->configurable_type,
                    'configurable_id' => $policyConfiguration->configurable_id,
                    'config' => $policyConfiguration->config,
                    'is_override' => $policyConfiguration->is_override,
                ],
                auth()->id(),
                $this->getSource()
            );
        } catch (\Exception $e) {
            Log::error('Failed to dispatch ConfigurationCreated event', [
                'error' => $e->getMessage(),
                'model_id' => $policyConfiguration->id,
            ]);
        }
    }

    /**
     * Handle the PolicyConfiguration "updating" event.
     */
    public function updating(PolicyConfiguration $policyConfiguration): void
    {
        if ($policyConfiguration->isDirty('config') || $policyConfiguration->isDirty('policy_type')) {
            $this->validatePolicyConfig($policyConfiguration);
            $this->sanitizeConfigValues($policyConfiguration);
        }
    }

    /**
     * Handle the PolicyConfiguration "updated" event.
     */
    public function updated(PolicyConfiguration $policyConfiguration): void
    {
        try {
            $changes = $policyConfiguration->getChanges();
            $original = $policyConfiguration->getRawOriginal();

            // Fire an event for each changed field
            foreach ($changes as $key => $newValue) {
                if ($key === 'updated_at') {
                    continue; // Skip timestamp changes
                }

                $oldValue = $original[$key] ?? null;

                ConfigurationUpdated::dispatch(
                    (string) $policyConfiguration->company_id,
                    PolicyConfiguration::class,
                    $policyConfiguration->id,
                    $key,
                    $oldValue,
                    $newValue,
                    auth()->id(),
                    $this->getSource()
                );
            }
        } catch (\Exception $e) {
            Log::error('Failed to dispatch ConfigurationUpdated event', [
                'error' => $e->getMessage(),
                'model_id' => $policyConfiguration->id,
            ]);
        }
    }

    /**
     * Handle the PolicyConfiguration "deleted" event.
     */
    public function deleted(PolicyConfiguration $policyConfiguration): void
    {
        try {
            ConfigurationDeleted::dispatch(
                (string) $policyConfiguration->company_id,
                PolicyConfiguration::class,
                $policyConfiguration->id,
                [
                    'policy_type' => $policyConfiguration->policy_type,
                    'configurable_type' => $policyConfiguration->configurable_type,
                    'configurable_id' => $policyConfiguration->configurable_id,
                    'config' => $policyConfiguration->config,
                ],
                true,
                auth()->id(),
                $this->getSource()
            );
        } catch (\Exception $e) {
            Log::error('Failed to dispatch ConfigurationDeleted event', [
                'error' => $e->getMessage(),
                'model_id' => $policyConfiguration->id,
            ]);
        }
    }

    /**
     * Handle the PolicyConfiguration "forceDeleted" event.
     */
    public function forceDeleted(PolicyConfiguration $policyConfiguration): void
    {
        try {
            ConfigurationDeleted::dispatch(
                (string) $policyConfiguration->company_id,
                PolicyConfiguration::class,
                $policyConfiguration->id,
                [
                    'policy_type' => $policyConfiguration->policy_type,
                    'config' => $policyConfiguration->config,
                ],
                false,
                auth()->id(),
                $this->getSource()
            );
        } catch (\Exception $e) {
            Log::error('Failed to dispatch ConfigurationDeleted event (force)', [
                'error' => $e->getMessage(),
                'model_id' => $policyConfiguration->id,
            ]);
        }
    }

    /**
     * Validate policy configuration based on policy_type.
     */
    protected function validatePolicyConfig(PolicyConfiguration $policyConfiguration): void
    {
        $config = is_array($policyConfiguration->config)
            ? $policyConfiguration->config
            : json_decode($policyConfiguration->config, true);

        if (!is_array($config)) {
            throw ValidationException::withMessages([
                'config' => 'Policy configuration must be a valid JSON object.',
            ]);
        }

        $schema = $this->getSchemaForPolicyType($policyConfiguration->policy_type);

        foreach ($schema['required'] as $field) {
            if (!isset($config[$field])) {
                throw ValidationException::withMessages([
                    'config' => "Required field '{$field}' is missing for {$policyConfiguration->policy_type} policy.",
                ]);
            }
        }

        foreach ($config as $key => $value) {
            if (!isset($schema['fields'][$key])) {
                throw ValidationException::withMessages([
                    'config' => "Unknown field '{$key}' for {$policyConfiguration->policy_type} policy.",
                ]);
            }

            $expectedType = $schema['fields'][$key];
            if (!$this->validateType($value, $expectedType)) {
                throw ValidationException::withMessages([
                    'config' => "Field '{$key}' must be of type {$expectedType}.",
                ]);
            }
        }
    }

    /**
     * Sanitize config values to prevent XSS.
     */
    protected function sanitizeConfigValues(PolicyConfiguration $policyConfiguration): void
    {
        $config = is_array($policyConfiguration->config)
            ? $policyConfiguration->config
            : json_decode($policyConfiguration->config, true);

        if (!is_array($config)) {
            return;
        }

        array_walk_recursive($config, function (&$value) {
            if (is_string($value)) {
                $value = strip_tags($value);
                $value = htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
            }
        });

        $policyConfiguration->config = $config;
    }

    /**
     * Get JSON schema for policy type.
     */
    protected function getSchemaForPolicyType(string $policyType): array
    {
        return match ($policyType) {
            'cancellation' => [
                'required' => ['hours_before', 'fee_percentage'],
                'fields' => [
                    'hours_before' => 'integer',
                    'fee_percentage' => 'numeric',
                    'max_cancellations_per_month' => 'integer',
                    'require_reason' => 'boolean',
                    'allow_free_cancellation_window' => 'boolean',
                    'free_cancellation_hours' => 'integer',
                ],
            ],
            'reschedule' => [
                'required' => ['hours_before', 'max_reschedules_per_appointment'],
                'fields' => [
                    'hours_before' => 'integer',
                    'max_reschedules_per_appointment' => 'integer',
                    'fee_percentage' => 'numeric',
                    'require_reason' => 'boolean',
                    'allow_same_day_reschedule' => 'boolean',
                ],
            ],
            'recurring' => [
                'required' => ['allow_partial_cancel'],
                'fields' => [
                    'allow_partial_cancel' => 'boolean',
                    'require_full_series_notice' => 'boolean',
                    'partial_cancel_fee_percentage' => 'numeric',
                    'full_series_hours_before' => 'integer',
                    'min_appointments_to_keep' => 'integer',
                ],
            ],
            'gateway_mode' => [
                'required' => ['mode'],
                'fields' => [
                    'mode' => 'string', // appointment, service_desk, hybrid
                ],
            ],
            default => throw ValidationException::withMessages([
                'policy_type' => "Invalid policy type: {$policyType}",
            ]),
        };
    }

    /**
     * Validate value type matches expected type.
     */
    protected function validateType($value, string $expectedType): bool
    {
        return match ($expectedType) {
            'integer' => is_int($value),
            'numeric' => is_numeric($value),
            'boolean' => is_bool($value),
            'string' => is_string($value),
            'array' => is_array($value),
            default => false,
        };
    }

    /**
     * Determine the source of the change
     */
    private function getSource(): string
    {
        if (app()->runningInConsole()) {
            return 'console';
        }

        if (request()->is('api/*')) {
            return 'api';
        }

        return 'ui';
    }
}

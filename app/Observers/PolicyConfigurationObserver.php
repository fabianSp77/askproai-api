<?php

namespace App\Observers;

use App\Models\PolicyConfiguration;
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
}

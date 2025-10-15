<?php

namespace App\Listeners;

use App\Events\ConfigurationUpdated;
use App\Events\ConfigurationCreated;
use App\Events\ConfigurationDeleted;
use Illuminate\Support\Facades\Log;
use Spatie\Activitylog\Facades\LogActivity;

/**
 * Log configuration changes for audit trail
 *
 * This listener creates detailed audit logs for all configuration changes
 * using the spatie/laravel-activitylog package.
 *
 * @package App\Listeners
 */
class LogConfigurationChange
{
    /**
     * Handle ConfigurationUpdated event
     */
    public function handleUpdated(ConfigurationUpdated $event): void
    {
        try {
            $properties = [
                'config_key' => $event->configKey,
                'old_value' => $event->getMaskedOldValue(),
                'new_value' => $event->getMaskedNewValue(),
                'source' => $event->source,
                'model_type' => $event->modelType,
                'model_id' => $event->modelId,
                'is_sensitive' => $event->isSensitive(),
            ];

            // Use spatie/laravel-activitylog if available
            if (class_exists(LogActivity::class)) {
                activity()
                    ->causedBy($event->userId)
                    ->performedOn($this->getModel($event->modelType, $event->modelId))
                    ->withProperties(array_merge($properties, $event->metadata))
                    ->log("Configuration '{$event->configKey}' updated");
            }

            // Also log to Laravel log for immediate visibility
            Log::info('Configuration updated', array_merge($properties, [
                'company_id' => $event->companyId,
                'user_id' => $event->userId,
            ]));

            // Alert for sensitive changes
            if ($event->isSensitive()) {
                Log::warning('Sensitive configuration updated', [
                    'company_id' => $event->companyId,
                    'config_key' => $event->configKey,
                    'user_id' => $event->userId,
                    'ip' => $event->metadata['ip'] ?? null,
                ]);
            }
        } catch (\Exception $e) {
            // Log error but don't fail the request
            Log::error('Failed to log configuration change', [
                'error' => $e->getMessage(),
                'event' => $event->toArray(),
            ]);
        }
    }

    /**
     * Handle ConfigurationCreated event
     */
    public function handleCreated(ConfigurationCreated $event): void
    {
        try {
            $properties = [
                'config_data' => $event->configData,
                'source' => $event->source,
                'model_type' => $event->modelType,
                'model_id' => $event->modelId,
            ];

            if (class_exists(LogActivity::class)) {
                activity()
                    ->causedBy($event->userId)
                    ->performedOn($this->getModel($event->modelType, $event->modelId))
                    ->withProperties(array_merge($properties, $event->metadata))
                    ->log("Configuration created");
            }

            Log::info('Configuration created', array_merge($properties, [
                'company_id' => $event->companyId,
                'user_id' => $event->userId,
            ]));
        } catch (\Exception $e) {
            Log::error('Failed to log configuration creation', [
                'error' => $e->getMessage(),
                'event' => $event->toArray(),
            ]);
        }
    }

    /**
     * Handle ConfigurationDeleted event
     */
    public function handleDeleted(ConfigurationDeleted $event): void
    {
        try {
            $properties = [
                'config_data' => $event->configData,
                'source' => $event->source,
                'model_type' => $event->modelType,
                'model_id' => $event->modelId,
                'is_soft_delete' => $event->isSoftDelete,
            ];

            if (class_exists(LogActivity::class)) {
                activity()
                    ->causedBy($event->userId)
                    ->withProperties(array_merge($properties, $event->metadata))
                    ->log("Configuration deleted" . ($event->isSoftDelete ? ' (soft delete)' : ' (permanent)'));
            }

            Log::warning('Configuration deleted', array_merge($properties, [
                'company_id' => $event->companyId,
                'user_id' => $event->userId,
            ]));
        } catch (\Exception $e) {
            Log::error('Failed to log configuration deletion', [
                'error' => $e->getMessage(),
                'event' => $event->toArray(),
            ]);
        }
    }

    /**
     * Get model instance for activity log
     */
    private function getModel(string $modelType, int|string $modelId): ?object
    {
        try {
            if (!class_exists($modelType)) {
                return null;
            }

            return $modelType::find($modelId);
        } catch (\Exception $e) {
            return null;
        }
    }
}

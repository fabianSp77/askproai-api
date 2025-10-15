<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Event fired when a configuration is updated
 *
 * This event is fired whenever any configuration field is changed,
 * allowing for cache invalidation, audit logging, and real-time UI updates.
 *
 * @package App\Events
 */
class ConfigurationUpdated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * @var string Company ID that owns this configuration
     */
    public string $companyId;

    /**
     * @var string Model type (PolicyConfiguration, Company, Branch, etc.)
     */
    public string $modelType;

    /**
     * @var int|string Model ID
     */
    public int|string $modelId;

    /**
     * @var string Configuration key that was changed
     */
    public string $configKey;

    /**
     * @var mixed Old value before update
     */
    public mixed $oldValue;

    /**
     * @var mixed New value after update
     */
    public mixed $newValue;

    /**
     * @var int|null User ID who made the change
     */
    public ?int $userId;

    /**
     * @var string Source of the change (ui|api|console|job)
     */
    public string $source;

    /**
     * @var array Additional metadata
     */
    public array $metadata;

    /**
     * Create a new event instance.
     */
    public function __construct(
        string $companyId,
        string $modelType,
        int|string $modelId,
        string $configKey,
        mixed $oldValue,
        mixed $newValue,
        ?int $userId = null,
        string $source = 'ui',
        array $metadata = []
    ) {
        $this->companyId = $companyId;
        $this->modelType = $modelType;
        $this->modelId = $modelId;
        $this->configKey = $configKey;
        $this->oldValue = $oldValue;
        $this->newValue = $newValue;
        $this->userId = $userId ?? auth()->id();
        $this->source = $source;
        $this->metadata = array_merge([
            'ip' => request()?->ip(),
            'user_agent' => request()?->userAgent(),
            'timestamp' => now()->toISOString(),
        ], $metadata);
    }

    /**
     * Check if this is a sensitive configuration change
     */
    public function isSensitive(): bool
    {
        $sensitiveKeys = ['api_key', 'secret', 'password', 'token', 'webhook_signing_secret'];

        foreach ($sensitiveKeys as $sensitive) {
            if (str_contains(strtolower($this->configKey), $sensitive)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get masked old value for logging
     */
    public function getMaskedOldValue(): mixed
    {
        if (!$this->isSensitive()) {
            return $this->oldValue;
        }

        return is_string($this->oldValue)
            ? '••••••••' . substr($this->oldValue, -4)
            : '[REDACTED]';
    }

    /**
     * Get masked new value for logging
     */
    public function getMaskedNewValue(): mixed
    {
        if (!$this->isSensitive()) {
            return $this->newValue;
        }

        return is_string($this->newValue)
            ? '••••••••' . substr($this->newValue, -4)
            : '[REDACTED]';
    }

    /**
     * Get cache tags that should be invalidated
     */
    public function getCacheTags(): array
    {
        return [
            "company:{$this->companyId}",
            "config:{$this->configKey}",
            "model:{$this->modelType}:{$this->modelId}",
        ];
    }

    /**
     * Convert event to array for logging
     */
    public function toArray(): array
    {
        return [
            'company_id' => $this->companyId,
            'model_type' => $this->modelType,
            'model_id' => $this->modelId,
            'config_key' => $this->configKey,
            'old_value' => $this->getMaskedOldValue(),
            'new_value' => $this->getMaskedNewValue(),
            'user_id' => $this->userId,
            'source' => $this->source,
            'metadata' => $this->metadata,
        ];
    }
}

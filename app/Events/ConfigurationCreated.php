<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Event fired when a new configuration is created
 *
 * @package App\Events
 */
class ConfigurationCreated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public string $companyId;
    public string $modelType;
    public int|string $modelId;
    public array $configData;
    public ?int $userId;
    public string $source;
    public array $metadata;

    /**
     * Create a new event instance.
     */
    public function __construct(
        string $companyId,
        string $modelType,
        int|string $modelId,
        array $configData,
        ?int $userId = null,
        string $source = 'ui',
        array $metadata = []
    ) {
        $this->companyId = $companyId;
        $this->modelType = $modelType;
        $this->modelId = $modelId;
        $this->configData = $configData;
        $this->userId = $userId ?? auth()->id();
        $this->source = $source;
        $this->metadata = array_merge([
            'ip' => request()?->ip(),
            'user_agent' => request()?->userAgent(),
            'timestamp' => now()->toISOString(),
        ], $metadata);
    }

    /**
     * Get cache tags that should be invalidated
     */
    public function getCacheTags(): array
    {
        return [
            "company:{$this->companyId}",
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
            'config_data' => $this->configData,
            'user_id' => $this->userId,
            'source' => $this->source,
            'metadata' => $this->metadata,
        ];
    }
}

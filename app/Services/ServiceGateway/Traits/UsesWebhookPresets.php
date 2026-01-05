<?php

namespace App\Services\ServiceGateway\Traits;

use App\Models\ServiceCase;
use App\Models\ServiceOutputConfiguration;
use App\Models\WebhookPreset;
use App\Services\ServiceGateway\WebhookTemplateEngine;
use App\Services\ServiceGateway\TemplateRenderException;
use Illuminate\Support\Facades\Log;

/**
 * Trait UsesWebhookPresets
 *
 * Provides webhook preset template rendering capabilities for output handlers.
 * Use this trait in WebhookOutputHandler to enable preset-based payload generation.
 *
 * Integration example:
 * ```php
 * class WebhookOutputHandler implements OutputHandlerInterface
 * {
 *     use UsesWebhookPresets;
 *
 *     private function buildPayload(ServiceCase $case, ServiceOutputConfiguration $config): array
 *     {
 *         // Try preset first, fallback to existing logic
 *         $presetPayload = $this->buildPayloadFromPreset($case, $config);
 *         if ($presetPayload !== null) {
 *             return $presetPayload;
 *         }
 *
 *         // Existing payload logic...
 *     }
 * }
 * ```
 */
trait UsesWebhookPresets
{
    /**
     * Cached template engine instance
     */
    private ?WebhookTemplateEngine $templateEngine = null;

    /**
     * Build payload from a linked webhook preset.
     *
     * Returns null if no preset is configured or if rendering fails,
     * allowing fallback to default payload logic.
     *
     * @param ServiceCase $case The service case to render
     * @param ServiceOutputConfiguration $config The output configuration
     * @param array $overrides Additional values to override
     * @return array|null Rendered payload or null for fallback
     */
    protected function buildPayloadFromPreset(
        ServiceCase $case,
        ServiceOutputConfiguration $config,
        array $overrides = []
    ): ?array {
        // Check if preset is configured
        if (!$config->usesPreset()) {
            return null;
        }

        // Load preset if not already loaded
        if (!$config->relationLoaded('webhookPreset')) {
            $config->load('webhookPreset');
        }

        $preset = $config->webhookPreset;
        if (!$preset || !$preset->is_active) {
            Log::warning('[UsesWebhookPresets] Preset not found or inactive', [
                'case_id' => $case->id,
                'config_id' => $config->id,
                'preset_id' => $config->webhook_preset_id,
            ]);
            return null;
        }

        try {
            $engine = $this->getTemplateEngine();
            $payload = $engine->render($preset, $case, $overrides);

            // Log validation warnings but proceed with delivery
            if ($engine->hasErrors()) {
                Log::warning('[UsesWebhookPresets] Render completed with warnings', [
                    'case_id' => $case->id,
                    'preset' => $preset->slug,
                    'errors' => $engine->getErrors(),
                ]);
            }

            Log::info('[UsesWebhookPresets] Preset payload rendered', [
                'case_id' => $case->id,
                'preset' => $preset->slug,
                'target_system' => $preset->target_system,
                'payload_size' => strlen(json_encode($payload)),
            ]);

            return $payload;

        } catch (TemplateRenderException $e) {
            Log::error('[UsesWebhookPresets] Failed to render preset', [
                'case_id' => $case->id,
                'preset_id' => $preset->id,
                'preset_slug' => $preset->slug,
                'error' => $e->getMessage(),
            ]);

            // Return null to trigger fallback to default payload
            return null;
        }
    }

    /**
     * Build headers from a linked webhook preset.
     *
     * Returns null if no preset is configured or no headers template exists.
     *
     * @param ServiceOutputConfiguration $config The output configuration
     * @return array|null Headers array or null for fallback
     */
    protected function buildHeadersFromPreset(ServiceOutputConfiguration $config): ?array
    {
        if (!$config->usesPreset()) {
            return null;
        }

        $preset = $config->webhookPreset;
        if (!$preset) {
            return null;
        }

        return $preset->headers_template;
    }

    /**
     * Get the template engine instance.
     *
     * @return WebhookTemplateEngine
     */
    protected function getTemplateEngine(): WebhookTemplateEngine
    {
        if ($this->templateEngine === null) {
            $this->templateEngine = app(WebhookTemplateEngine::class);
        }

        return $this->templateEngine;
    }

    /**
     * Set a custom template engine (for testing).
     *
     * @param WebhookTemplateEngine $engine
     * @return void
     */
    public function setTemplateEngine(WebhookTemplateEngine $engine): void
    {
        $this->templateEngine = $engine;
    }

    /**
     * Validate a preset template against case data.
     *
     * Useful for pre-flight checks before delivery.
     *
     * @param WebhookPreset $preset The preset to validate
     * @param ServiceCase $case The case to validate against
     * @return array Validation result ['valid' => bool, 'errors' => array]
     */
    protected function validatePresetForCase(WebhookPreset $preset, ServiceCase $case): array
    {
        // First validate template syntax
        $engine = $this->getTemplateEngine();
        $syntaxResult = $engine->validate($preset->payload_template);

        if (!$syntaxResult['valid']) {
            return $syntaxResult;
        }

        // Then validate required variables
        $testContext = [
            'case' => [
                'subject' => $case->subject,
                'description' => $case->description,
            ],
        ];

        $missing = $preset->validateRequiredVariables($testContext);

        return [
            'valid' => empty($missing),
            'errors' => array_map(fn($v) => "Missing required: {$v}", $missing),
        ];
    }

    /**
     * Get available presets for a company.
     *
     * @param int $companyId
     * @param string|null $targetSystem Filter by target system
     * @return \Illuminate\Database\Eloquent\Collection
     */
    protected function getAvailablePresets(int $companyId, ?string $targetSystem = null): \Illuminate\Database\Eloquent\Collection
    {
        $query = WebhookPreset::availableFor($companyId);

        if ($targetSystem) {
            $query->forSystem($targetSystem);
        }

        return $query->orderBy('name')->get();
    }
}

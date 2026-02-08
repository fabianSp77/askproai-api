<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Company;
use App\Models\ServiceCaseCategory;
use App\Models\ServiceOutputConfiguration;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ServiceOutputConfiguration>
 */
class ServiceOutputConfigurationFactory extends Factory
{
    protected $model = ServiceOutputConfiguration::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'name' => $this->faker->randomElement([
                'IT-Systemhaus Support',
                'Netzwerk Support Email',
                'Software Support Webhook',
                'Hardware Support Output',
            ]),
            'output_type' => ServiceOutputConfiguration::TYPE_EMAIL,
            'email_recipients' => ['support@example.com'],
            'is_active' => true,
            'retry_on_failure' => true,
            'email_audio_option' => 'none',
            'include_transcript' => true,
            'include_summary' => true,
        ];
    }

    /**
     * Create a Visionary Data configuration (technical mode).
     */
    public function visionaryData(): static
    {
        return $this->state([
            'name' => 'Visionary Data Backup Email',
            'output_type' => ServiceOutputConfiguration::TYPE_EMAIL,
            'email_audio_option' => 'none',
            'include_transcript' => true,
            'include_summary' => true,
        ]);
    }

    /**
     * Create an IT-Systemhaus configuration (admin mode).
     */
    public function itSystemhaus(): static
    {
        return $this->state([
            'name' => 'IT-Systemhaus Support',
            'output_type' => ServiceOutputConfiguration::TYPE_EMAIL,
            'email_audio_option' => 'link',
            'include_transcript' => true,
            'include_summary' => true,
        ]);
    }

    /**
     * Create a configuration with audio link.
     */
    public function withAudioLink(): static
    {
        return $this->state([
            'email_audio_option' => 'link',
        ]);
    }

    /**
     * Create a configuration with audio attachment.
     */
    public function withAudioAttachment(): static
    {
        return $this->state([
            'email_audio_option' => 'attachment',
        ]);
    }

    /**
     * Create a configuration without transcript.
     */
    public function withoutTranscript(): static
    {
        return $this->state([
            'include_transcript' => false,
        ]);
    }

    /**
     * Create a webhook configuration.
     */
    public function webhook(): static
    {
        return $this->state([
            'output_type' => ServiceOutputConfiguration::TYPE_WEBHOOK,
            'webhook_url' => $this->faker->url(),
            'webhook_headers' => ['Content-Type' => 'application/json'],
        ]);
    }

    /**
     * Create an inactive configuration.
     */
    public function inactive(): static
    {
        return $this->state([
            'is_active' => false,
        ]);
    }

    /**
     * Create category-specific output configuration.
     *
     * Supports category types:
     * - security: Critical alert with enrichment wait
     * - infrastructure: High priority for network/server issues
     * - application: Standard support for M365/UC
     * - general: Standard inquiry handling
     *
     * @param string $categoryType Type of category (security|infrastructure|application|general)
     * @param Company $company Company to associate with
     * @param string|null $customName Optional custom name (defaults to template-based name)
     * @return ServiceOutputConfiguration
     */
    public static function forCategory(string $categoryType, Company $company, ?string $customName = null): ServiceOutputConfiguration
    {
        $templates = [
            'security' => [
                'name' => $customName ?? "Security Incident - Critical Alert - {$company->name}",
                'email_audio_option' => 'link',
                'email_show_admin_link' => true,
                'wait_for_enrichment' => true,
                'enrichment_timeout_seconds' => 300, // 5 minutes for critical
                'include_transcript' => true,
                'include_summary' => true,
            ],
            'infrastructure' => [
                'name' => $customName ?? "Infrastructure Support - High Priority - {$company->name}",
                'email_audio_option' => 'link',
                'email_show_admin_link' => true,
                'wait_for_enrichment' => false, // immediate delivery
                'enrichment_timeout_seconds' => 180, // 3 minutes
                'include_transcript' => true,
                'include_summary' => true,
            ],
            'application' => [
                'name' => $customName ?? "Application Support - Standard - {$company->name}",
                'email_audio_option' => 'link',
                'email_show_admin_link' => false,
                'wait_for_enrichment' => false,
                'enrichment_timeout_seconds' => 180, // 3 minutes
                'include_transcript' => true,
                'include_summary' => true,
            ],
            'general' => [
                'name' => $customName ?? "General Inquiry - Standard - {$company->name}",
                'email_audio_option' => 'link',
                'email_show_admin_link' => false,
                'wait_for_enrichment' => false,
                'enrichment_timeout_seconds' => 120, // 2 minutes
                'include_transcript' => true,
                'include_summary' => true,
            ],
        ];

        $config = $templates[$categoryType] ?? $templates['general'];

        return ServiceOutputConfiguration::create(array_merge($config, [
            'company_id' => $company->id,
            'output_type' => ServiceOutputConfiguration::TYPE_EMAIL,
            'email_recipients' => ["support@{$company->id}.askproai.de"], // Use company ID for unique email
            'is_active' => true,
            'retry_on_failure' => true,
        ]));
    }
}

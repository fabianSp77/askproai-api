<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * ServiceOutputConfiguration Model
 *
 * Defines how service case outputs are delivered (email, webhook, etc.)
 *
 * @property int $id
 * @property int $company_id
 * @property string $name
 * @property string $output_type email|webhook|both
 * @property array|null $email_recipients
 * @property string|null $email_subject_template
 * @property string|null $email_body_template
 * @property int|null $webhook_configuration_id
 * @property int|null $webhook_preset_id Link to webhook template preset
 * @property string|null $webhook_url
 * @property array|null $webhook_headers
 * @property string|null $webhook_payload_template
 * @property string|null $webhook_secret HMAC-SHA256 secret for webhook signing (encrypted)
 * @property bool $webhook_enabled Toggle to enable/disable webhook delivery
 * @property bool $webhook_include_transcript Include call transcript in webhook payload
 * @property array|null $fallback_emails
 * @property bool $retry_on_failure
 * @property bool $is_active
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 * @property-read WebhookPreset|null $webhookPreset
 */
class ServiceOutputConfiguration extends Model
{
    use BelongsToCompany, HasFactory;

    /**
     * Output type constants
     * Note: Database uses 'hybrid' for combined email+webhook delivery
     */
    public const TYPE_EMAIL = 'email';

    public const TYPE_WEBHOOK = 'webhook';

    public const TYPE_HYBRID = 'hybrid'; // Both email and webhook

    public const OUTPUT_TYPES = [
        self::TYPE_EMAIL,
        self::TYPE_WEBHOOK,
        self::TYPE_HYBRID,
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'company_id',
        'name',
        'output_type',
        'email_recipients',
        'muted_recipients',
        'email_template_type',
        'email_subject_template',
        'email_body_template',
        'template_id',
        'webhook_configuration_id',
        'webhook_url',
        'webhook_headers',
        'webhook_payload_template',
        'webhook_preset_id',
        'webhook_secret',
        'webhook_enabled',
        'webhook_include_transcript',
        'contact_type_override', // ServiceNow contact_type override (null = auto-map)
        'fallback_emails',
        'retry_on_failure',
        'is_active',
        'email_audio_option',
        'include_transcript',
        'include_summary',
        'email_show_admin_link',
        // Delivery-gate fields (2-Phase Pattern)
        'wait_for_enrichment',
        'enrichment_timeout_seconds',
        'audio_url_ttl_minutes',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_recipients' => 'array',
        'muted_recipients' => 'array',
        'webhook_headers' => 'array',
        'webhook_payload_template' => 'array',
        'fallback_emails' => 'array',
        'retry_on_failure' => 'boolean',
        'is_active' => 'boolean',
        'include_transcript' => 'boolean',
        'include_summary' => 'boolean',
        'email_show_admin_link' => 'boolean',
        'webhook_secret' => 'encrypted',
        'webhook_enabled' => 'boolean',
        'webhook_include_transcript' => 'boolean',
        // Delivery-gate fields
        'wait_for_enrichment' => 'boolean',
        'enrichment_timeout_seconds' => 'integer',
        'audio_url_ttl_minutes' => 'integer',
    ];

    /**
     * Get the webhook configuration.
     */
    public function webhookConfiguration(): BelongsTo
    {
        return $this->belongsTo(WebhookConfiguration::class);
    }

    /**
     * Get the webhook preset template.
     */
    public function webhookPreset(): BelongsTo
    {
        return $this->belongsTo(WebhookPreset::class);
    }

    /**
     * Get the custom email template.
     */
    public function emailTemplate(): BelongsTo
    {
        return $this->belongsTo(EmailTemplate::class, 'template_id');
    }

    /**
     * Check if this configuration uses a preset template.
     */
    public function usesPreset(): bool
    {
        return $this->webhook_preset_id !== null;
    }

    /**
     * Get the effective payload template (from preset or direct configuration).
     */
    public function getEffectivePayloadTemplate(): ?array
    {
        // Preset takes precedence if linked
        if ($this->usesPreset() && $this->webhookPreset) {
            return $this->webhookPreset->payload_template;
        }

        return $this->webhook_payload_template;
    }

    /**
     * Get the effective headers template (from preset or direct configuration).
     */
    public function getEffectiveHeadersTemplate(): ?array
    {
        // Preset takes precedence if linked
        if ($this->usesPreset() && $this->webhookPreset) {
            return $this->webhookPreset->headers_template;
        }

        return $this->webhook_headers;
    }

    /**
     * Get the effective contact_type for a ServiceCase.
     *
     * Uses override if configured, otherwise auto-maps from the case's source.
     *
     * @return string ServiceNow-compatible contact_type
     */
    public function getEffectiveContactType(\App\Models\ServiceCase $case): string
    {
        // Use override if explicitly set
        if (! empty($this->contact_type_override)) {
            return $this->contact_type_override;
        }

        // Auto-map from source
        return $case->service_now_contact_type;
    }

    /**
     * Get the categories using this output configuration.
     */
    public function categories(): HasMany
    {
        return $this->hasMany(ServiceCaseCategory::class, 'output_configuration_id');
    }

    /**
     * Get all exchange logs for this output configuration.
     * Used for Delivery Historie UI.
     */
    public function exchangeLogs(): HasMany
    {
        return $this->hasMany(ServiceGatewayExchangeLog::class, 'output_configuration_id');
    }

    /**
     * Get recent delivery logs (last 20) for UI display.
     */
    public function recentDeliveries(): HasMany
    {
        return $this->exchangeLogs()
            ->orderBy('created_at', 'desc')
            ->limit(20);
    }

    /**
     * Scope to active configurations only.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Check if this configuration sends email.
     */
    public function sendsEmail(): bool
    {
        return in_array($this->output_type, [self::TYPE_EMAIL, self::TYPE_HYBRID]);
    }

    /**
     * Check if this configuration sends webhook.
     */
    public function sendsWebhook(): bool
    {
        return in_array($this->output_type, [self::TYPE_WEBHOOK, self::TYPE_HYBRID]);
    }

    /**
     * Check if webhook is actively enabled (type + toggle).
     * Use this for actual delivery decisions.
     */
    public function webhookIsActive(): bool
    {
        return $this->sendsWebhook() && ($this->webhook_enabled ?? true);
    }

    // =========================================================================
    // Recipient Muting (for testing without modifying primary list)
    // =========================================================================

    /**
     * Get active (non-muted) email recipients.
     *
     * Use this for actual email delivery to exclude paused recipients.
     *
     * @return array<string> List of active email addresses
     */
    public function getActiveRecipients(): array
    {
        $all = $this->email_recipients ?? [];
        $muted = $this->muted_recipients ?? [];

        return array_values(array_diff($all, $muted));
    }

    /**
     * Get count of muted recipients.
     */
    public function getMutedCount(): int
    {
        return count($this->muted_recipients ?? []);
    }

    /**
     * Check if a specific email is muted.
     */
    public function isRecipientMuted(string $email): bool
    {
        return in_array($email, $this->muted_recipients ?? [], true);
    }

    /**
     * Mute specific recipients (add to muted list).
     *
     * @param  array<string>|string  $emails  Email(s) to mute
     */
    public function muteRecipients(array|string $emails): void
    {
        $emails = is_array($emails) ? $emails : [$emails];
        $current = $this->muted_recipients ?? [];

        $this->muted_recipients = array_values(array_unique(array_merge($current, $emails)));
    }

    /**
     * Unmute specific recipients (remove from muted list).
     *
     * @param  array<string>|string  $emails  Email(s) to unmute
     */
    public function unmuteRecipients(array|string $emails): void
    {
        $emails = is_array($emails) ? $emails : [$emails];
        $current = $this->muted_recipients ?? [];

        $this->muted_recipients = array_values(array_diff($current, $emails));
    }

    /**
     * Unmute all recipients.
     */
    public function unmuteAll(): void
    {
        $this->muted_recipients = [];
    }

    /**
     * Mute all recipients except specified ones.
     *
     * Useful for testing: muteAllExcept(['test@example.com'])
     *
     * @param  array<string>  $keepActive  Emails to keep active
     */
    public function muteAllExcept(array $keepActive): void
    {
        $all = $this->email_recipients ?? [];
        $this->muted_recipients = array_values(array_diff($all, $keepActive));
    }
}

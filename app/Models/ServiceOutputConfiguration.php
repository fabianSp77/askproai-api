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
 * @property string|null $webhook_url
 * @property array|null $webhook_headers
 * @property string|null $webhook_payload_template
 * @property array|null $fallback_emails
 * @property bool $retry_on_failure
 * @property bool $is_active
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 */
class ServiceOutputConfiguration extends Model
{
    use HasFactory, BelongsToCompany;

    /**
     * Output type constants
     */
    public const TYPE_EMAIL = 'email';
    public const TYPE_WEBHOOK = 'webhook';
    public const TYPE_BOTH = 'both';

    public const OUTPUT_TYPES = [
        self::TYPE_EMAIL,
        self::TYPE_WEBHOOK,
        self::TYPE_BOTH,
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
        'email_subject_template',
        'email_body_template',
        'webhook_configuration_id',
        'webhook_url',
        'webhook_headers',
        'webhook_payload_template',
        'fallback_emails',
        'retry_on_failure',
        'is_active',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_recipients' => 'array',
        'webhook_headers' => 'array',
        'fallback_emails' => 'array',
        'retry_on_failure' => 'boolean',
        'is_active' => 'boolean',
    ];

    /**
     * Get the webhook configuration.
     */
    public function webhookConfiguration(): BelongsTo
    {
        return $this->belongsTo(WebhookConfiguration::class);
    }

    /**
     * Get the categories using this output configuration.
     */
    public function categories(): HasMany
    {
        return $this->hasMany(ServiceCaseCategory::class, 'output_configuration_id');
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
        return in_array($this->output_type, [self::TYPE_EMAIL, self::TYPE_BOTH]);
    }

    /**
     * Check if this configuration sends webhook.
     */
    public function sendsWebhook(): bool
    {
        return in_array($this->output_type, [self::TYPE_WEBHOOK, self::TYPE_BOTH]);
    }
}

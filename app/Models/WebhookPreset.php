<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

/**
 * WebhookPreset Model
 *
 * Reusable webhook templates for external system integrations.
 * Supports both system-provided presets and company-custom templates.
 *
 * @property int $id
 * @property int|null $company_id Null for system presets
 * @property string $name Human-readable name
 * @property string $slug URL-safe identifier
 * @property string|null $description
 * @property string $target_system jira|servicenow|otrs|zendesk|slack|teams|custom
 * @property string $category ticketing|messaging|custom
 * @property array $payload_template JSON template with {{variable}} placeholders
 * @property array|null $headers_template Custom headers template
 * @property array|null $variable_schema JSON Schema for variable validation
 * @property array|null $default_values Default values for optional variables
 * @property string $auth_type hmac|bearer|basic|api_key|none
 * @property string|null $auth_instructions Setup instructions
 * @property string $version Semantic version
 * @property bool $is_active
 * @property bool $is_system Cannot be deleted by companies
 * @property string|null $documentation_url
 * @property array|null $example_response Expected response format
 * @property string|null $created_by Staff UUID
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 */
class WebhookPreset extends Model
{
    use HasFactory, SoftDeletes, BelongsToCompany;

    /**
     * Target system constants
     */
    public const SYSTEM_JIRA = 'jira';
    public const SYSTEM_SERVICENOW = 'servicenow';
    public const SYSTEM_OTRS = 'otrs';
    public const SYSTEM_ZENDESK = 'zendesk';
    public const SYSTEM_SLACK = 'slack';
    public const SYSTEM_TEAMS = 'teams';
    public const SYSTEM_CUSTOM = 'custom';

    public const TARGET_SYSTEMS = [
        self::SYSTEM_JIRA,
        self::SYSTEM_SERVICENOW,
        self::SYSTEM_OTRS,
        self::SYSTEM_ZENDESK,
        self::SYSTEM_SLACK,
        self::SYSTEM_TEAMS,
        self::SYSTEM_CUSTOM,
    ];

    /**
     * Category constants
     */
    public const CATEGORY_TICKETING = 'ticketing';
    public const CATEGORY_MESSAGING = 'messaging';
    public const CATEGORY_CUSTOM = 'custom';

    public const CATEGORIES = [
        self::CATEGORY_TICKETING,
        self::CATEGORY_MESSAGING,
        self::CATEGORY_CUSTOM,
    ];

    /**
     * Authentication type constants
     */
    public const AUTH_HMAC = 'hmac';
    public const AUTH_BEARER = 'bearer';
    public const AUTH_BASIC = 'basic';
    public const AUTH_API_KEY = 'api_key';
    public const AUTH_NONE = 'none';

    public const AUTH_TYPES = [
        self::AUTH_HMAC,
        self::AUTH_BEARER,
        self::AUTH_BASIC,
        self::AUTH_API_KEY,
        self::AUTH_NONE,
    ];

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'company_id',
        'name',
        'slug',
        'description',
        'target_system',
        'category',
        'payload_template',
        'headers_template',
        'variable_schema',
        'default_values',
        'auth_type',
        'auth_instructions',
        'version',
        'is_active',
        'is_system',
        'documentation_url',
        'example_response',
        'created_by',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'payload_template' => 'array',
        'headers_template' => 'array',
        'variable_schema' => 'array',
        'default_values' => 'array',
        'example_response' => 'array',
        'is_active' => 'boolean',
        'is_system' => 'boolean',
    ];

    /**
     * Boot the model.
     */
    protected static function boot(): void
    {
        parent::boot();

        // Auto-generate slug from name
        static::creating(function (self $preset) {
            if (empty($preset->slug)) {
                $preset->slug = Str::slug($preset->name);
            }
        });

        // Prevent deletion of system presets
        static::deleting(function (self $preset) {
            if ($preset->is_system) {
                throw new \RuntimeException('System presets cannot be deleted.');
            }
        });
    }

    // =========================================================================
    // Relationships
    // =========================================================================

    /**
     * Get the company that owns this preset (null for system presets).
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get the staff member who created this preset.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'created_by');
    }

    /**
     * Get output configurations using this preset.
     */
    public function outputConfigurations(): HasMany
    {
        return $this->hasMany(ServiceOutputConfiguration::class, 'webhook_preset_id');
    }

    // =========================================================================
    // Scopes
    // =========================================================================

    /**
     * Scope to active presets only.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to system presets only.
     */
    public function scopeSystem($query)
    {
        return $query->where('is_system', true)->whereNull('company_id');
    }

    /**
     * Scope to company-custom presets only.
     */
    public function scopeCustom($query)
    {
        return $query->where('is_system', false)->whereNotNull('company_id');
    }

    /**
     * Scope to presets available for a specific company.
     * Includes both system presets and company-specific presets.
     */
    public function scopeAvailableFor($query, int $companyId)
    {
        return $query->where(function ($q) use ($companyId) {
            $q->whereNull('company_id')  // System presets
                ->orWhere('company_id', $companyId);  // Company presets
        })->active();
    }

    /**
     * Scope by target system.
     */
    public function scopeForSystem($query, string $system)
    {
        return $query->where('target_system', $system);
    }

    /**
     * Scope by category.
     */
    public function scopeInCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    // =========================================================================
    // Template Methods
    // =========================================================================

    /**
     * Get all variables defined in the payload template.
     *
     * Extracts variables in these formats:
     * - {{variable}} - Simple substitution
     * - {{#if variable}}...{{/if}} - Conditional blocks
     * - {{variable|default:value}} - With default
     *
     * @return array List of variable names
     */
    public function extractVariables(): array
    {
        $json = json_encode($this->payload_template);
        $variables = [];

        // Match {{variable}}, {{variable|default:value}}, {{#if variable}}
        preg_match_all('/\{\{#?(?:if\s+)?([a-zA-Z0-9_.]+)(?:\|[^}]+)?\}\}/', $json, $matches);

        if (!empty($matches[1])) {
            $variables = array_unique($matches[1]);
        }

        return array_values($variables);
    }

    /**
     * Get required variables (those without defaults).
     *
     * @return array List of required variable names
     */
    public function getRequiredVariables(): array
    {
        $schema = $this->variable_schema ?? [];
        $required = [];

        foreach ($schema as $variable => $config) {
            if (($config['required'] ?? false) === true) {
                $required[] = $variable;
            }
        }

        return $required;
    }

    /**
     * Validate that all required variables are provided.
     *
     * @param array $data Data to validate against
     * @return array List of missing required variables (empty if valid)
     */
    public function validateRequiredVariables(array $data): array
    {
        $required = $this->getRequiredVariables();
        $missing = [];

        foreach ($required as $variable) {
            $value = data_get($data, $variable);
            if ($value === null || $value === '') {
                $missing[] = $variable;
            }
        }

        return $missing;
    }

    /**
     * Get the merged default values.
     *
     * @param array $overrides Values to override defaults
     * @return array Merged values
     */
    public function getMergedDefaults(array $overrides = []): array
    {
        return array_merge($this->default_values ?? [], $overrides);
    }

    /**
     * Check if this preset supports a specific variable.
     *
     * @param string $variable Variable name (dot notation)
     * @return bool
     */
    public function supportsVariable(string $variable): bool
    {
        $variables = $this->extractVariables();
        return in_array($variable, $variables);
    }

    /**
     * Get the payload template as JSON string.
     *
     * @return string
     */
    public function getPayloadJson(): string
    {
        return json_encode($this->payload_template, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }

    /**
     * Create a copy of this preset for a specific company.
     *
     * @param int $companyId
     * @param string|null $newName
     * @return self
     */
    public function duplicateForCompany(int $companyId, ?string $newName = null): self
    {
        $copy = $this->replicate();
        $copy->company_id = $companyId;
        $copy->is_system = false;
        $copy->name = $newName ?? $this->name . ' (Copy)';
        $copy->slug = Str::slug($copy->name) . '-' . Str::random(6);
        $copy->save();

        return $copy;
    }

    // =========================================================================
    // Display Helpers
    // =========================================================================

    /**
     * Get human-readable target system name.
     */
    public function getTargetSystemLabelAttribute(): string
    {
        return match ($this->target_system) {
            self::SYSTEM_JIRA => 'Jira',
            self::SYSTEM_SERVICENOW => 'ServiceNow',
            self::SYSTEM_OTRS => 'OTRS',
            self::SYSTEM_ZENDESK => 'Zendesk',
            self::SYSTEM_SLACK => 'Slack',
            self::SYSTEM_TEAMS => 'Microsoft Teams',
            self::SYSTEM_CUSTOM => 'Custom',
            default => ucfirst($this->target_system),
        };
    }

    /**
     * Get human-readable category name.
     */
    public function getCategoryLabelAttribute(): string
    {
        return match ($this->category) {
            self::CATEGORY_TICKETING => 'Ticketing System',
            self::CATEGORY_MESSAGING => 'Messaging Platform',
            self::CATEGORY_CUSTOM => 'Custom Integration',
            default => ucfirst($this->category),
        };
    }

    /**
     * Get badge color for target system (Filament).
     */
    public function getTargetSystemColorAttribute(): string
    {
        return match ($this->target_system) {
            self::SYSTEM_JIRA => 'primary',
            self::SYSTEM_SERVICENOW => 'success',
            self::SYSTEM_OTRS => 'warning',
            self::SYSTEM_ZENDESK => 'danger',
            self::SYSTEM_SLACK => 'info',
            self::SYSTEM_TEAMS => 'purple',
            default => 'gray',
        };
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\Cache;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SystemSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'group',
        'category',
        'key',
        'value',
        'default_value',
        'min_value',
        'max_value',
        'type',
        'label',
        'description',
        'help_text',
        'options',
        'rules',
        'validation_message',
        'is_public',
        'is_encrypted',
        'is_readonly',
        'is_system',
        'is_visible',
        'priority',
        'cache_ttl',
        'requires_restart',
        'updated_by',
        'change_count',
        'last_changed_at',
        'metadata',
    ];

    protected $casts = [
        'options' => 'array',
        'rules' => 'array',
        'metadata' => 'array',
        'is_public' => 'boolean',
        'is_encrypted' => 'boolean',
        'is_readonly' => 'boolean',
        'is_system' => 'boolean',
        'is_visible' => 'boolean',
        'requires_restart' => 'boolean',
        'priority' => 'integer',
        'cache_ttl' => 'integer',
        'change_count' => 'integer',
        'last_changed_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Setting groups
    const GROUP_GENERAL = 'general';
    const GROUP_EMAIL = 'email';
    const GROUP_SECURITY = 'security';
    const GROUP_INTEGRATION = 'integration';
    const GROUP_PERFORMANCE = 'performance';
    const GROUP_APPEARANCE = 'appearance';
    const GROUP_NOTIFICATION = 'notification';
    const GROUP_BACKUP = 'backup';
    const GROUP_MAINTENANCE = 'maintenance';
    const GROUP_API = 'api';
    const GROUP_ANALYTICS = 'analytics';
    const GROUP_LOCALIZATION = 'localization';
    const GROUP_LOGGING = 'logging';
    const GROUP_CACHE = 'cache';

    // Setting categories
    const CATEGORY_CORE = 'core';
    const CATEGORY_FEATURE = 'feature';
    const CATEGORY_INTEGRATION = 'integration';
    const CATEGORY_ADVANCED = 'advanced';
    const CATEGORY_EXPERIMENTAL = 'experimental';
    const CATEGORY_DEPRECATED = 'deprecated';

    // Setting types
    const TYPE_STRING = 'string';
    const TYPE_INTEGER = 'integer';
    const TYPE_FLOAT = 'float';
    const TYPE_BOOLEAN = 'boolean';
    const TYPE_JSON = 'json';
    const TYPE_ARRAY = 'array';
    const TYPE_TEXTAREA = 'textarea';
    const TYPE_SELECT = 'select';
    const TYPE_MULTISELECT = 'multiselect';
    const TYPE_COLOR = 'color';
    const TYPE_DATE = 'date';
    const TYPE_TIME = 'time';
    const TYPE_DATETIME = 'datetime';
    const TYPE_URL = 'url';
    const TYPE_EMAIL = 'email';
    const TYPE_PASSWORD = 'password';
    const TYPE_FILE = 'file';
    const TYPE_CODE = 'code';
    const TYPE_ENCRYPTED = 'encrypted';

    /**
     * Boot the model
     */
    protected static function boot()
    {
        parent::boot();

        static::saving(function ($setting) {
            // Track changes
            if ($setting->isDirty('value')) {
                $setting->change_count = ($setting->change_count ?? 0) + 1;
                $setting->last_changed_at = now();
            }
        });

        static::saved(function ($setting) {
            // Clear cache
            Cache::forget("setting:{$setting->key}");
            Cache::forget('settings:all');
            Cache::forget('settings:grouped');

            // Clear specific group cache
            Cache::forget("settings:group:{$setting->group}");
        });

        static::deleted(function ($setting) {
            Cache::forget("setting:{$setting->key}");
            Cache::forget('settings:all');
            Cache::forget('settings:grouped');
            Cache::forget("settings:group:{$setting->group}");
        });
    }

    /**
     * Get the user who last updated this setting
     */
    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Get setting value by key
     */
    public static function getValue(string $key, $default = null)
    {
        $setting = static::where('key', $key)->first();

        if (!$setting) {
            return $default;
        }

        $ttl = $setting->cache_ttl ?? 3600;

        return Cache::remember("setting:$key", $ttl, function () use ($setting) {
            return $setting->getParsedValue();
        });
    }

    /**
     * Set setting value by key
     */
    public static function setValue(string $key, $value, ?int $userId = null): bool
    {
        $setting = static::firstOrNew(['key' => $key]);

        // Store old value for change tracking
        $oldValue = $setting->exists ? $setting->value : null;

        $setting->value = is_array($value) ? json_encode($value) : $value;

        if ($userId) {
            $setting->updated_by = $userId;
        }

        $result = $setting->save();

        // Log the change
        if ($result && $oldValue !== $setting->value) {
            activity()
                ->performedOn($setting)
                ->causedBy($userId ? User::find($userId) : null)
                ->withProperties([
                    'old_value' => $oldValue,
                    'new_value' => $setting->value,
                    'key' => $key,
                ])
                ->log("Setting '{$key}' updated");
        }

        return $result;
    }

    /**
     * Get parsed value based on type
     */
    public function getParsedValue()
    {
        $value = $this->value;

        // Decrypt if needed (use decryptString to avoid serialization)
        if ($this->is_encrypted && $value) {
            try {
                $value = \Illuminate\Support\Facades\Crypt::decryptString($value);
            } catch (\Exception $e) {
                // Return default if decryption fails
                return $this->getDefaultParsedValue();
            }
        }

        // Return default if value is null
        if ($value === null) {
            return $this->getDefaultParsedValue();
        }

        return match($this->type) {
            self::TYPE_INTEGER => (int) $value,
            self::TYPE_FLOAT => (float) $value,
            self::TYPE_BOOLEAN => filter_var($value, FILTER_VALIDATE_BOOLEAN),
            self::TYPE_JSON, self::TYPE_ARRAY, self::TYPE_MULTISELECT => json_decode($value, true) ?? [],
            self::TYPE_DATE => $value ? \Carbon\Carbon::parse($value)->toDateString() : null,
            self::TYPE_DATETIME => $value ? \Carbon\Carbon::parse($value)->toDateTimeString() : null,
            self::TYPE_TIME => $value ? \Carbon\Carbon::parse($value)->toTimeString() : null,
            default => $value,
        };
    }

    /**
     * Get default parsed value
     */
    public function getDefaultParsedValue()
    {
        if (!$this->default_value) {
            return match($this->type) {
                self::TYPE_BOOLEAN => false,
                self::TYPE_INTEGER, self::TYPE_FLOAT => 0,
                self::TYPE_JSON, self::TYPE_ARRAY, self::TYPE_MULTISELECT => [],
                default => null,
            };
        }

        $value = $this->default_value;

        return match($this->type) {
            self::TYPE_INTEGER => (int) $value,
            self::TYPE_FLOAT => (float) $value,
            self::TYPE_BOOLEAN => filter_var($value, FILTER_VALIDATE_BOOLEAN),
            self::TYPE_JSON, self::TYPE_ARRAY, self::TYPE_MULTISELECT => json_decode($value, true) ?? [],
            default => $value,
        };
    }

    /**
     * Set value with encryption if needed (use encryptString to avoid serialization)
     */
    public function setValueAttribute($value)
    {
        if ($this->is_encrypted && $value !== null) {
            $this->attributes['value'] = \Illuminate\Support\Facades\Crypt::encryptString($value);
        } else {
            $this->attributes['value'] = is_array($value) ? json_encode($value) : $value;
        }
    }

    /**
     * Get formatted label
     */
    public function getFormattedLabelAttribute(): string
    {
        return $this->label ?: str_replace(['_', '-'], ' ', ucfirst($this->key));
    }

    /**
     * Get group label with icon
     */
    public function getGroupLabelAttribute(): string
    {
        return match($this->group) {
            self::GROUP_GENERAL => '⚙️ Allgemein',
            self::GROUP_EMAIL => '📧 E-Mail',
            self::GROUP_SECURITY => '🔒 Sicherheit',
            self::GROUP_INTEGRATION => '🔗 Integrationen',
            self::GROUP_PERFORMANCE => '⚡ Performance',
            self::GROUP_APPEARANCE => '🎨 Erscheinungsbild',
            self::GROUP_NOTIFICATION => '🔔 Benachrichtigungen',
            self::GROUP_BACKUP => '💾 Backup',
            self::GROUP_MAINTENANCE => '🔧 Wartung',
            self::GROUP_API => '🔌 API',
            self::GROUP_ANALYTICS => '📊 Analytics',
            self::GROUP_LOCALIZATION => '🌍 Lokalisierung',
            default => ucfirst($this->group),
        };
    }

    /**
     * Get category label
     */
    public function getCategoryLabelAttribute(): string
    {
        return match($this->category) {
            self::CATEGORY_CORE => '🎯 Kern',
            self::CATEGORY_ADVANCED => '🚀 Erweitert',
            self::CATEGORY_EXPERIMENTAL => '🧪 Experimentell',
            self::CATEGORY_DEPRECATED => '⚠️ Veraltet',
            default => ucfirst($this->category ?? 'Standard'),
        };
    }

    /**
     * Get type label
     */
    public function getTypeLabelAttribute(): string
    {
        return match($this->type) {
            self::TYPE_STRING => '📝 Text',
            self::TYPE_INTEGER => '#️⃣ Ganzzahl',
            self::TYPE_FLOAT => '🔢 Dezimalzahl',
            self::TYPE_BOOLEAN => '✅ Ja/Nein',
            self::TYPE_JSON => '📋 JSON',
            self::TYPE_ARRAY => '📚 Array',
            self::TYPE_TEXTAREA => '📄 Textbereich',
            self::TYPE_SELECT => '📌 Auswahl',
            self::TYPE_MULTISELECT => '📌 Mehrfachauswahl',
            self::TYPE_COLOR => '🎨 Farbe',
            self::TYPE_DATE => '📅 Datum',
            self::TYPE_TIME => '🕐 Zeit',
            self::TYPE_DATETIME => '📅 Datum & Zeit',
            self::TYPE_URL => '🔗 URL',
            self::TYPE_EMAIL => '📧 E-Mail',
            self::TYPE_PASSWORD => '🔐 Passwort',
            self::TYPE_FILE => '📁 Datei',
            self::TYPE_CODE => '💻 Code',
            default => ucfirst($this->type),
        };
    }

    /**
     * Get priority label
     */
    public function getPriorityLabelAttribute(): string
    {
        if ($this->priority >= 1000) {
            return '🔴 Kritisch';
        } elseif ($this->priority >= 500) {
            return '🟡 Hoch';
        } elseif ($this->priority >= 100) {
            return '🔵 Normal';
        } else {
            return '⚪ Niedrig';
        }
    }

    /**
     * Check if setting is critical
     */
    public function getIsCriticalAttribute(): bool
    {
        $criticalKeys = [
            'maintenance_mode',
            'backup_enabled',
            'enable_2fa',
            'api_rate_limit',
            'max_login_attempts',
        ];

        return in_array($this->key, $criticalKeys) || $this->priority >= 1000;
    }

    /**
     * Get validation status
     */
    public function validateValue($value = null): bool
    {
        $value = $value ?? $this->value;

        if (!$this->rules) {
            return true;
        }

        $validator = validator(['value' => $value], ['value' => $this->rules]);
        return !$validator->fails();
    }

    /**
     * Get all settings grouped
     */
    public static function getAllGrouped(): array
    {
        return Cache::remember('settings:grouped', 3600, function () {
            return static::where('is_visible', true)
                ->orderBy('group')
                ->orderBy('priority', 'desc')
                ->orderBy('key')
                ->get()
                ->groupBy('group')
                ->map(function ($items) {
                    return $items->mapWithKeys(function ($item) {
                        return [$item->key => $item->getParsedValue()];
                    });
                })->toArray();
        });
    }

    /**
     * Get settings by group
     */
    public static function getByGroup(string $group): array
    {
        return Cache::remember("settings:group:$group", 3600, function () use ($group) {
            return static::where('group', $group)
                ->where('is_visible', true)
                ->orderBy('priority', 'desc')
                ->orderBy('key')
                ->get()
                ->mapWithKeys(function ($item) {
                    return [$item->key => $item->getParsedValue()];
                })->toArray();
        });
    }

    /**
     * Scopes
     */
    public function scopeVisible($query)
    {
        return $query->where('is_visible', true);
    }

    public function scopePublic($query)
    {
        return $query->where('is_public', true);
    }

    public function scopePrivate($query)
    {
        return $query->where('is_public', false);
    }

    public function scopeSystem($query)
    {
        return $query->where('is_system', true);
    }

    public function scopeEditable($query)
    {
        return $query->where('is_readonly', false);
    }

    public function scopeEncrypted($query)
    {
        return $query->where('is_encrypted', true);
    }

    public function scopeRequiresRestart($query)
    {
        return $query->where('requires_restart', true);
    }

    public function scopeInGroup($query, string $group)
    {
        return $query->where('group', $group);
    }

    public function scopeInCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    public function scopeByPriority($query)
    {
        return $query->orderBy('priority', 'desc');
    }

    public function scopeCritical($query)
    {
        return $query->where('priority', '>=', 1000);
    }

    /**
     * Create default settings
     */
    public static function createDefaults(): void
    {
        $defaults = [
            // General
            [
                'group' => self::GROUP_GENERAL,
                'category' => self::CATEGORY_CORE,
                'key' => 'site_name',
                'value' => config('app.name', 'API Gateway'),
                'default_value' => 'API Gateway',
                'type' => self::TYPE_STRING,
                'label' => 'Website Name',
                'description' => 'Der Name Ihrer Website',
                'help_text' => 'Dieser Name wird in E-Mails und im Browser-Tab angezeigt',
                'is_public' => true,
                'priority' => 1000,
            ],
            [
                'group' => self::GROUP_GENERAL,
                'category' => self::CATEGORY_CORE,
                'key' => 'site_description',
                'value' => 'Professional API Gateway System',
                'default_value' => 'Professional API Gateway System',
                'type' => self::TYPE_TEXTAREA,
                'label' => 'Website Beschreibung',
                'description' => 'Kurze Beschreibung Ihrer Website',
                'is_public' => true,
                'priority' => 900,
            ],
            [
                'group' => self::GROUP_GENERAL,
                'category' => self::CATEGORY_CORE,
                'key' => 'timezone',
                'value' => 'Europe/Berlin',
                'default_value' => 'Europe/Berlin',
                'type' => self::TYPE_SELECT,
                'label' => 'Zeitzone',
                'description' => 'Standard Zeitzone für das System',
                'options' => timezone_identifiers_list(),
                'priority' => 800,
            ],

            // Email
            [
                'group' => self::GROUP_EMAIL,
                'category' => self::CATEGORY_CORE,
                'key' => 'mail_from_address',
                'value' => 'noreply@example.com',
                'default_value' => 'noreply@example.com',
                'type' => self::TYPE_EMAIL,
                'label' => 'Absender E-Mail',
                'description' => 'Standard E-Mail Absenderadresse',
                'rules' => ['email'],
                'priority' => 900,
            ],
            [
                'group' => self::GROUP_EMAIL,
                'category' => self::CATEGORY_CORE,
                'key' => 'mail_from_name',
                'value' => 'API Gateway',
                'default_value' => 'API Gateway',
                'type' => self::TYPE_STRING,
                'label' => 'Absender Name',
                'description' => 'Standard E-Mail Absendername',
                'priority' => 800,
            ],

            // Security
            [
                'group' => self::GROUP_SECURITY,
                'category' => self::CATEGORY_CORE,
                'key' => 'password_min_length',
                'value' => 8,
                'default_value' => 8,
                'min_value' => 6,
                'max_value' => 32,
                'type' => self::TYPE_INTEGER,
                'label' => 'Minimale Passwortlänge',
                'description' => 'Mindestanzahl von Zeichen für Passwörter',
                'help_text' => 'Empfohlen: Mindestens 8 Zeichen',
                'rules' => ['integer', 'min:6', 'max:32'],
                'priority' => 1000,
                'requires_restart' => false,
            ],
            [
                'group' => self::GROUP_SECURITY,
                'category' => self::CATEGORY_CORE,
                'key' => 'enable_2fa',
                'value' => true,
                'default_value' => true,
                'type' => self::TYPE_BOOLEAN,
                'label' => '2FA aktivieren',
                'description' => 'Zwei-Faktor-Authentifizierung aktivieren',
                'help_text' => 'Erhöht die Sicherheit durch zusätzlichen Authentifizierungsschritt',
                'priority' => 900,
            ],
            [
                'group' => self::GROUP_SECURITY,
                'category' => self::CATEGORY_CORE,
                'key' => 'session_lifetime',
                'value' => 120,
                'default_value' => 120,
                'min_value' => 15,
                'max_value' => 1440,
                'type' => self::TYPE_INTEGER,
                'label' => 'Session Lebensdauer (Minuten)',
                'description' => 'Wie lange Benutzer eingeloggt bleiben',
                'rules' => ['integer', 'min:15', 'max:1440'],
                'priority' => 800,
            ],

            // Performance
            [
                'group' => self::GROUP_PERFORMANCE,
                'category' => self::CATEGORY_ADVANCED,
                'key' => 'cache_enabled',
                'value' => true,
                'default_value' => true,
                'type' => self::TYPE_BOOLEAN,
                'label' => 'Cache aktivieren',
                'description' => 'System-Cache aktivieren für bessere Performance',
                'priority' => 1000,
                'requires_restart' => true,
            ],
            [
                'group' => self::GROUP_PERFORMANCE,
                'category' => self::CATEGORY_ADVANCED,
                'key' => 'api_rate_limit',
                'value' => 60,
                'default_value' => 60,
                'min_value' => 10,
                'max_value' => 1000,
                'type' => self::TYPE_INTEGER,
                'label' => 'API Rate Limit',
                'description' => 'Maximale API-Anfragen pro Minute',
                'rules' => ['integer', 'min:10', 'max:1000'],
                'priority' => 900,
            ],

            // Maintenance
            [
                'group' => self::GROUP_MAINTENANCE,
                'category' => self::CATEGORY_CORE,
                'key' => 'maintenance_mode',
                'value' => false,
                'default_value' => false,
                'type' => self::TYPE_BOOLEAN,
                'label' => 'Wartungsmodus',
                'description' => 'Website in Wartungsmodus setzen',
                'help_text' => 'Nur Administratoren können die Website während der Wartung aufrufen',
                'priority' => 2000,
                'is_critical' => true,
            ],
            [
                'group' => self::GROUP_MAINTENANCE,
                'category' => self::CATEGORY_CORE,
                'key' => 'maintenance_message',
                'value' => 'Wir führen gerade Wartungsarbeiten durch. Bitte versuchen Sie es später erneut.',
                'default_value' => 'Wir führen gerade Wartungsarbeiten durch. Bitte versuchen Sie es später erneut.',
                'type' => self::TYPE_TEXTAREA,
                'label' => 'Wartungsnachricht',
                'description' => 'Nachricht für Benutzer während der Wartung',
                'priority' => 1900,
            ],

            // Backup
            [
                'group' => self::GROUP_BACKUP,
                'category' => self::CATEGORY_CORE,
                'key' => 'backup_enabled',
                'value' => true,
                'default_value' => true,
                'type' => self::TYPE_BOOLEAN,
                'label' => 'Automatisches Backup',
                'description' => 'Automatische Backups aktivieren',
                'priority' => 1000,
                'is_critical' => true,
            ],
            [
                'group' => self::GROUP_BACKUP,
                'category' => self::CATEGORY_CORE,
                'key' => 'backup_retention_days',
                'value' => 30,
                'default_value' => 30,
                'min_value' => 1,
                'max_value' => 365,
                'type' => self::TYPE_INTEGER,
                'label' => 'Backup Aufbewahrung (Tage)',
                'description' => 'Wie lange Backups aufbewahrt werden',
                'rules' => ['integer', 'min:1', 'max:365'],
                'priority' => 900,
            ],

            // API Settings
            [
                'group' => self::GROUP_API,
                'category' => self::CATEGORY_ADVANCED,
                'key' => 'api_version',
                'value' => 'v1',
                'default_value' => 'v1',
                'type' => self::TYPE_SELECT,
                'label' => 'API Version',
                'description' => 'Aktuelle API Version',
                'options' => ['v1', 'v2'],
                'priority' => 1000,
            ],
            [
                'group' => self::GROUP_API,
                'category' => self::CATEGORY_ADVANCED,
                'key' => 'api_timeout',
                'value' => 30,
                'default_value' => 30,
                'min_value' => 5,
                'max_value' => 300,
                'type' => self::TYPE_INTEGER,
                'label' => 'API Timeout (Sekunden)',
                'description' => 'Maximale Wartezeit für API-Anfragen',
                'rules' => ['integer', 'min:5', 'max:300'],
                'priority' => 900,
            ],
        ];

        foreach ($defaults as $setting) {
            static::firstOrCreate(
                ['key' => $setting['key']],
                $setting
            );
        }
    }
}
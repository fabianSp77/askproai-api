<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Builder;
use Carbon\Carbon;

class ActivityLog extends Model
{
    use BelongsToCompany;
    protected $table = 'activity_log';

    protected $fillable = [
        'log_name',
        'description',
        'subject_type',
        'subject_id',
        'causer_type',
        'causer_id',
        'properties',
        'batch_uuid',
        'event',
        'user_id',
        'team_id',
        'company_id',
        'type',
        'severity',
        'ip_address',
        'user_agent',
        'method',
        'url',
        'status_code',
        'response_time',
        'session_id',
        'old_values',
        'new_values',
        'changes',
        'tags',
        'context',
        'is_read',
        'read_at',
        'is_important',
        'is_archived',
        'archived_at',
    ];

    protected $casts = [
        'properties' => 'array',
        'old_values' => 'array',
        'new_values' => 'array',
        'changes' => 'array',
        'tags' => 'array',
        'context' => 'array',
        'is_read' => 'boolean',
        'is_important' => 'boolean',
        'is_archived' => 'boolean',
        'response_time' => 'integer',
        'status_code' => 'integer',
        'read_at' => 'datetime',
        'archived_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Event types
    const TYPE_AUTH = 'auth';
    const TYPE_USER = 'user';
    const TYPE_SYSTEM = 'system';
    const TYPE_DATA = 'data';
    const TYPE_API = 'api';
    const TYPE_ERROR = 'error';
    const TYPE_SECURITY = 'security';
    const TYPE_AUDIT = 'audit';
    const TYPE_PERFORMANCE = 'performance';
    const TYPE_BUSINESS = 'business';
    const TYPE_INTEGRATION = 'integration';
    const TYPE_NOTIFICATION = 'notification';

    // Severity levels
    const SEVERITY_DEBUG = 'debug';
    const SEVERITY_INFO = 'info';
    const SEVERITY_NOTICE = 'notice';
    const SEVERITY_WARNING = 'warning';
    const SEVERITY_ERROR = 'error';
    const SEVERITY_CRITICAL = 'critical';
    const SEVERITY_ALERT = 'alert';
    const SEVERITY_EMERGENCY = 'emergency';

    // Common events
    const EVENT_LOGIN = 'login';
    const EVENT_LOGOUT = 'logout';
    const EVENT_FAILED_LOGIN = 'failed_login';
    const EVENT_PASSWORD_RESET = 'password_reset';
    const EVENT_2FA_ENABLED = '2fa_enabled';
    const EVENT_2FA_DISABLED = '2fa_disabled';
    const EVENT_CREATED = 'created';
    const EVENT_UPDATED = 'updated';
    const EVENT_DELETED = 'deleted';
    const EVENT_RESTORED = 'restored';
    const EVENT_VIEWED = 'viewed';
    const EVENT_EXPORTED = 'exported';
    const EVENT_IMPORTED = 'imported';
    const EVENT_DOWNLOADED = 'downloaded';
    const EVENT_UPLOADED = 'uploaded';
    const EVENT_API_CALL = 'api_call';
    const EVENT_ERROR = 'error';
    const EVENT_WARNING = 'warning';
    const EVENT_PERMISSION_DENIED = 'permission_denied';
    const EVENT_RATE_LIMITED = 'rate_limited';
    const EVENT_MAINTENANCE = 'maintenance';
    const EVENT_BACKUP = 'backup';
    const EVENT_RESTORE = 'restore';
    const EVENT_SETTING_CHANGED = 'setting_changed';
    const EVENT_EXPORT = 'export';
    const EVENT_IMPORT = 'import';
    const EVENT_SYNC = 'sync';

    /**
     * Boot the model
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($log) {
            // Auto-fill some fields
            if (!$log->log_name) {
                $log->log_name = 'default';
            }
            if (!$log->severity) {
                $log->severity = self::SEVERITY_INFO;
            }
            if (!$log->ip_address && request()) {
                $log->ip_address = request()->ip();
            }
            if (!$log->user_agent && request()) {
                $log->user_agent = request()->userAgent();
            }
            if (!$log->method && request()) {
                $log->method = request()->method();
            }
            if (!$log->url && request()) {
                $log->url = request()->fullUrl();
            }
            if (!$log->session_id && session()) {
                $log->session_id = session()->getId();
            }
            if (!$log->user_id && auth()->check()) {
                $log->user_id = auth()->id();
            }
        });
    }

    /**
     * Get the user that performed the activity
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the team
     */
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    /**
     * Get the company
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get the subject of the activity
     */
    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Get the causer of the activity
     */
    public function causer(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Get type label with icon
     */
    public function getTypeLabelAttribute(): string
    {
        return match($this->type) {
            self::TYPE_AUTH => '🔐 Authentifizierung',
            self::TYPE_USER => '👤 Benutzer',
            self::TYPE_SYSTEM => '⚙️ System',
            self::TYPE_DATA => '📊 Daten',
            self::TYPE_API => '🔌 API',
            self::TYPE_ERROR => '❌ Fehler',
            self::TYPE_SECURITY => '🛡️ Sicherheit',
            self::TYPE_AUDIT => '📋 Audit',
            self::TYPE_PERFORMANCE => '⚡ Performance',
            self::TYPE_BUSINESS => '💼 Geschäft',
            self::TYPE_INTEGRATION => '🔗 Integration',
            default => ucfirst($this->type ?? 'Unbekannt'),
        };
    }

    /**
     * Get event label
     */
    public function getEventLabelAttribute(): string
    {
        return match($this->event) {
            self::EVENT_LOGIN => '✅ Anmeldung',
            self::EVENT_LOGOUT => '🚪 Abmeldung',
            self::EVENT_FAILED_LOGIN => '❌ Fehlgeschlagene Anmeldung',
            self::EVENT_PASSWORD_RESET => '🔑 Passwort zurückgesetzt',
            self::EVENT_2FA_ENABLED => '🔐 2FA aktiviert',
            self::EVENT_2FA_DISABLED => '🔓 2FA deaktiviert',
            self::EVENT_CREATED => '➕ Erstellt',
            self::EVENT_UPDATED => '✏️ Aktualisiert',
            self::EVENT_DELETED => '🗑️ Gelöscht',
            self::EVENT_RESTORED => '♻️ Wiederhergestellt',
            self::EVENT_VIEWED => '👁️ Angesehen',
            self::EVENT_EXPORTED => '📤 Exportiert',
            self::EVENT_IMPORTED => '📥 Importiert',
            self::EVENT_DOWNLOADED => '⬇️ Heruntergeladen',
            self::EVENT_UPLOADED => '⬆️ Hochgeladen',
            self::EVENT_API_CALL => '🔌 API-Aufruf',
            self::EVENT_ERROR => '❌ Fehler',
            self::EVENT_WARNING => '⚠️ Warnung',
            self::EVENT_PERMISSION_DENIED => '🚫 Zugriff verweigert',
            self::EVENT_RATE_LIMITED => '⏱️ Rate-Limited',
            self::EVENT_MAINTENANCE => '🔧 Wartung',
            self::EVENT_BACKUP => '💾 Backup',
            self::EVENT_RESTORE => '♻️ Wiederherstellung',
            self::EVENT_SETTING_CHANGED => '⚙️ Einstellung geändert',
            default => ucfirst(str_replace('_', ' ', $this->event ?? 'Unbekannt')),
        };
    }

    /**
     * Get severity label with icon
     */
    public function getSeverityLabelAttribute(): string
    {
        return match($this->severity) {
            self::SEVERITY_DEBUG => '🔍 Debug',
            self::SEVERITY_INFO => 'ℹ️ Info',
            self::SEVERITY_NOTICE => '📝 Hinweis',
            self::SEVERITY_WARNING => '⚠️ Warnung',
            self::SEVERITY_ERROR => '❌ Fehler',
            self::SEVERITY_CRITICAL => '🔴 Kritisch',
            self::SEVERITY_ALERT => '🚨 Alarm',
            self::SEVERITY_EMERGENCY => '🆘 Notfall',
            default => ucfirst($this->severity ?? 'Info'),
        };
    }

    /**
     * Get severity color
     */
    public function getSeverityColorAttribute(): string
    {
        return match($this->severity) {
            self::SEVERITY_DEBUG => 'gray',
            self::SEVERITY_INFO => 'info',
            self::SEVERITY_NOTICE => 'primary',
            self::SEVERITY_WARNING => 'warning',
            self::SEVERITY_ERROR => 'danger',
            self::SEVERITY_CRITICAL => 'danger',
            self::SEVERITY_ALERT => 'danger',
            self::SEVERITY_EMERGENCY => 'danger',
            default => 'secondary',
        };
    }

    /**
     * Get severity score (for sorting)
     */
    public function getSeverityScoreAttribute(): int
    {
        return match($this->severity) {
            self::SEVERITY_DEBUG => 0,
            self::SEVERITY_INFO => 1,
            self::SEVERITY_NOTICE => 2,
            self::SEVERITY_WARNING => 3,
            self::SEVERITY_ERROR => 4,
            self::SEVERITY_CRITICAL => 5,
            self::SEVERITY_ALERT => 6,
            self::SEVERITY_EMERGENCY => 7,
            default => 1,
        };
    }

    /**
     * Get HTTP status label
     */
    public function getStatusLabelAttribute(): string
    {
        if (!$this->status_code) {
            return '';
        }

        $icon = match(true) {
            $this->status_code >= 200 && $this->status_code < 300 => '✅',
            $this->status_code >= 300 && $this->status_code < 400 => '↪️',
            $this->status_code >= 400 && $this->status_code < 500 => '⚠️',
            $this->status_code >= 500 => '❌',
            default => '❓',
        };

        return "$icon {$this->status_code}";
    }

    /**
     * Get response time label
     */
    public function getResponseTimeLabelAttribute(): string
    {
        if (!$this->response_time) {
            return '';
        }

        $icon = match(true) {
            $this->response_time < 100 => '🚀',
            $this->response_time < 500 => '✅',
            $this->response_time < 1000 => '🐢',
            default => '🐌',
        };

        return "$icon {$this->response_time}ms";
    }

    /**
     * Get detailed description
     */
    public function getDetailedDescriptionAttribute(): string
    {
        $user = $this->user?->name ?? $this->causer?->name ?? 'System';
        $event = $this->event_label;
        $type = $this->type_label;

        if ($this->subject) {
            $subjectClass = class_basename($this->subject_type);
            $subjectName = method_exists($this->subject, 'getName')
                ? $this->subject->getName()
                : "#{$this->subject_id}";
            return "$user: $event für $subjectClass $subjectName";
        }

        if ($this->description) {
            return $this->description;
        }

        return "$user: $event ($type)";
    }

    /**
     * Get formatted properties for display
     */
    public function getFormattedPropertiesAttribute(): array
    {
        $formatted = [];

        // Old values
        if ($this->old_values && count($this->old_values) > 0) {
            $formatted['🕒 Alte Werte'] = $this->old_values;
        }

        // New values
        if ($this->new_values && count($this->new_values) > 0) {
            $formatted['✨ Neue Werte'] = $this->new_values;
        }

        // Changes summary
        if ($this->changes && count($this->changes) > 0) {
            $formatted['📝 Änderungen'] = $this->changes;
        }

        // Additional properties
        if ($this->properties && count($this->properties) > 0) {
            foreach ($this->properties as $key => $value) {
                if (!in_array($key, ['old', 'attributes', 'changes'])) {
                    $formatted[ucfirst(str_replace('_', ' ', $key))] = $value;
                }
            }
        }

        // Context
        if ($this->context && count($this->context) > 0) {
            $formatted['🔍 Kontext'] = $this->context;
        }

        // Tags
        if ($this->tags && count($this->tags) > 0) {
            $formatted['🏷️ Tags'] = $this->tags;
        }

        return $formatted;
    }

    /**
     * Get time ago label
     */
    public function getTimeAgoAttribute(): string
    {
        return $this->created_at->diffForHumans();
    }

    /**
     * Get formatted date
     */
    public function getFormattedDateAttribute(): string
    {
        return $this->created_at->format('d.m.Y H:i:s');
    }

    /**
     * Check if log is recent (last 5 minutes)
     */
    public function getIsRecentAttribute(): bool
    {
        return $this->created_at->greaterThan(now()->subMinutes(5));
    }

    /**
     * Check if log is old (older than 30 days)
     */
    public function getIsOldAttribute(): bool
    {
        return $this->created_at->lessThan(now()->subDays(30));
    }

    /**
     * Mark as read
     */
    public function markAsRead(): void
    {
        if (!$this->is_read) {
            $this->update([
                'is_read' => true,
                'read_at' => now(),
            ]);
        }
    }

    /**
     * Mark as important
     */
    public function markAsImportant(): void
    {
        $this->update(['is_important' => true]);
    }

    /**
     * Archive the log
     */
    public function archive(): void
    {
        if (!$this->is_archived) {
            $this->update([
                'is_archived' => true,
                'archived_at' => now(),
            ]);
        }
    }

    /**
     * Scopes
     */
    public function scopeUnread(Builder $query): Builder
    {
        return $query->where('is_read', false);
    }

    public function scopeRead(Builder $query): Builder
    {
        return $query->where('is_read', true);
    }

    public function scopeImportant(Builder $query): Builder
    {
        return $query->where('is_important', true);
    }

    public function scopeNotArchived(Builder $query): Builder
    {
        return $query->where('is_archived', false);
    }

    public function scopeArchived(Builder $query): Builder
    {
        return $query->where('is_archived', true);
    }

    public function scopeByUser(Builder $query, $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    public function scopeByTeam(Builder $query, $teamId): Builder
    {
        return $query->where('team_id', $teamId);
    }

    public function scopeByCompany(Builder $query, $companyId): Builder
    {
        return $query->where('company_id', $companyId);
    }

    public function scopeOfType(Builder $query, $type): Builder
    {
        return $query->where('type', $type);
    }

    public function scopeOfSeverity(Builder $query, $severity): Builder
    {
        return $query->where('severity', $severity);
    }

    public function scopeForEvent(Builder $query, $event): Builder
    {
        return $query->where('event', $event);
    }

    public function scopeWithTag(Builder $query, string $tag): Builder
    {
        return $query->whereJsonContains('tags', $tag);
    }

    public function scopeToday(Builder $query): Builder
    {
        return $query->whereDate('created_at', today());
    }

    public function scopeYesterday(Builder $query): Builder
    {
        return $query->whereDate('created_at', today()->subDay());
    }

    public function scopeThisWeek(Builder $query): Builder
    {
        return $query->whereBetween('created_at', [
            now()->startOfWeek(),
            now()->endOfWeek(),
        ]);
    }

    public function scopeThisMonth(Builder $query): Builder
    {
        return $query->whereBetween('created_at', [
            now()->startOfMonth(),
            now()->endOfMonth(),
        ]);
    }

    public function scopeBetweenDates(Builder $query, $startDate, $endDate): Builder
    {
        return $query->whereBetween('created_at', [$startDate, $endDate]);
    }

    public function scopeHighSeverity(Builder $query): Builder
    {
        return $query->whereIn('severity', [
            self::SEVERITY_ERROR,
            self::SEVERITY_CRITICAL,
            self::SEVERITY_ALERT,
            self::SEVERITY_EMERGENCY,
        ]);
    }

    public function scopeLowSeverity(Builder $query): Builder
    {
        return $query->whereIn('severity', [
            self::SEVERITY_DEBUG,
            self::SEVERITY_INFO,
            self::SEVERITY_NOTICE,
        ]);
    }

    public function scopeErrors(Builder $query): Builder
    {
        return $query->where('type', self::TYPE_ERROR)
            ->orWhere('severity', '>=', self::SEVERITY_ERROR);
    }

    public function scopeApiCalls(Builder $query): Builder
    {
        return $query->where('type', self::TYPE_API)
            ->orWhere('event', self::EVENT_API_CALL);
    }

    public function scopeAuthentication(Builder $query): Builder
    {
        return $query->where('type', self::TYPE_AUTH)
            ->orWhereIn('event', [
                self::EVENT_LOGIN,
                self::EVENT_LOGOUT,
                self::EVENT_FAILED_LOGIN,
                self::EVENT_PASSWORD_RESET,
            ]);
    }

    /**
     * Static logging methods
     */
    public static function log(
        string $type,
        string $event,
        ?string $description = null,
        ?Model $subject = null,
        array $properties = [],
        ?Model $causer = null,
        ?string $severity = null
    ): self {
        $log = new static([
            'type' => $type,
            'event' => $event,
            'description' => $description,
            'severity' => $severity ?? self::SEVERITY_INFO,
            'properties' => $properties,
            'user_id' => auth()->id(),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'method' => request()->method(),
            'url' => request()->fullUrl(),
        ]);

        if ($subject) {
            $log->subject()->associate($subject);
        }

        if ($causer) {
            $log->causer()->associate($causer);
        } elseif (auth()->check()) {
            $log->causer()->associate(auth()->user());
        }

        $log->save();

        return $log;
    }

    public static function logAuth(string $event, ?string $description = null, array $properties = []): self
    {
        $severity = match($event) {
            self::EVENT_FAILED_LOGIN, self::EVENT_PERMISSION_DENIED => self::SEVERITY_WARNING,
            default => self::SEVERITY_INFO,
        };

        return static::log(self::TYPE_AUTH, $event, $description, null, $properties, null, $severity);
    }

    public static function logSystem(string $event, ?string $description = null, array $properties = []): self
    {
        return static::log(self::TYPE_SYSTEM, $event, $description, null, $properties, null, self::SEVERITY_INFO);
    }

    public static function logError(string $message, array $context = [], ?Model $subject = null): self
    {
        return static::log(
            self::TYPE_ERROR,
            self::EVENT_ERROR,
            $message,
            $subject,
            ['context' => $context],
            null,
            self::SEVERITY_ERROR
        );
    }

    public static function logApi(
        string $endpoint,
        string $method,
        int $statusCode,
        int $responseTime,
        array $properties = []
    ): self {
        $severity = match(true) {
            $statusCode >= 500 => self::SEVERITY_ERROR,
            $statusCode >= 400 => self::SEVERITY_WARNING,
            default => self::SEVERITY_INFO,
        };

        return static::log(
            self::TYPE_API,
            self::EVENT_API_CALL,
            "$method $endpoint",
            null,
            array_merge($properties, [
                'endpoint' => $endpoint,
                'method' => $method,
                'status_code' => $statusCode,
                'response_time' => $responseTime,
            ]),
            null,
            $severity
        );
    }

    public static function logModelChanges(Model $model, string $event): self
    {
        $properties = [];

        if ($event === self::EVENT_UPDATED) {
            $properties['old_values'] = $model->getOriginal();
            $properties['new_values'] = $model->getAttributes();
            $properties['changes'] = $model->getChanges();
        } elseif ($event === self::EVENT_CREATED) {
            $properties['new_values'] = $model->getAttributes();
        } elseif ($event === self::EVENT_DELETED) {
            $properties['old_values'] = $model->getAttributes();
        }

        return static::log(
            self::TYPE_DATA,
            $event,
            class_basename($model) . " $event",
            $model,
            $properties
        );
    }

    /**
     * Clean old logs
     */
    public static function cleanOldLogs(int $days = 90): int
    {
        return static::where('created_at', '<', now()->subDays($days))
            ->where('is_important', false)
            ->delete();
    }

    /**
     * Get statistics
     */
    public static function getStatistics(?Carbon $startDate = null, ?Carbon $endDate = null): array
    {
        $query = static::query();

        if ($startDate && $endDate) {
            $query->whereBetween('created_at', [$startDate, $endDate]);
        }

        return [
            'total' => $query->count(),
            'by_type' => $query->groupBy('type')
                ->selectRaw('type, COUNT(*) as count')
                ->pluck('count', 'type')
                ->toArray(),
            'by_severity' => $query->groupBy('severity')
                ->selectRaw('severity, COUNT(*) as count')
                ->pluck('count', 'severity')
                ->toArray(),
            'errors' => $query->errors()->count(),
            'warnings' => $query->ofSeverity(self::SEVERITY_WARNING)->count(),
            'api_calls' => $query->apiCalls()->count(),
            'auth_events' => $query->authentication()->count(),
            'unread' => $query->unread()->count(),
            'important' => $query->important()->count(),
        ];
    }
}
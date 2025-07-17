<?php

namespace App\Models;

use App\Scopes\TenantScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CallActivity extends Model
{
    use HasFactory;

    protected $fillable = [
        'call_id',
        'company_id',
        'user_id',
        'activity_type',
        'title',
        'description',
        'metadata',
        'icon',
        'color',
        'is_system',
    ];

    protected $casts = [
        'metadata' => 'array',
        'is_system' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Activity types constants
    const TYPE_CALL_RECEIVED = 'call_received';
    const TYPE_CALL_ENDED = 'call_ended';
    const TYPE_STATUS_CHANGED = 'status_changed';
    const TYPE_ASSIGNED = 'assigned';
    const TYPE_EMAIL_SENT = 'email_sent';
    const TYPE_NOTE_ADDED = 'note_added';
    const TYPE_ANALYZED = 'analyzed';
    const TYPE_RECORDING_AVAILABLE = 'recording_available';
    const TYPE_TRANSCRIPT_GENERATED = 'transcript_generated';
    const TYPE_SUMMARY_GENERATED = 'summary_generated';

    // Icon and color mappings
    const ACTIVITY_CONFIG = [
        self::TYPE_CALL_RECEIVED => [
            'icon' => 'Phone',
            'color' => 'blue'
        ],
        self::TYPE_CALL_ENDED => [
            'icon' => 'PhoneOff',
            'color' => 'gray'
        ],
        self::TYPE_STATUS_CHANGED => [
            'icon' => 'Activity',
            'color' => 'orange'
        ],
        self::TYPE_ASSIGNED => [
            'icon' => 'UserCheck',
            'color' => 'green'
        ],
        self::TYPE_EMAIL_SENT => [
            'icon' => 'Send',
            'color' => 'blue'
        ],
        self::TYPE_NOTE_ADDED => [
            'icon' => 'MessageSquare',
            'color' => 'purple'
        ],
        self::TYPE_ANALYZED => [
            'icon' => 'CheckCircle',
            'color' => 'green'
        ],
        self::TYPE_RECORDING_AVAILABLE => [
            'icon' => 'Mic',
            'color' => 'red'
        ],
        self::TYPE_TRANSCRIPT_GENERATED => [
            'icon' => 'FileText',
            'color' => 'blue'
        ],
        self::TYPE_SUMMARY_GENERATED => [
            'icon' => 'FileText',
            'color' => 'green'
        ],
    ];

    protected static function booted()
    {
        static::addGlobalScope(new TenantScope);

        static::creating(function ($activity) {
            if (!$activity->company_id && $activity->call) {
                $activity->company_id = $activity->call->company_id;
            }

            // Auto-set icon and color based on activity type
            if (isset(self::ACTIVITY_CONFIG[$activity->activity_type])) {
                $config = self::ACTIVITY_CONFIG[$activity->activity_type];
                if (!$activity->icon) {
                    $activity->icon = $config['icon'];
                }
                if (!$activity->color) {
                    $activity->color = $config['color'];
                }
            }
        });
    }

    public function call(): BelongsTo
    {
        return $this->belongsTo(Call::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(PortalUser::class, 'user_id');
    }

    /**
     * Create a new activity for a call
     */
    public static function log(Call $call, string $type, string $title, array $data = []): self
    {
        $metadata = $data['metadata'] ?? [];
        unset($data['metadata']);

        return self::create(array_merge([
            'call_id' => $call->id,
            'company_id' => $call->company_id,
            'activity_type' => $type,
            'title' => $title,
            'metadata' => $metadata,
            'is_system' => $data['is_system'] ?? true,
        ], $data));
    }

    /**
     * Get formatted activity for display
     */
    public function getFormattedActivity(): array
    {
        return [
            'id' => $this->id,
            'type' => $this->activity_type,
            'title' => $this->title,
            'description' => $this->description,
            'icon' => $this->icon,
            'color' => $this->color,
            'user' => $this->user ? [
                'id' => $this->user->id,
                'name' => $this->user->name,
            ] : ['name' => 'System'],
            'metadata' => $this->metadata,
            'created_at' => $this->created_at,
            'time_ago' => $this->created_at->diffForHumans(),
        ];
    }
}

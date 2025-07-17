<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Str;

class Notification extends Model
{
    use HasFactory;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id',
        'type',
        'notifiable_type',
        'notifiable_id',
        'data',
        'read_at',
        'category',
        'priority',
        'expires_at'
    ];

    protected $casts = [
        'data' => 'array',
        'read_at' => 'datetime',
        'expires_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->id)) {
                $model->id = (string) Str::uuid();
            }
        });
    }

    public function notifiable(): MorphTo
    {
        return $this->morphTo();
    }

    public function markAsRead()
    {
        if (is_null($this->read_at)) {
            $this->forceFill(['read_at' => $this->freshTimestamp()])->save();
        }
    }

    public function markAsUnread()
    {
        if (!is_null($this->read_at)) {
            $this->forceFill(['read_at' => null])->save();
        }
    }

    public function scopeUnread($query)
    {
        return $query->whereNull('read_at');
    }

    public function scopeRead($query)
    {
        return $query->whereNotNull('read_at');
    }

    public function scopeRecent($query, $days = 7)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    public function scopeByCategory($query, $category)
    {
        return $query->where('category', $category);
    }

    public function scopeHighPriority($query)
    {
        return $query->where('priority', 'high');
    }

    public function getIconAttribute()
    {
        $typeIcons = [
            'appointment.created' => 'calendar',
            'appointment.confirmed' => 'check-circle',
            'appointment.cancelled' => 'x-circle',
            'appointment.reminder' => 'clock',
            'call.received' => 'phone',
            'call.missed' => 'phone-missed',
            'invoice.created' => 'file-text',
            'invoice.paid' => 'dollar',
            'team.member_added' => 'user-plus',
            'system.update' => 'info',
            'system.alert' => 'alert-triangle',
            'feedback.received' => 'message-square',
            'feedback.responded' => 'message-circle'
        ];

        return $typeIcons[$this->type] ?? 'bell';
    }

    public function getColorAttribute()
    {
        $categoryColors = [
            'appointment' => 'blue',
            'call' => 'green',
            'invoice' => 'orange',
            'team' => 'purple',
            'system' => 'gray',
            'feedback' => 'cyan'
        ];

        $category = explode('.', $this->type)[0];
        return $categoryColors[$category] ?? 'default';
    }
}
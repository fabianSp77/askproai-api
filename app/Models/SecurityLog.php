<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SecurityLog extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'type',
        'ip_address',
        'user_agent',
        'user_id',
        'company_id',
        'url',
        'method',
        'data',
        'created_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'data' => 'array',
        'created_at' => 'datetime',
    ];

    /**
     * Get the user that triggered this security event.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the company associated with this security event.
     */
    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Scope to filter by event type.
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope to filter by IP address.
     */
    public function scopeFromIp($query, string $ip)
    {
        return $query->where('ip_address', $ip);
    }

    /**
     * Scope to filter by time period.
     */
    public function scopeInLastHours($query, int $hours)
    {
        return $query->where('created_at', '>=', now()->subHours($hours));
    }
}
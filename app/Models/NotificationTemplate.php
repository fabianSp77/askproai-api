<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;

class NotificationTemplate extends Model
{
    use BelongsToCompany;
    protected $fillable = [
        'key',
        'name',
        'description',
        'channel',
        'type',
        'subject',
        'content',
        'variables',
        'metadata',
        'is_active',
        'priority'
    ];

    protected $casts = [
        'subject' => 'array',
        'content' => 'array',
        'variables' => 'array',
        'metadata' => 'array',
        'is_active' => 'boolean'
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByChannel($query, string $channel)
    {
        return $query->where('channel', $channel);
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);
    }

    public function getSubjectForLanguage(string $language): ?string
    {
        return $this->subject[$language] ?? $this->subject['de'] ?? null;
    }

    public function getContentForLanguage(string $language): ?string
    {
        return $this->content[$language] ?? $this->content['de'] ?? null;
    }
}
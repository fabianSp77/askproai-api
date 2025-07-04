<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Scopes\TenantScope;

class NotificationTemplate extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'key',
        'channel',
        'translations',
        'variables',
        'metadata',
        'is_active',
        'is_system'
    ];

    protected $casts = [
        'translations' => 'array',
        'variables' => 'array',
        'metadata' => 'array',
        'is_active' => 'boolean',
        'is_system' => 'boolean'
    ];

    /**
     * Apply the tenant scope
     */
    protected static function booted()
    {
        static::addGlobalScope(new TenantScope);
    }

    /**
     * Get the company that owns the template
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get translation for a specific language
     */
    public function getTranslation(string $language, ?string $fallback = null): ?array
    {
        // Try requested language
        if (isset($this->translations[$language])) {
            return $this->translations[$language];
        }

        // Try fallback language
        if ($fallback && isset($this->translations[$fallback])) {
            return $this->translations[$fallback];
        }

        // Try company default
        if ($this->company && isset($this->translations[$this->company->default_language])) {
            return $this->translations[$this->company->default_language];
        }

        // Try German as final fallback
        if (isset($this->translations['de'])) {
            return $this->translations['de'];
        }

        // Return first available translation
        return !empty($this->translations) ? reset($this->translations) : null;
    }

    /**
     * Check if template has translation for a language
     */
    public function hasTranslation(string $language): bool
    {
        return isset($this->translations[$language]);
    }

    /**
     * Add or update a translation
     */
    public function setTranslation(string $language, array $content): void
    {
        $translations = $this->translations;
        $translations[$language] = $content;
        $this->translations = $translations;
        $this->save();
    }

    /**
     * Get all available languages for this template
     */
    public function getAvailableLanguages(): array
    {
        return array_keys($this->translations);
    }

    /**
     * Render template with variables
     */
    public function render(string $language, array $variables = []): ?array
    {
        $translation = $this->getTranslation($language);
        
        if (!$translation) {
            return null;
        }

        $rendered = [];
        
        // Replace variables in each field
        foreach ($translation as $field => $content) {
            $rendered[$field] = $this->replaceVariables($content, $variables);
        }

        return $rendered;
    }

    /**
     * Replace template variables
     */
    private function replaceVariables(string $content, array $variables): string
    {
        foreach ($variables as $key => $value) {
            $content = str_replace('{{' . $key . '}}', $value, $content);
        }

        return $content;
    }

    /**
     * Scope for active templates
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for specific channel
     */
    public function scopeForChannel($query, string $channel)
    {
        return $query->where('channel', $channel);
    }

    /**
     * Scope for specific key
     */
    public function scopeForKey($query, string $key)
    {
        return $query->where('key', $key);
    }
}
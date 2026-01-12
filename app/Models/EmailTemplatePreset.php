<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Email Template Preset Model
 *
 * Global presets for email templates - NOT company-scoped.
 * These are system-wide templates that companies can clone into their own templates.
 *
 * @property int $id
 * @property string $key Unique identifier for the preset
 * @property string $name Display name
 * @property string|null $description Description of the preset
 * @property string $subject Email subject line
 * @property string $body_html HTML template body
 * @property string|null $variables_hint List of variables used in this preset
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 */
class EmailTemplatePreset extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'key',
        'name',
        'description',
        'subject',
        'body_html',
        'variables_hint',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }
}

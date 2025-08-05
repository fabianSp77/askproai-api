<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PromptTemplate extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'content',
        'variables',
        'parent_id',
        'category',
        'version',
        'is_active',
        'metadata',
    ];

    protected $casts = [
        'variables' => 'array',
        'metadata' => 'array',
        'is_active' => 'boolean',
    ];

    public function parent()
    {
        return $this->belongsTo(PromptTemplate::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(PromptTemplate::class, 'parent_id');
    }

    /**
     * Compile the template with given variables
     */
    public function compile(array $variables = []): string
    {
        $content = $this->content;
        
        // Replace variables in the format {{variable_name}}
        foreach ($variables as $key => $value) {
            $content = str_replace('{{' . $key . '}}', $value, $content);
        }
        
        // Also support {variable_name} format
        foreach ($variables as $key => $value) {
            $content = str_replace('{' . $key . '}', $value, $content);
        }
        
        return $content;
    }
}
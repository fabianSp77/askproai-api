<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IndustryTemplate extends Model
{
    protected $fillable = [
        'slug',
        'name',
        'name_en',
        'icon',
        'description',
        'default_services',
        'default_hours',
        'ai_personality',
        'common_questions',
        'booking_rules',
        'setup_time_estimate',
        'popularity_score',
        'is_active',
    ];

    protected $casts = [
        'default_services' => 'array',
        'default_hours' => 'array',
        'ai_personality' => 'array',
        'common_questions' => 'array',
        'booking_rules' => 'array',
        'is_active' => 'boolean',
    ];

    /**
     * Get formatted working hours for display.
     */
    public function getFormattedHoursAttribute(): array
    {
        $formatted = [];
        $days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
        
        foreach ($days as $day) {
            if ($this->default_hours[$day] ?? false) {
                $hours = $this->default_hours[$day];
                if (count($hours) === 4) {
                    // Split shift
                    $formatted[$day] = "{$hours[0]}-{$hours[1]}, {$hours[2]}-{$hours[3]}";
                } else {
                    // Single shift
                    $formatted[$day] = "{$hours[0]}-{$hours[1]}";
                }
            } else {
                $formatted[$day] = 'Geschlossen';
            }
        }
        
        return $formatted;
    }

    /**
     * Apply template to a company.
     */
    public function applyToCompany($company)
    {
        // Apply services
        foreach ($this->default_services as $service) {
            $company->services()->create([
                'name' => $service['name'],
                'duration' => $service['duration'],
                'price' => $service['price'],
                'is_active' => true,
            ]);
        }
        
        // Apply working hours
        $branch = $company->branches()->first();
        if ($branch) {
            foreach ($this->default_hours as $day => $hours) {
                if ($hours) {
                    $branch->workingHours()->create([
                        'day_of_week' => array_search($day, ['sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday']),
                        'start_time' => $hours[0],
                        'end_time' => count($hours) === 2 ? $hours[1] : $hours[1],
                        'break_start' => count($hours) === 4 ? $hours[1] : null,
                        'break_end' => count($hours) === 4 ? $hours[2] : null,
                    ]);
                }
            }
        }
        
        return true;
    }

    /**
     * Scope for active templates.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for popular templates.
     */
    public function scopePopular($query)
    {
        return $query->orderBy('popularity_score', 'desc');
    }
}
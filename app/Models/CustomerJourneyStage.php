<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CustomerJourneyStage extends Model
{
    protected $fillable = [
        'code',
        'name',
        'description',
        'order',
        'color',
        'icon',
        'next_stages',
        'automation_rules',
        'is_active'
    ];
    
    protected $casts = [
        'next_stages' => 'array',
        'automation_rules' => 'array',
        'is_active' => 'boolean',
        'order' => 'integer'
    ];
    
    /**
     * Get all active stages in order
     */
    public static function getActiveStages()
    {
        return static::where('is_active', true)
            ->orderBy('order')
            ->get();
    }
    
    /**
     * Get stage by code
     */
    public static function getByCode($code)
    {
        return static::where('code', $code)->first();
    }
    
    /**
     * Check if a transition is valid
     */
    public function canTransitionTo($targetStageCode)
    {
        if (!$this->next_stages) {
            return false;
        }
        
        return in_array($targetStageCode, $this->next_stages);
    }
    
    /**
     * Get all possible next stages
     */
    public function getNextStages()
    {
        if (!$this->next_stages) {
            return collect();
        }
        
        return static::whereIn('code', $this->next_stages)
            ->orderBy('order')
            ->get();
    }
}
<?php

namespace App\Models\Traits;

trait HasLoadingProfiles
{
    /**
     * Initialize loading profiles for the model
     */
    public static function bootHasLoadingProfiles(): void
    {
        static::defineLoadingProfiles();
    }
    
    /**
     * Define the loading profiles for this model
     * Override this method in your model
     */
    protected static function defineLoadingProfiles(): void
    {
        // Example implementation - override in model
        static::defineLoadingProfile('minimal', []);
        static::defineLoadingProfile('standard', []);
        static::defineLoadingProfile('full', []);
        static::defineLoadingProfile('counts', []);
    }
}
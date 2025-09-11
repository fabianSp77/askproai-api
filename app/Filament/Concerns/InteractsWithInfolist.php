<?php

namespace App\Filament\Concerns;

use Filament\Infolists\Infolist;

/**
 * Trait to fix ViewRecord infolist rendering issues
 * 
 * This trait overrides critical methods to ensure infolists are properly
 * rendered in ViewRecord-based pages. Without these overrides, ViewRecord's
 * hasInfolist() returns false by default, causing the entire infolist
 * rendering pipeline to be skipped.
 * 
 * @package App\Filament\Concerns
 */
trait InteractsWithInfolist
{
    /**
     * Cache for initialized infolists
     */
    protected array $cachedInfolists = [];
    
    /**
     * Force infolist availability
     * 
     * Override ViewRecord's hasInfolist() to always return true,
     * ensuring the infolist rendering pipeline is activated.
     */
    protected function hasInfolist(): bool
    {
        // Always return true to force infolist rendering
        return true;
    }
    
    /**
     * Override mount to ensure proper initialization
     * 
     * Extends the parent mount method to ensure the record is properly loaded
     * with all necessary relationships.
     */
    public function mount(int | string $record): void
    {
        // First call parent mount without the hasInfolist check
        $this->record = $this->resolveRecord($record);
        $this->authorizeAccess();
        
        // Always initialize infolist, never fall back to form
        // This bypasses the hasInfolist() check in parent mount
    }
    
    /**
     * Ensure the infolist is properly initialized
     * 
     * This method is called by Filament during the rendering process
     */
    public function getInfolist(string $name): Infolist
    {
        if (!isset($this->cachedInfolists[$name])) {
            $this->cachedInfolists[$name] = $this->makeInfolist()
                ->statePath($name)
                ->record($this->getRecord());
                
            // Call the infolist method if it exists
            if (method_exists($this, 'infolist')) {
                $this->cachedInfolists[$name] = $this->infolist($this->cachedInfolists[$name]);
            }
        }
        
        return $this->cachedInfolists[$name];
    }
}
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/**
 * This middleware patches the Livewire Redirector to add a headers property
 * to prevent "Undefined property" errors in other middleware
 */
class PatchLivewireRedirector
{
    public function handle(Request $request, Closure $next)
    {
        // Add headers property to Livewire Redirector if it doesn't exist
        if (class_exists(\Livewire\Features\SupportRedirects\Redirector::class)) {
            $redirectorClass = \Livewire\Features\SupportRedirects\Redirector::class;
            
            // Use reflection to check if headers property exists
            $reflection = new \ReflectionClass($redirectorClass);
            $hasHeaders = false;
            
            foreach ($reflection->getProperties() as $property) {
                if ($property->getName() === 'headers') {
                    $hasHeaders = true;
                    break;
                }
            }
            
            // If headers property doesn't exist, add it dynamically
            if (!$hasHeaders && !property_exists($redirectorClass, 'headers')) {
                // Create a dummy headers object that won't cause errors
                $dummyHeaders = new class {
                    public function set($key, $value) { return $this; }
                    public function get($key) { return null; }
                    public function has($key) { return false; }
                    public function all() { return []; }
                    public function __call($method, $args) { return null; }
                };
                
                // Add headers property to all Redirector instances
                $redirectorClass::macro('headers', function() use ($dummyHeaders) {
                    return $dummyHeaders;
                });
                
                // Also add it as a property
                $redirectorClass::$headers = $dummyHeaders;
            }
        }
        
        return $next($request);
    }
}
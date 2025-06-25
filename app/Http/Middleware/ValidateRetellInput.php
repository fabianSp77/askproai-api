<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class ValidateRetellInput
{
    /**
     * Handle an incoming request and validate Retell-specific input
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        // Get args from request (Retell sends data in 'args' parameter)
        $args = $request->input('args', []);
        
        // Define validation rules based on the endpoint
        $rules = $this->getValidationRules($request->route()->getName());
        
        // Create validator
        $validator = Validator::make($args, $rules);
        
        // Check if validation fails
        if ($validator->fails()) {
            Log::warning('Retell input validation failed', [
                'route' => $request->route()->getName(),
                'errors' => $validator->errors()->toArray(),
                'ip' => $request->ip()
            ]);
            
            return response()->json([
                'success' => false,
                'error' => 'Invalid input data',
                'details' => $validator->errors()
            ], 422);
        }
        
        // Sanitize input data
        $sanitized = $this->sanitizeInput($args);
        $request->merge(['args' => $sanitized]);
        
        return $next($request);
    }
    
    /**
     * Get validation rules based on route name
     */
    protected function getValidationRules(string $routeName): array
    {
        $baseRules = [
            'call_id' => 'nullable|string|max:100|regex:/^[a-zA-Z0-9_-]+$/',
            'to_number' => 'nullable|string|max:20|regex:/^[+0-9\s\-()]+$/',
            'from_number' => 'nullable|string|max:20|regex:/^[+0-9\s\-()]+$/',
        ];
        
        $specificRules = [
            'api.retell.identify-customer' => [
                'phone_number' => 'nullable|string|max:20|regex:/^[+0-9\s\-()]+$/',
                'telefonnummer' => 'nullable|string|max:20|regex:/^[+0-9\s\-()]+$/',
            ],
            'api.retell.save-preference' => [
                'customer_id' => 'required|integer|exists:customers,id',
                'preference_type' => 'required|string|in:time,weekday,staff,service,language',
                'preference_key' => 'required|string|max:50|regex:/^[a-zA-Z0-9_-]+$/',
                'preference_value' => 'nullable|string|max:255',
            ],
            'api.retell.apply-vip-benefits' => [
                'customer_id' => 'required|integer|exists:customers,id',
                'booking_data' => 'nullable|array',
                'booking_data.*.service_id' => 'nullable|integer',
                'booking_data.*.start_time' => 'nullable|date',
            ],
        ];
        
        return array_merge($baseRules, $specificRules[$routeName] ?? []);
    }
    
    /**
     * Sanitize input data to prevent XSS and other attacks
     */
    protected function sanitizeInput(array $data): array
    {
        $sanitized = [];
        
        foreach ($data as $key => $value) {
            if (is_string($value)) {
                // Remove any HTML tags
                $value = strip_tags($value);
                // Trim whitespace
                $value = trim($value);
                // Encode special characters
                $value = htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
            } elseif (is_array($value)) {
                // Recursively sanitize arrays
                $value = $this->sanitizeInput($value);
            }
            
            $sanitized[$key] = $value;
        }
        
        return $sanitized;
    }
}
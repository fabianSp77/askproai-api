<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Log;
use App\Security\InputValidator;

class InputValidationMiddleware
{
    private InputValidator $validator;

    public function __construct(InputValidator $validator)
    {
        $this->validator = $validator;
    }

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Validate all input data
        $errors = [];
        
        // Check for common injection patterns in all input
        foreach ($request->all() as $key => $value) {
            if (is_string($value)) {
                // Check for SQL injection patterns
                if ($this->validator->containsSqlInjection($value)) {
                    $errors[] = "Invalid characters detected in field: $key";
                    Log::warning('Potential SQL injection attempt', [
                        'field' => $key,
                        'value' => substr($value, 0, 100),
                        'ip' => $request->ip(),
                        'url' => $request->fullUrl()
                    ]);
                }
                
                // Check for XSS patterns
                if ($this->validator->containsXss($value)) {
                    $errors[] = "HTML/Script tags not allowed in field: $key";
                    Log::warning('Potential XSS attempt', [
                        'field' => $key,
                        'value' => substr($value, 0, 100),
                        'ip' => $request->ip(),
                        'url' => $request->fullUrl()
                    ]);
                }
                
                // Check for path traversal
                if ($this->validator->containsPathTraversal($value)) {
                    $errors[] = "Invalid file path in field: $key";
                    Log::warning('Potential path traversal attempt', [
                        'field' => $key,
                        'value' => substr($value, 0, 100),
                        'ip' => $request->ip(),
                        'url' => $request->fullUrl()
                    ]);
                }
            }
        }
        
        // Validate specific fields based on route
        $errors = array_merge($errors, $this->validateRouteSpecificFields($request));
        
        // If errors found, return validation error response
        if (!empty($errors)) {
            return response()->json([
                'error' => 'Validation failed',
                'messages' => $errors
            ], 422);
        }
        
        // Sanitize input before passing to application
        $this->sanitizeInput($request);
        
        return $next($request);
    }
    
    /**
     * Validate fields specific to certain routes
     */
    private function validateRouteSpecificFields(Request $request): array
    {
        $errors = [];
        $route = $request->route();
        
        if (!$route) {
            return $errors;
        }
        
        // Phone number validation for specific routes
        if (in_array($route->getName(), ['api.appointments.store', 'api.customers.store', 'api.customers.update'])) {
            if ($request->has('phone')) {
                $phone = $request->input('phone');
                if (!$this->validator->isValidPhoneNumber($phone)) {
                    $errors[] = 'Invalid phone number format';
                }
            }
        }
        
        // Email validation
        if ($request->has('email')) {
            $email = $request->input('email');
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errors[] = 'Invalid email format';
            }
        }
        
        // Date validation
        foreach (['start_time', 'end_time', 'date', 'start', 'end'] as $field) {
            if ($request->has($field)) {
                $date = $request->input($field);
                if (!$this->validator->isValidDateTime($date)) {
                    $errors[] = "Invalid date/time format in field: $field";
                }
            }
        }
        
        return $errors;
    }
    
    /**
     * Sanitize input data
     */
    private function sanitizeInput(Request $request): void
    {
        $sanitized = [];
        
        foreach ($request->all() as $key => $value) {
            if (is_string($value)) {
                // Trim whitespace
                $value = trim($value);
                
                // Remove null bytes
                $value = str_replace(chr(0), '', $value);
                
                // Normalize line breaks
                $value = str_replace(["\r\n", "\r"], "\n", $value);
                
                $sanitized[$key] = $value;
            } else {
                $sanitized[$key] = $value;
            }
        }
        
        $request->merge($sanitized);
    }
}
<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ScreenshotAuthService
{
    /**
     * Get authentication cookies for screenshot service
     */
    public function getAuthCookies(): array
    {
        // Option 1: Use API token from environment
        if ($token = config('screenshot.api_token')) {
            return [
                [
                    'name' => 'api_token',
                    'value' => $token,
                    'domain' => parse_url(config('app.url'), PHP_URL_HOST),
                    'path' => '/',
                ]
            ];
        }
        
        // Option 2: Create a temporary session
        return $this->createTemporarySession();
    }
    
    /**
     * Create a temporary session for screenshot capture
     */
    protected function createTemporarySession(): array
    {
        try {
            // Find the screenshot service user or first admin
            $user = User::where('email', config('screenshot.service_email', 'screenshot@askproai.de'))
                ->orWhere('is_admin', true)
                ->first();
            
            if (!$user) {
                Log::warning('No user found for screenshot authentication');
                return [];
            }
            
            // Create a temporary token
            $token = $user->createToken('screenshot-service', ['read'])->plainTextToken;
            
            return [
                [
                    'name' => 'Authorization',
                    'value' => 'Bearer ' . $token,
                    'domain' => parse_url(config('app.url'), PHP_URL_HOST),
                    'path' => '/',
                ]
            ];
            
        } catch (\Exception $e) {
            Log::error('Failed to create screenshot session', [
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }
    
    /**
     * Authenticate via HTTP request (for testing)
     */
    public function authenticateViaHttp(string $email, string $password): ?array
    {
        try {
            $response = Http::post(config('app.url') . '/api/login', [
                'email' => $email,
                'password' => $password,
            ]);
            
            if ($response->successful()) {
                $data = $response->json();
                $token = $data['token'] ?? null;
                
                if ($token) {
                    return [
                        [
                            'name' => 'Authorization',
                            'value' => 'Bearer ' . $token,
                            'domain' => parse_url(config('app.url'), PHP_URL_HOST),
                            'path' => '/',
                        ]
                    ];
                }
            }
        } catch (\Exception $e) {
            Log::error('HTTP authentication failed', [
                'error' => $e->getMessage()
            ]);
        }
        
        return null;
    }
}
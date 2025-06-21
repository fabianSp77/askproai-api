<?php

namespace App\Services;

use App\Models\CustomerAuth;
use App\Models\Company;
use App\Notifications\CustomerMagicLinkNotification;
use App\Notifications\CustomerWelcomeNotification;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class CustomerPortalService
{
    /**
     * Enable portal access for a customer.
     */
    public function enablePortalAccess(CustomerAuth $customer, ?string $password = null): bool
    {
        try {
            // Generate random password if not provided
            if (!$password) {
                $password = Str::random(12);
            }
            
            $customer->update([
                'password' => Hash::make($password),
                'portal_enabled' => true,
                'email_verified_at' => now(),
            ]);
            
            // Send welcome email with credentials
            $customer->notify(new CustomerWelcomeNotification($password));
            
            Log::info('Portal access enabled for customer', [
                'customer_id' => $customer->id,
                'email' => $customer->email,
            ]);
            
            return true;
        } catch (\Exception $e) {
            Log::error('Failed to enable portal access', [
                'customer_id' => $customer->id,
                'error' => $e->getMessage(),
            ]);
            
            return false;
        }
    }
    
    /**
     * Disable portal access for a customer.
     */
    public function disablePortalAccess(CustomerAuth $customer): bool
    {
        try {
            $customer->update([
                'portal_enabled' => false,
                'portal_access_token' => null,
                'portal_token_expires_at' => null,
            ]);
            
            // Revoke all API tokens
            $customer->tokens()->delete();
            
            Log::info('Portal access disabled for customer', [
                'customer_id' => $customer->id,
            ]);
            
            return true;
        } catch (\Exception $e) {
            Log::error('Failed to disable portal access', [
                'customer_id' => $customer->id,
                'error' => $e->getMessage(),
            ]);
            
            return false;
        }
    }
    
    /**
     * Send magic link to customer.
     */
    public function sendMagicLink(CustomerAuth $customer, string $token): bool
    {
        try {
            $customer->notify(new CustomerMagicLinkNotification($token));
            
            Log::info('Magic link sent to customer', [
                'customer_id' => $customer->id,
                'email' => $customer->email,
            ]);
            
            return true;
        } catch (\Exception $e) {
            Log::error('Failed to send magic link', [
                'customer_id' => $customer->id,
                'error' => $e->getMessage(),
            ]);
            
            return false;
        }
    }
    
    /**
     * Generate portal URL for company.
     */
    public function getPortalUrl(Company $company): string
    {
        $subdomain = $company->subdomain ?? $company->slug ?? Str::slug($company->name);
        
        // In production, use subdomain
        if (app()->environment('production')) {
            return "https://{$subdomain}." . config('app.domain', 'askproai.de') . '/portal';
        }
        
        // In development, use path prefix
        return config('app.url') . '/portal/' . $subdomain;
    }
    
    /**
     * Check if customer can access portal.
     */
    public function canAccessPortal(CustomerAuth $customer): bool
    {
        return $customer->portal_enabled && 
               $customer->email_verified_at !== null &&
               $customer->company->portal_enabled ?? true;
    }
    
    /**
     * Get portal features for company.
     */
    public function getPortalFeatures(Company $company): array
    {
        $features = [
            'appointments' => true,
            'invoices' => true,
            'profile' => true,
            'cancellation' => true,
            'rescheduling' => false, // TODO: Implement
            'online_booking' => false, // TODO: Implement
            'chat_support' => false, // TODO: Implement
        ];
        
        // Override with company-specific settings
        if ($company->portal_features) {
            $features = array_merge($features, $company->portal_features);
        }
        
        return $features;
    }
    
    /**
     * Get portal statistics for customer.
     */
    public function getCustomerStats(CustomerAuth $customer): array
    {
        $stats = [
            'total_appointments' => $customer->appointments()->count(),
            'upcoming_appointments' => $customer->appointments()
                ->where('start_time', '>=', now())
                ->count(),
            'completed_appointments' => $customer->appointments()
                ->where('status', 'completed')
                ->count(),
            'cancelled_appointments' => $customer->appointments()
                ->where('status', 'cancelled')
                ->count(),
            'no_show_count' => $customer->appointments()
                ->where('status', 'no_show')
                ->count(),
            'total_calls' => $customer->calls()->count(),
            'member_since' => $customer->created_at,
            'last_appointment' => $customer->appointments()
                ->where('status', 'completed')
                ->orderBy('end_time', 'desc')
                ->first()?->end_time,
            'next_appointment' => $customer->appointments()
                ->where('start_time', '>=', now())
                ->orderBy('start_time')
                ->first()?->start_time,
        ];
        
        return $stats;
    }
    
    /**
     * Bulk enable portal access.
     */
    public function bulkEnablePortalAccess(Company $company, array $customerIds = []): array
    {
        $results = [
            'success' => 0,
            'failed' => 0,
            'errors' => [],
        ];
        
        $query = CustomerAuth::where('company_id', $company->id)
            ->where('portal_enabled', false)
            ->whereNotNull('email');
            
        if (!empty($customerIds)) {
            $query->whereIn('id', $customerIds);
        }
        
        $customers = $query->get();
        
        foreach ($customers as $customer) {
            if ($this->enablePortalAccess($customer)) {
                $results['success']++;
            } else {
                $results['failed']++;
                $results['errors'][] = "Failed to enable access for {$customer->email}";
            }
        }
        
        return $results;
    }
}
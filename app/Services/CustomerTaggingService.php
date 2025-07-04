<?php

namespace App\Services;

use App\Models\Customer;
use Carbon\Carbon;

class CustomerTaggingService
{
    public function analyzeAndTag(Customer $customer): void
    {
        $tags = $customer->tags ?? [];
        $originalTags = $tags;
        
        // Remove all auto-generated tags (we'll re-add them based on current data)
        $tags = array_filter($tags, function ($tag) {
            return !in_array($tag, [
                'VIP',
                'Stammkunde',
                'Neukunde',
                'At Risk',
                'Häufige No-Shows',
                'High Value',
                'Geburtstag diesen Monat',
                'Inaktiv',
            ]);
        });
        
        // Calculate metrics
        $completedAppointments = $customer->appointments()->where('status', 'completed')->count();
        $totalRevenue = $customer->appointments()
            ->where('status', 'completed')
            ->join('services', 'appointments.service_id', '=', 'services.id')
            ->sum('services.price') / 100;
        $noShows = $customer->appointments()->where('status', 'no_show')->count();
        $lastAppointment = $customer->appointments()->latest('starts_at')->first();
        
        // New customer (less than 3 appointments)
        if ($completedAppointments < 3) {
            $tags[] = 'Neukunde';
        }
        
        // Regular customer (3-10 appointments)
        elseif ($completedAppointments >= 3 && $completedAppointments < 10) {
            $tags[] = 'Stammkunde';
        }
        
        // VIP customer (10+ appointments or high revenue)
        elseif ($completedAppointments >= 10 || $totalRevenue >= 1000) {
            $tags[] = 'VIP';
        }
        
        // High value customer (>500€ total revenue)
        if ($totalRevenue >= 500) {
            $tags[] = 'High Value';
        }
        
        // Frequent no-shows
        if ($noShows >= 3) {
            $tags[] = 'Häufige No-Shows';
        }
        
        // At risk (no appointment in last 90 days for regular customers)
        if ($completedAppointments >= 3 && $lastAppointment) {
            $daysSinceLastAppointment = $lastAppointment->starts_at->diffInDays(now());
            if ($daysSinceLastAppointment > 90) {
                $tags[] = 'At Risk';
            }
        }
        
        // Inactive (no appointment in last 180 days)
        if ($lastAppointment) {
            $daysSinceLastAppointment = $lastAppointment->starts_at->diffInDays(now());
            if ($daysSinceLastAppointment > 180) {
                $tags[] = 'Inaktiv';
            }
        }
        
        // Birthday this month
        if ($customer->birthdate) {
            $birthdayThisYear = Carbon::createFromFormat('Y-m-d', 
                now()->year . '-' . $customer->birthdate->format('m-d'));
            
            if ($birthdayThisYear->month === now()->month) {
                $tags[] = 'Geburtstag diesen Monat';
            }
        }
        
        // Remove duplicates and update if changed
        $tags = array_unique($tags);
        
        if ($tags !== $originalTags) {
            $customer->update(['tags' => array_values($tags)]);
        }
    }
    
    /**
     * Analyze all customers and update their tags
     */
    public function analyzeAllCustomers(): void
    {
        Customer::chunk(100, function ($customers) {
            foreach ($customers as $customer) {
                $this->analyzeAndTag($customer);
            }
        });
    }
}
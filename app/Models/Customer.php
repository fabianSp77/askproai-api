<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Builder;

class Customer extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'name', 
        'email', 
        'phone', 
        'birthdate',
        'tags',
        'notes'
    ];
    
    protected $casts = [
        'birthdate' => 'date',
        'tags' => 'array'
    ];

    public function branches(): HasMany
    {
        return $this->hasMany(Branch::class);
    }
    
    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }
    
    public function calls(): HasMany
    {
        return $this->hasMany(Call::class);
    }
    
    public function notes(): HasMany
    {
        return $this->hasMany(Note::class);
    }
    
    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Scope for active customers (with appointments)
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->whereHas('appointments', function ($q) {
            $q->where('status', '!=', 'cancelled');
        });
    }

    /**
     * Scope for customers with appointments
     */
    public function scopeWithAppointments(Builder $query): Builder
    {
        return $query->has('appointments');
    }

    /**
     * Scope for customers by phone (including variants)
     */
    public function scopeByPhone(Builder $query, string $phone): Builder
    {
        // Normalize phone number for search
        $normalizedPhone = $this->normalizePhoneNumber($phone);
        
        return $query->where(function ($q) use ($phone, $normalizedPhone) {
            $q->where('phone', $phone)
              ->orWhere('phone', $normalizedPhone)
              ->orWhere('phone', 'LIKE', '%' . substr($normalizedPhone, -10) . '%');
        });
    }

    /**
     * Scope for customers by email
     */
    public function scopeByEmail(Builder $query, string $email): Builder
    {
        return $query->where('email', $email);
    }

    /**
     * Scope for customers by company
     */
    public function scopeForCompany(Builder $query, $companyId): Builder
    {
        return $query->where('company_id', $companyId);
    }

    /**
     * Scope for customers with appointment count
     */
    public function scopeWithAppointmentCount(Builder $query): Builder
    {
        return $query->withCount(['appointments' => function ($q) {
            $q->where('status', '!=', 'cancelled');
        }]);
    }

    /**
     * Scope for customers with recent activity
     */
    public function scopeRecentlyActive(Builder $query, int $days = 30): Builder
    {
        return $query->whereHas('appointments', function ($q) use ($days) {
            $q->where('starts_at', '>=', now()->subDays($days));
        });
    }

    /**
     * Scope for customers with no-shows
     */
    public function scopeWithNoShows(Builder $query): Builder
    {
        return $query->whereHas('appointments', function ($q) {
            $q->where('status', 'no_show');
        });
    }

    /**
     * Scope for customers with no-show count
     */
    public function scopeWithNoShowCount(Builder $query): Builder
    {
        return $query->withCount(['appointments as no_show_count' => function ($q) {
            $q->where('status', 'no_show');
        }]);
    }

    /**
     * Scope for search by name, email or phone
     */
    public function scopeSearch(Builder $query, string $search): Builder
    {
        $normalizedPhone = $this->normalizePhoneNumber($search);
        
        return $query->where(function ($q) use ($search, $normalizedPhone) {
            $q->where('name', 'LIKE', '%' . $search . '%')
              ->orWhere('email', 'LIKE', '%' . $search . '%')
              ->orWhere('phone', 'LIKE', '%' . $search . '%')
              ->orWhere('phone', 'LIKE', '%' . $normalizedPhone . '%');
        });
    }

    /**
     * Normalize phone number for consistent searching
     */
    private function normalizePhoneNumber(string $phone): string
    {
        // Remove all non-numeric characters
        $phone = preg_replace('/[^0-9]/', '', $phone);
        
        // If it starts with country code, keep it
        if (strpos($phone, '49') === 0) {
            return '+' . $phone;
        }
        
        // If it starts with 0, replace with +49
        if (strpos($phone, '0') === 0) {
            return '+49' . substr($phone, 1);
        }
        
        return $phone;
    }
    
    /**
     * Calculate total revenue from all appointments
     */
    public function calculateTotalRevenue(): float
    {
        return $this->appointments()
            ->join('services', 'appointments.service_id', '=', 'services.id')
            ->where('appointments.status', 'completed')
            ->sum('services.price') ?? 0;
    }
}

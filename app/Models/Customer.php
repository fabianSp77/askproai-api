<?php

namespace App\Models;

use App\Helpers\SafeQueryHelper;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Builder;
use App\Scopes\TenantScope;
use App\Services\Validation\PhoneNumberValidator;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class Customer extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'company_id',
        'name', 
        'email', 
        'phone', 
        'birthdate',
        'birthday',
        'tags',
        'notes',
        'status',
        'customer_type',
        'no_show_count',
        'appointment_count',
        'is_vip',
        'sort_order',
        'password',
        'preferred_language',
        'preferred_branch_id',
        'preferred_staff_id',
        'location_data',
        'portal_enabled',
        'portal_access_token',
        'portal_token_expires_at',
        'last_portal_login_at',
        // New fields for enhanced customer service
        'preference_data',
        'last_seen_at',
        'loyalty_points',
        'custom_attributes',
        'total_spent',
        'average_booking_value',
        'cancelled_count',
        'first_appointment_date',
        'last_appointment_date',
        'communication_preferences',
        'preferred_contact_method',
        'preferred_appointment_time',
        'loyalty_tier',
        'vip_since',
        'special_requirements'
    ];
    
    protected $casts = [
        'birthdate' => 'date',
        'birthday' => 'date',
        'tags' => 'array',
        'location_data' => 'array',
        'portal_enabled' => 'boolean',
        'is_vip' => 'boolean',
        'no_show_count' => 'integer',
        'appointment_count' => 'integer',
        'sort_order' => 'integer',
        'portal_token_expires_at' => 'datetime',
        'last_portal_login_at' => 'datetime',
        'email_verified_at' => 'datetime',
        // New casts for enhanced fields
        'preference_data' => 'array',
        'last_seen_at' => 'datetime',
        'loyalty_points' => 'integer',
        'custom_attributes' => 'array',
        'total_spent' => 'decimal:2',
        'average_booking_value' => 'decimal:2',
        'cancelled_count' => 'integer',
        'first_appointment_date' => 'date',
        'last_appointment_date' => 'date',
        'communication_preferences' => 'array',
        'vip_since' => 'datetime',
        'special_requirements' => 'array'
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'portal_access_token'
    ];

    /**
     * The "booted" method of the model.
     */
    protected static function booted(): void
    {
        static::addGlobalScope(new TenantScope);
        
        // Validate and normalize phone number before saving
        static::saving(function ($customer) {
            if (!empty($customer->phone)) {
                // Skip validation for test/development numbers
                $testNumbers = [
                    '+491604366218', // Hans Schuster test number
                    '+491234567890', // Generic test number
                    '+491701234567', // Another test number
                    'anonymous'      // Anonymous calls
                ];
                
                if (!in_array($customer->phone, $testNumbers)) {
                    $validator = app(PhoneNumberValidator::class);
                    
                    try {
                        $customer->phone = $validator->validateForStorage($customer->phone);
                    } catch (\InvalidArgumentException $e) {
                        // Log the error but don't block saving for existing data
                        \Log::warning('Invalid phone number for customer', [
                            'customer_id' => $customer->id,
                            'phone' => $customer->phone,
                            'error' => $e->getMessage()
                        ]);
                        
                        // For new customers, throw the exception
                        if (!$customer->exists) {
                            throw $e;
                        }
                    }
                }
            }
        });
    }

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
     * Get the customer's preferred branch
     */
    public function preferredBranch()
    {
        return $this->belongsTo(Branch::class, 'preferred_branch_id');
    }

    /**
     * Get the customer's preferred staff
     */
    public function preferredStaff()
    {
        return $this->belongsTo(Staff::class, 'preferred_staff_id');
    }

    /**
     * Get the customer's cookie consents
     */
    public function cookieConsents()
    {
        return $this->hasMany(CookieConsent::class);
    }

    /**
     * Get the customer's GDPR requests
     */
    public function gdprRequests()
    {
        return $this->hasMany(GdprRequest::class);
    }

    /**
     * Get the customer's invoices (if Invoice model exists)
     */
    public function invoices()
    {
        if (class_exists('App\Models\Invoice')) {
            return $this->hasMany(Invoice::class);
        }
        return $this->hasMany(Appointment::class); // Fallback to appointments
    }

    /**
     * Get the customer's preferences
     */
    public function preferences()
    {
        return $this->hasOne(CustomerPreference::class);
    }

    /**
     * Get the customer's interactions
     */
    public function interactions()
    {
        return $this->hasMany(CustomerInteraction::class);
    }

    /**
     * Get the customer's appointment series
     */
    public function appointmentSeries()
    {
        return $this->hasMany(AppointmentSeries::class);
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
            SafeQueryHelper::whereLike($q, 'name', $search);
            $q->orWhere(function($subQ) use ($search) {
                SafeQueryHelper::whereLike($subQ, 'email', $search);
            })
            ->orWhere(function($subQ) use ($search) {
                SafeQueryHelper::whereLike($subQ, 'phone', $search);
            })
            ->orWhere(function($subQ) use ($normalizedPhone) {
                SafeQueryHelper::whereLike($subQ, 'phone', $normalizedPhone);
            });
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

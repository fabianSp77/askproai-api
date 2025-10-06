<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PhoneNumber extends Model
{
    use HasFactory, SoftDeletes, BelongsToCompany;

    /**
     * Mass Assignment Protection
     *
     * SECURITY: Protects against VULN-009 - Mass Assignment vulnerability
     * Tenant isolation fields must never be mass-assigned
     */
    protected $guarded = [
        'id',                    // Primary key (string type)

        // Multi-tenant isolation (CRITICAL)
        'company_id',            // Must be set only during creation by admin
        'branch_id',             // Must be set only during creation by admin

        // System timestamps
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    protected $casts = [
        'capabilities' => 'array',
        'metadata' => 'array',
        'routing_config' => 'array',
        'is_active' => 'boolean',
        'is_primary' => 'boolean',
        'sms_enabled' => 'boolean',
        'whatsapp_enabled' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public $incrementing = false;
    protected $keyType = 'string';

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'branch_id', 'id');
    }

    public function calls(): HasMany
    {
        return $this->hasMany(Call::class);
    }

    // Removed retellAgent relationship - RetellAgent model doesn't exist
    // public function retellAgent(): BelongsTo
    // {
    //     return $this->belongsTo(RetellAgent::class, 'retell_agent_id', 'agent_id');
    // }

    /**
     * Get display name for the phone number
     */
    public function getDisplayNameAttribute(): string
    {
        return $this->description ?? $this->number;
    }

    /**
     * Format phone number for display
     */
    public function getFormattedNumberAttribute(): string
    {
        // Simple formatting for German numbers
        $number = preg_replace('/[^0-9+]/', '', $this->number);
        if (strpos($number, '+49') === 0) {
            // Format: +49 XXX XXXXXXX
            return preg_replace('/(\+49)(\d{3})(\d+)/', '$1 $2 $3', $number);
        }
        return $this->number;
    }

    /**
     * Validation rules for the model
     */
    public static function rules($id = null): array
    {
        return [
            'number' => [
                'required',
                'string',
                'max:255',
                'regex:/^\+?[0-9\s\-\(\)]+$/',
                'unique:phone_numbers,number' . ($id ? ',' . $id : '')
            ],
            'company_id' => 'required|exists:companies,id',
            'branch_id' => 'nullable|exists:branches,id',
            'type' => 'required|in:direct,hotline',
            'description' => 'nullable|string|max:255',
            'is_active' => 'boolean',
            'is_primary' => 'boolean',
            'sms_enabled' => 'boolean',
            'whatsapp_enabled' => 'boolean',
            'retell_phone_id' => 'nullable|string|max:255',
            'retell_agent_id' => 'nullable|string|max:255',
            'retell_agent_version' => 'nullable|string|max:255',
            'agent_id' => 'nullable|string|max:255',
            'metadata' => 'nullable|array',
            'capabilities' => 'nullable|array',
            'routing_config' => 'nullable|array',
        ];
    }

    /**
     * Boot the model
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($phoneNumber) {
            if (!$phoneNumber->id) {
                $phoneNumber->id = (string) \Illuminate\Support\Str::uuid();
            }
        });

        static::saving(function ($phoneNumber) {
            // Ensure only one primary number per company
            if ($phoneNumber->is_primary) {
                static::where('company_id', $phoneNumber->company_id)
                    ->where('id', '!=', $phoneNumber->id)
                    ->update(['is_primary' => false]);
            }
        });
    }
}
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Company extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'address', 'contact_person', 'phone', 'email',
        'opening_hours', 'calcom_api_key', 'calcom_user_id',
        'retell_api_key', 'active',
    ];

    protected $casts = [
        'opening_hours' => 'array',
        'active' => 'boolean',
    ];

    public function staff(): HasMany
    {
        return $this->hasMany(Staff::class);
    }

    public function services(): HasMany
    {
        return $this->hasMany(Service::class);
    }

    public function phoneNumbers(): HasMany
    {
        return $this->hasMany(PhoneNumber::class);
    }

    public function retellAgents(): HasMany
    {
        return $this->hasMany(RetellAgent::class);
    }

    public function apiHealthLogs(): HasMany
    {
        return $this->hasMany(ApiHealthLog::class);
    }
}

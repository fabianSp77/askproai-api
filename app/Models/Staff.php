<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Staff extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_id', 'name', 'email', 'phone', 'external_id', 'active'
    ];

    protected $casts = [
        'active' => 'boolean'
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function appointments()
    {
        return $this->hasMany(Appointment::class);
    }
}

public function company(): BelongsTo
{
    return $this->belongsTo(Company::class);
}

public function services(): BelongsToMany
{
    return $this->belongsToMany(Service::class);
}

public function workingHours(): HasMany
{
    return $this->hasMany(WorkingHour::class);
}

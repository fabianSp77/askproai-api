<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Service extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_id', 'name', 'description', 'duration', 'external_id', 'active'
    ];

    protected $casts = [
        'duration' => 'integer',
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

public function staff(): BelongsToMany
{
    return $this->belongsToMany(Staff::class);
}

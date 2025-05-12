<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PhoneNumber extends Model
{
    protected $fillable = ['customer_id', 'phone_number'];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }
}

public function company(): BelongsTo
{
    return $this->belongsTo(Company::class);
}

public function retellAgent(): HasOne
{
    return $this->hasOne(RetellAgent::class);
}

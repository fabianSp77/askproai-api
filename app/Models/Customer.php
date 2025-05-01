<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Customer extends Model
{
    protected $fillable = ['name', 'email', 'phone', 'notes'];

    public function branches(): HasMany
    {
        return $this->hasMany(Branch::class);
    }
}

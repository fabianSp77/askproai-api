<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PremiumService extends Model
{
    protected $fillable = [
        'name',
        'description',
        'price',
        'duration',
        'active',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'active' => 'boolean',
    ];
}

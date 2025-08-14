<?php

namespace App\Models;

use App\Models\Concerns\IsUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Branch extends Model
{
    use HasFactory, IsUuid, SoftDeletes;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'customer_id',
        'name',
        'slug',
        'city',
        'phone_number',
        'active',
    ];

    // Beziehungen wie gehabt ...
}

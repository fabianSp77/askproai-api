<?php

namespace App\Models;

use App\Models\Concerns\IsUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Branch extends Model
{
    use IsUuid, SoftDeletes, HasFactory;

    public $incrementing = false;
    protected $keyType   = 'string';

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

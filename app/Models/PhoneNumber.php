<?php

namespace App\Models;

use App\Models\Concerns\IsUuid;
use Illuminate\Database\Eloquent\Model;

class PhoneNumber extends Model
{
    use IsUuid;

    public $incrementing = false;
    protected $keyType   = 'string';

    protected $fillable = [
        'branch_id',
        'number',
        'active',
    ];

    /* Beziehung */
    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }
}

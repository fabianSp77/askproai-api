<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\{BelongsTo, BelongsToMany};
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Staff extends Model
{
    use HasUuids, HasFactory;

    public $incrementing = false;
    protected $keyType   = 'string';

    protected $fillable = [
        'name',
        'email',
        'phone',
        'active',
        'branch_id',
        'home_branch_id',
    ];

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }
}

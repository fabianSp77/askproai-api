<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\{BelongsTo, BelongsToMany};
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Staff extends Model
{
    use HasUuids;

    public $incrementing = false;
    protected $keyType   = 'string';

    /* -------------------------------------------------------------- */
    protected $fillable = [
        'name',
        'email',
        'phone',
        'active',
        'branch_id',        //  ← neu!
        'home_branch_id',   //  bleibt
    ];

    /* ---------------- Beziehungen --------------------------------- */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }

    public function homeBranch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'home_branch_id');
    }

    public function branches(): BelongsToMany
    {
        return $this->belongsToMany(Branch::class, 'branch_staff')
                    ->withTimestamps();
    }

    public function services(): BelongsToMany
    {
        return $this->belongsToMany(Service::class, 'staff_service')
                    ->withTimestamps();
    }
}

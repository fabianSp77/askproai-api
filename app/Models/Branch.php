<?php

namespace App\Models;

use App\Models\Concerns\IsUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Branch extends Model
{
    use IsUuid, SoftDeletes;

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

    /* ---------------- Beziehungen ---------------- */

    /** Kunde/Firma */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /** Mitarbeiter-Liste */
    public function staff(): HasMany
    {
        return $this->hasMany(Staff::class);
    }

    /** angebotene Services (Pivot branch_service) */
    public function services(): BelongsToMany
    {
        return $this->belongsToMany(Service::class, 'branch_service')
                    ->withTimestamps();
    }
}

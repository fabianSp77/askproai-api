<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Service extends Model
{
    protected $fillable = ['name'];

    public function staff(): BelongsToMany
    {
        return $this->belongsToMany(Staff::class, 'staff_service')
            ->withTimestamps();
    }
}

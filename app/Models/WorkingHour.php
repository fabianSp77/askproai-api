<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkingHour extends Model
{
    protected $guarded = [];

    public function staff(): BelongsTo
    {
        return $this->belongsTo(Staff::class);
    }
}

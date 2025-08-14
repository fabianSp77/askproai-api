<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CalcomEventType extends Model
{
    protected $fillable = ['calcom_id', 'title', 'staff_id', 'active'];

    public function staff(): BelongsTo
    {
        return $this->belongsTo(Staff::class);
    }
}

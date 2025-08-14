<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class StaffService extends Pivot
{
    protected $table = 'staff_service';
    public $incrementing = true;
    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'staff_id',
        'service_id'
    ];
}

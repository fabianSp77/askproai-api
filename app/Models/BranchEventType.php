<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class BranchEventType extends Pivot
{
    protected $table = 'branch_event_types';
    
    protected $fillable = [
        'branch_id',
        'event_type_id',
        'is_primary',
    ];
    
    protected $casts = [
        'is_primary' => 'boolean',
    ];
}
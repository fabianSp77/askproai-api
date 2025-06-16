<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CalendarMapping extends Model
{
    use HasFactory;

    protected $fillable = [
        'branch_id',
        'staff_id',
        'calendar_type',
        'calendar_details',
    ];

    protected $casts = [
        'calendar_details' => 'array',
    ];

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function staff()
    {
        return $this->belongsTo(Staff::class);
    }
}

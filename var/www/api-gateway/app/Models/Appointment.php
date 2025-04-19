<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Appointment extends Model
{
    use HasFactory;

    protected $fillable = [
        'kunde_id', 'call_id', 'start_time', 'end_time',
        'service', 'service_id', 'staff_id', 'notes',
        'status', 'external_id', 'external_system'
    ];
    
    protected $casts = [
        'start_time' => 'datetime',
        'end_time' => 'datetime'
    ];
    
    public function kunde()
    {
        return $this->belongsTo(Kunde::class);
    }
    
    public function call()
    {
        return $this->belongsTo(Call::class);
    }
    
    public function service()
    {
        return $this->belongsTo(Service::class);
    }
    
    public function staff()
    {
        return $this->belongsTo(Staff::class);
    }
}

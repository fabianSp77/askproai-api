<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Agent extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'name',
        'agent_id',
        'type',
        'config',
        'active'
    ];
    
    protected $casts = [
        'config' => 'array',
        'active' => 'boolean'
    ];
}

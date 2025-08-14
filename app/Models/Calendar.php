<?php

namespace App\Models;

use App\Models\Concerns\IsUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Calendar extends Model
{
    use IsUuid, SoftDeletes;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'staff_id',
        'provider',
        'api_key',
        'event_type_id',
        'external_user_id',
        'validated_at',
    ];

    protected $casts = [
        'validated_at' => 'datetime',
    ];

    /* Beziehung */
    public function staff()
    {
        return $this->belongsTo(Staff::class);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Integration extends Model
{
    protected $fillable = ['kunde_id', 'name', 'details'];

    public function kunde()
    {
        return $this->belongsTo(Kunde::class);
    }
}

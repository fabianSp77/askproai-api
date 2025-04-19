<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    protected $table = 'kunden';

    protected $fillable = [
        'name', 'email', 'telefonnummer', 'notizen'
    ];

    public function calls()
    {
        return $this->hasMany(Call::class, 'kunde_id');
    }

    public function appointments()
    {
        return $this->hasMany(Appointment::class, 'kunde_id');
    }
}

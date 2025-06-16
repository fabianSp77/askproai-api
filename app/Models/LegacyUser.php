<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class LegacyUser extends Model
{
    protected $table = 'users';
    protected $primaryKey = 'user_id';
    public $timestamps = false;
    
    // Nur Legacy-Felder
    protected $fillable = [
        'fname', 'lname', 'name', 'email', 'password', 
        'username', 'organization', 'position', 'phone'
    ];
}

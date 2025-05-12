<?php

namespace App\Models;

use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;   // Rollen

class User extends Authenticatable implements FilamentUser
{
    use HasApiTokens, HasFactory, Notifiable, HasRoles;

    /* -------------------------------------------------------------------- */
    protected $fillable = ['name', 'email', 'password', 'email_verified_at'];
    protected $hidden   = ['password', 'remember_token'];
    protected $casts    = ['email_verified_at' => 'datetime'];

    /*  Zugriff aufs Admin-Panel – nur mit Rolle admin                     */
    public function canAccessPanel(Panel $panel): bool
    {
        return $this->hasRole('admin');
    }
}

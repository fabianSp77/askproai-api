<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;
use Filament\Models\Contracts\FilamentUser;

class User extends Authenticatable implements FilamentUser
{
    use HasApiTokens, HasFactory, Notifiable, HasRoles;

    protected $fillable = ['name', 'email', 'password'];
    protected $hidden   = ['password', 'remember_token'];
    protected $casts    = ['email_verified_at' => 'datetime'];

    /**  ► Filament: darf dieser User das Panel sehen?  */
    public function canAccessPanel(\Filament\Panel $panel): bool
    {
        // simplest-possible Regel:
        //   – super_admin kommt immer rein
        //   – alle anderen nur, wenn sie die Shield-Permission haben
        return $this->hasRole('super_admin')
            || $this->can('access_admin_panel');
    }
}

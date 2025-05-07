<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Tenant extends Model
{
    public $incrementing = false;
    protected $keyType   = 'string';

    // slug  + api_key jetzt mass-assignable
    protected $fillable = ['name', 'slug', 'api_key'];

    /* -------------------------------------------------------------------- */
    protected static function booted(): void
    {
        static::creating(function (self $tenant) {
            $tenant->id       ??= (string) Str::uuid();
            $tenant->slug     ??= Str::slug($tenant->name);
            $tenant->api_key  ??= Str::random(32);
        });
    }

    /* -------------------------------------------------------------------- */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }
}

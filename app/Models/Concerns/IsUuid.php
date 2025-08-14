<?php

namespace App\Models\Concerns;

use Illuminate\Support\Str;

trait IsUuid
{
    protected static function bootIsUuid(): void
    {
        static::creating(fn ($m) => $m->id ??= (string) Str::uuid());
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Represents a business customer within the application.
 *
 * @property-read \Illuminate\Support\Collection|Branch[] $branches
 */
class Customer extends Model
{
    use HasFactory;

    /**
     * Mass assignable attributes for the customer.
     *
     * @var array<string>
     */
    protected array $fillable = ['name', 'email', 'phone', 'notes'];

    /**
     * Branches that belong to the customer.
     *
     * @return HasMany
     */
    public function branches(): HasMany
    {
        return $this->hasMany(Branch::class);
    }
}

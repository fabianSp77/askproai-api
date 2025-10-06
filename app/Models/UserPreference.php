<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class UserPreference extends Model
{
    use BelongsToCompany;
    protected $fillable = [
        'user_id',
        'user_type',
        'preference_key',
        'preference_value',
    ];

    protected $casts = [
        'preference_value' => 'json',
    ];

    /**
     * Get the user that owns the preference
     */
    public function user(): MorphTo
    {
        return $this->morphTo('user', 'user_type', 'user_id');
    }

    /**
     * Get a preference value for a user
     */
    public static function get($userId, string $key, $default = null)
    {
        $preference = static::where('user_id', $userId)
            ->where('preference_key', $key)
            ->first();

        return $preference ? $preference->preference_value : $default;
    }

    /**
     * Set a preference value for a user
     */
    public static function set($userId, string $key, $value): self
    {
        return static::updateOrCreate(
            [
                'user_id' => $userId,
                'preference_key' => $key,
            ],
            [
                'user_type' => 'App\Models\User',
                'preference_value' => $value,
            ]
        );
    }

    /**
     * Get column order for a specific resource
     */
    public static function getColumnOrder($userId, string $resource): array
    {
        $key = "{$resource}_column_order";
        return static::get($userId, $key, []);
    }

    /**
     * Save column order for a specific resource
     */
    public static function saveColumnOrder($userId, string $resource, array $columns): void
    {
        $key = "{$resource}_column_order";
        static::set($userId, $key, $columns);
    }

    /**
     * Get column visibility for a specific resource
     */
    public static function getColumnVisibility($userId, string $resource): array
    {
        $key = "{$resource}_column_visibility";
        return static::get($userId, $key, []);
    }

    /**
     * Save column visibility for a specific resource
     */
    public static function saveColumnVisibility($userId, string $resource, array $visibility): void
    {
        $key = "{$resource}_column_visibility";
        static::set($userId, $key, $visibility);
    }

    /**
     * Reset column preferences for a resource
     */
    public static function resetColumnPreferences($userId, string $resource): void
    {
        static::where('user_id', $userId)
            ->whereIn('preference_key', [
                "{$resource}_column_order",
                "{$resource}_column_visibility",
            ])
            ->delete();
    }
}
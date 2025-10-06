<?php

namespace App\Models;

use Spatie\Permission\Models\Role as SpatieRole;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Builder;

class Role extends SpatieRole
{
    protected $fillable = [
        'name',
        'guard_name',
        'description',
        'color',
        'icon',
        'is_system',
        'priority',
        'metadata',
    ];

    protected $casts = [
        'is_system' => 'boolean',
        'metadata' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Role definitions
    const SUPER_ADMIN = 'super-admin';
    const ADMIN = 'admin';
    const MANAGER = 'manager';
    const OPERATOR = 'operator';
    const VIEWER = 'viewer';

    // Default role colors
    const ROLE_COLORS = [
        self::SUPER_ADMIN => 'danger',
        self::ADMIN => 'warning',
        self::MANAGER => 'success',
        self::OPERATOR => 'info',
        self::VIEWER => 'gray',
    ];

    // Default role icons
    const ROLE_ICONS = [
        self::SUPER_ADMIN => 'heroicon-o-shield-exclamation',
        self::ADMIN => 'heroicon-o-shield-check',
        self::MANAGER => 'heroicon-o-briefcase',
        self::OPERATOR => 'heroicon-o-cog',
        self::VIEWER => 'heroicon-o-eye',
    ];

    /**
     * Get formatted badge color
     */
    public function getBadgeColorAttribute(): string
    {
        return $this->color ?? self::ROLE_COLORS[$this->name] ?? 'secondary';
    }

    /**
     * Get formatted icon
     */
    public function getIconNameAttribute(): string
    {
        return $this->icon ?? self::ROLE_ICONS[$this->name] ?? 'heroicon-o-user';
    }

    /**
     * Get user count
     */
    public function getUserCountAttribute(): int
    {
        return $this->users()->count();
    }

    /**
     * Get permission count
     */
    public function getPermissionCountAttribute(): int
    {
        return $this->permissions()->count();
    }

    /**
     * Check if role is deletable
     */
    public function getCanDeleteAttribute(): bool
    {
        return !$this->is_system && $this->user_count === 0;
    }

    /**
     * Get formatted description
     */
    public function getFormattedDescriptionAttribute(): string
    {
        if ($this->description) {
            return $this->description;
        }

        return match($this->name) {
            self::SUPER_ADMIN => 'Vollständiger Systemzugriff',
            self::ADMIN => 'Administrative Rechte',
            self::MANAGER => 'Management Funktionen',
            self::OPERATOR => 'Operative Tätigkeiten',
            self::VIEWER => 'Nur Leserechte',
            default => 'Benutzerdefinierte Rolle'
        };
    }

    /**
     * Scope for system roles
     */
    public function scopeSystem(Builder $query): Builder
    {
        return $query->where('is_system', true);
    }

    /**
     * Scope for custom roles
     */
    public function scopeCustom(Builder $query): Builder
    {
        return $query->where('is_system', false);
    }

    /**
     * Scope for active roles (with users)
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->whereHas('users');
    }

    /**
     * Get grouped permissions
     */
    public function getGroupedPermissions(): array
    {
        $permissions = $this->permissions()->get();
        $grouped = [];

        foreach ($permissions as $permission) {
            $parts = explode('.', $permission->name);
            $module = $parts[0] ?? 'general';

            if (!isset($grouped[$module])) {
                $grouped[$module] = [];
            }

            $grouped[$module][] = $permission;
        }

        return $grouped;
    }

    /**
     * Sync permissions with validation
     */
    public function syncPermissionsSafely(array $permissions): void
    {
        // Don't allow modification of system roles
        if ($this->is_system && !auth()->user()->hasRole(self::SUPER_ADMIN)) {
            throw new \Exception('System roles cannot be modified');
        }

        $this->syncPermissions($permissions);
    }

    /**
     * Create default roles
     */
    public static function createDefaultRoles(): void
    {
        $roles = [
            [
                'name' => self::SUPER_ADMIN,
                'description' => 'Vollständiger Systemzugriff mit allen Berechtigungen',
                'color' => 'danger',
                'icon' => 'heroicon-o-shield-exclamation',
                'is_system' => true,
                'priority' => 1,
            ],
            [
                'name' => self::ADMIN,
                'description' => 'Administrative Rechte für Systemverwaltung',
                'color' => 'warning',
                'icon' => 'heroicon-o-shield-check',
                'is_system' => true,
                'priority' => 2,
            ],
            [
                'name' => self::MANAGER,
                'description' => 'Management von Geschäftsprozessen',
                'color' => 'success',
                'icon' => 'heroicon-o-briefcase',
                'is_system' => true,
                'priority' => 3,
            ],
            [
                'name' => self::OPERATOR,
                'description' => 'Operative Tätigkeiten und Datenpflege',
                'color' => 'info',
                'icon' => 'heroicon-o-cog',
                'is_system' => true,
                'priority' => 4,
            ],
            [
                'name' => self::VIEWER,
                'description' => 'Nur Leserechte ohne Änderungsmöglichkeiten',
                'color' => 'gray',
                'icon' => 'heroicon-o-eye',
                'is_system' => true,
                'priority' => 5,
            ],
        ];

        foreach ($roles as $roleData) {
            static::firstOrCreate(
                ['name' => $roleData['name']],
                $roleData
            );
        }
    }
}
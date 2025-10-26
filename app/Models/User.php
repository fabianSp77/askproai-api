<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Log;
use TypeError;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasRoles {
        HasRoles::hasRole as protected spatieHasRole;
        HasRoles::hasAnyRole as protected spatieHasAnyRole;
        HasRoles::hasPermissionTo as protected spatieHasPermissionTo;
    }
    // REMOVED BelongsToCompany: User is the AUTH model and should not be company-scoped
    // This was causing circular dependency: Session deserialization → User boot → CompanyScope → Auth::check() → Session load → DEADLOCK

    /**
     * Guard flag to avoid spamming logs when permission tables are missing.
     */
    protected static bool $permissionTablesMissing = false;

    /**
     * Guard flag to avoid spamming logs for invalid role check types.
     */
    protected static bool $invalidRoleCheckTypeLogged = false;

    protected function isValidRoleArgument($roles): bool
    {
        return is_string($roles)
            || is_int($roles)
            || is_array($roles)
            || $roles instanceof \UnitEnum
            || $roles instanceof \Illuminate\Support\Collection
            || $roles instanceof \Illuminate\Database\Eloquent\Collection;
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'company_id',
        'tenant_id',
        'kunde_id',
        'interface_language',
        'content_language',
        'auto_translate_content',
        'last_login_at',
        'last_login_ip',
        'failed_login_attempts',
        'locked_until',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_secret',
        'two_factor_recovery_codes',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'two_factor_confirmed_at' => 'datetime',
            'two_factor_enforced' => 'boolean',
            'two_factor_phone_verified' => 'boolean',
            'auto_translate_content' => 'boolean',
            'last_login_at' => 'datetime',
            'locked_until' => 'datetime',
        ];
    }

    /**
     * Determine if the user can access the Filament panel.
     *
     * Security: Panel-specific access control to prevent unauthorized access
     * - Admin Panel (/admin): Only admins and resellers
     * - Customer Portal (/portal): Only company users (NOT admins)
     *
     * @param Panel $panel The Filament panel being accessed
     * @return bool True if user can access this specific panel
     */
    public function canAccessPanel(Panel $panel): bool
    {
        // Panel-specific authorization
        return match($panel->getId()) {
            'admin' => $this->canAccessAdminPanel(),
            'portal' => $this->canAccessCustomerPortal(),
            default => false, // Deny access to unknown panels
        };
    }

    /**
     * Check if user can access Admin Panel (/admin)
     *
     * Allowed roles:
     * - super_admin: System administrators
     * - Admin: Platform administrators
     * - reseller_admin: Reseller administrators
     *
     * @return bool
     */
    protected function canAccessAdminPanel(): bool
    {
        return $this->hasAnyRole(['super_admin', 'Admin', 'reseller_admin']);
    }

    /**
     * Check if user can access Customer Portal (/portal)
     *
     * Security requirements:
     * 1. Feature flag must be enabled
     * 2. User must belong to a company (company_id)
     * 3. User must have appropriate company role
     * 4. Admins are EXCLUDED (must use /admin instead)
     *
     * Allowed roles:
     * - company_owner: Company owners
     * - company_admin: Company administrators
     * - company_manager: Branch managers
     * - company_staff: Staff members
     *
     * @return bool
     */
    protected function canAccessCustomerPortal(): bool
    {
        // Feature flag check (kill switch)
        if (!config('features.customer_portal')) {
            return false;
        }

        // Exclude super_admins and platform admins (they should use /admin)
        if ($this->hasAnyRole(['super_admin', 'Admin', 'reseller_admin'])) {
            return false;
        }

        // Multi-tenancy check: User must belong to a company
        if ($this->company_id === null) {
            return false;
        }

        // Role-based access: Only company users allowed
        return $this->hasAnyRole([
            'company_owner',
            'company_admin',
            'company_manager',
            'company_staff',
        ]);
    }

    public function hasRole($roles, string $guard = null): bool
    {
        if (! $this->isValidRoleArgument($roles)) {
            return $this->handleInvalidRoleArgument($roles);
        }

        return $this->callSpatieRoleCheck(fn () => $this->spatieHasRole($roles, $guard));
    }

    public function hasAnyRole($roles, string $guard = null): bool
    {
        if (! $this->isValidRoleArgument($roles)) {
            return $this->handleInvalidRoleArgument($roles);
        }

        return $this->callSpatieRoleCheck(fn () => $this->spatieHasAnyRole($roles, $guard));
    }

    public function hasPermissionTo($permission, $guardName = null): bool
    {
        return $this->callSpatieRoleCheck(fn () => $this->spatieHasPermissionTo($permission, $guardName));
    }

    protected function handleInvalidRoleArgument($roles): bool
    {
        if (! static::$invalidRoleCheckTypeLogged) {
            static::$invalidRoleCheckTypeLogged = true;

            Log::warning('[Permissions] Invalid hasRole argument type detected. Returning "false".', [
                'type' => get_debug_type($roles),
                'value' => $roles,
                'trace' => debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 5),
            ]);
        }

        return false;
    }

    /**
     * Execute a role/permission check and gracefully handle missing tables.
     */
    protected function callSpatieRoleCheck(\Closure $callback): bool
    {
        try {
            return $callback();
        } catch (TypeError $exception) {
            if (! static::$invalidRoleCheckTypeLogged) {
                static::$invalidRoleCheckTypeLogged = true;

                Log::warning('[Permissions] Invalid type passed to role/permission check. Returning "false".', [
                    'error' => $exception->getMessage(),
                    'trace' => array_slice($exception->getTrace(), 0, 5),
                ]);
            }

            return false;
        } catch (QueryException $exception) {
            if ($exception->getCode() !== '42S02') {
                throw $exception;
            }

            if (! static::$permissionTablesMissing) {
                static::$permissionTablesMissing = true;

                Log::warning('[Permissions] Role/permission tables not found. Returning "false" for role checks.', [
                    'error' => $exception->getMessage(),
                ]);
            }

            return false;
        }
    }

    /**
     * Get the company that owns the user.
     */
    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get the tenant that owns the user.
     */
    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

}

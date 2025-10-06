<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CalcomTeamMember extends Model
{
    use BelongsToCompany;
    protected $fillable = [
        'company_id',
        'calcom_team_id',
        'calcom_user_id',
        'email',
        'name',
        'username',
        'role',
        'accepted',
        'availability',
        'is_active',
        'last_synced_at',
    ];

    protected $casts = [
        'accepted' => 'boolean',
        'is_active' => 'boolean',
        'availability' => 'array',
        'last_synced_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the company that owns this team member
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Check if member is an owner
     */
    public function isOwner(): bool
    {
        return $this->role === 'owner';
    }

    /**
     * Check if member is an admin
     */
    public function isAdmin(): bool
    {
        return in_array($this->role, ['owner', 'admin']);
    }

    /**
     * Check if member can manage team
     */
    public function canManageTeam(): bool
    {
        return $this->isAdmin() && $this->accepted && $this->is_active;
    }

    /**
     * Get the role badge color
     */
    public function getRoleBadgeAttribute(): string
    {
        return match($this->role) {
            'owner' => 'danger',
            'admin' => 'warning',
            'member' => 'success',
            default => 'secondary'
        };
    }

    /**
     * Get the role label
     */
    public function getRoleLabelAttribute(): string
    {
        return ucfirst($this->role);
    }
}
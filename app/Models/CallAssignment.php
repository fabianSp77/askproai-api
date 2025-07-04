<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CallAssignment extends Model
{
    use HasFactory;

    protected $fillable = [
        'call_id',
        'assigned_by',
        'assigned_to',
        'previous_assignee',
        'notes',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the call
     */
    public function call(): BelongsTo
    {
        return $this->belongsTo(Call::class);
    }

    /**
     * Get the user who made the assignment
     */
    public function assignedBy(): BelongsTo
    {
        return $this->belongsTo(PortalUser::class, 'assigned_by');
    }

    /**
     * Get the user who was assigned
     */
    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(PortalUser::class, 'assigned_to');
    }

    /**
     * Get the previous assignee
     */
    public function previousAssignee(): BelongsTo
    {
        return $this->belongsTo(PortalUser::class, 'previous_assignee');
    }
}
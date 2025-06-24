<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Carbon\Carbon;

class CallbackRequest extends Model
{
    use HasFactory, BelongsToCompany;

    protected $fillable = [
        'company_id',
        'branch_id',
        'call_id',
        'customer_phone',
        'customer_name',
        'requested_service',
        'requested_date',
        'requested_time',
        'reason',
        'error_details',
        'call_summary',
        'priority',
        'status',
        'assigned_to',
        'completed_by',
        'completion_notes',
        'auto_close_after_hours',
        'processed_at',
        'auto_closed_at'
    ];

    protected $casts = [
        'error_details' => 'array',
        'requested_date' => 'date',
        'requested_time' => 'time',
        'processed_at' => 'datetime',
        'auto_closed_at' => 'datetime'
    ];

    /**
     * Get the call that generated this callback request
     */
    public function call(): BelongsTo
    {
        return $this->belongsTo(Call::class);
    }

    /**
     * Get the branch for this callback
     */
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'branch_id', 'id');
    }

    /**
     * Get the user assigned to this callback
     */
    public function assignedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    /**
     * Get the user who completed this callback
     */
    public function completedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'completed_by');
    }

    /**
     * Check if this callback should be auto-closed
     */
    public function shouldAutoClose(): bool
    {
        if ($this->status !== 'pending') {
            return false;
        }

        $hours = $this->auto_close_after_hours ?? 24;
        return $this->created_at->diffInHours(now()) >= $hours;
    }

    /**
     * Auto-close this callback request
     */
    public function autoClose(): void
    {
        $this->update([
            'status' => 'auto_closed',
            'auto_closed_at' => now(),
            'completion_notes' => 'Automatisch geschlossen nach ' . 
                $this->auto_close_after_hours . ' Stunden ohne Bearbeitung.'
        ]);
    }

    /**
     * Mark as in progress by a user
     */
    public function markInProgress(User $user): void
    {
        $this->update([
            'status' => 'in_progress',
            'assigned_to' => $user->id,
            'processed_at' => now()
        ]);
    }

    /**
     * Complete the callback request
     */
    public function complete(User $user, ?string $notes = null): void
    {
        $this->update([
            'status' => 'completed',
            'completed_by' => $user->id,
            'completion_notes' => $notes,
            'processed_at' => $this->processed_at ?? now()
        ]);
    }

    /**
     * Cancel the callback request
     */
    public function cancel(?string $reason = null): void
    {
        $this->update([
            'status' => 'cancelled',
            'completion_notes' => $reason
        ]);
    }

    /**
     * Get the priority badge color
     */
    public function getPriorityColorAttribute(): string
    {
        return match($this->priority) {
            'urgent' => 'danger',
            'high' => 'warning',
            'normal' => 'primary',
            'low' => 'secondary',
            default => 'secondary'
        };
    }

    /**
     * Get the status badge color
     */
    public function getStatusColorAttribute(): string
    {
        return match($this->status) {
            'pending' => 'warning',
            'in_progress' => 'info',
            'completed' => 'success',
            'auto_closed' => 'secondary',
            'cancelled' => 'danger',
            default => 'secondary'
        };
    }

    /**
     * Get formatted phone number for display
     */
    public function getFormattedPhoneAttribute(): string
    {
        // Mask middle digits for privacy
        $phone = $this->customer_phone;
        if (strlen($phone) > 8) {
            return substr($phone, 0, 4) . '****' . substr($phone, -3);
        }
        return $phone;
    }

    /**
     * Get time until auto-close
     */
    public function getTimeUntilAutoCloseAttribute(): ?string
    {
        if ($this->status !== 'pending') {
            return null;
        }

        $hoursLeft = $this->auto_close_after_hours - $this->created_at->diffInHours(now());
        
        if ($hoursLeft <= 0) {
            return 'Überfällig';
        }

        if ($hoursLeft < 1) {
            $minutes = round($hoursLeft * 60);
            return $minutes . ' Min';
        }

        return round($hoursLeft) . ' Std';
    }

    /**
     * Scope for pending callbacks
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope for overdue callbacks
     */
    public function scopeOverdue($query)
    {
        return $query->where('status', 'pending')
            ->where('created_at', '<=', now()->subHours(2));
    }

    /**
     * Scope for today's callbacks
     */
    public function scopeToday($query)
    {
        return $query->whereDate('created_at', today());
    }

    /**
     * Scope by priority
     */
    public function scopePriority($query, string $priority)
    {
        return $query->where('priority', $priority);
    }
}
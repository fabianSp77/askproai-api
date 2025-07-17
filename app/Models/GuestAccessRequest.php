<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GuestAccessRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'call_id',
        'name',
        'email',
        'reason',
        'token',
        'expires_at',
        'approved_at',
        'approved_by',
        'rejected_at',
        'rejected_by',
        'rejection_reason',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'approved_at' => 'datetime',
        'rejected_at' => 'datetime',
    ];

    /**
     * Get the company
     */
    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get the call
     */
    public function call()
    {
        return $this->belongsTo(Call::class);
    }

    /**
     * Get the approver
     */
    public function approver()
    {
        return $this->belongsTo(PortalUser::class, 'approved_by');
    }

    /**
     * Get the rejecter
     */
    public function rejecter()
    {
        return $this->belongsTo(PortalUser::class, 'rejected_by');
    }

    /**
     * Check if request is expired
     */
    public function isExpired()
    {
        return $this->expires_at->isPast();
    }

    /**
     * Check if request is pending
     */
    public function isPending()
    {
        return !$this->approved_at && !$this->rejected_at && !$this->isExpired();
    }

    /**
     * Check if request is approved
     */
    public function isApproved()
    {
        return !is_null($this->approved_at);
    }

    /**
     * Check if request is rejected
     */
    public function isRejected()
    {
        return !is_null($this->rejected_at);
    }
}
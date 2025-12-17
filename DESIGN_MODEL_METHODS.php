<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Call Model - Extended Methods for UX Support
 */
class Call extends Model
{
    /**
     * Get related calls with contextual labels
     * Used for "Related Calls" section in detail view
     *
     * @return \Illuminate\Support\Collection
     */
    public function getRelatedCallsWithContext()
    {
        $relatedCalls = collect();

        // If this call created an appointment
        if ($this->appointment) {
            $appt = $this->appointment;

            // Add the original booking call (if different)
            if ($appt->originalCall && $appt->originalCall->id !== $this->id) {
                $relatedCalls->push([
                    'id' => $appt->originalCall->id,
                    'call_type_label' => 'Booking Call',
                    'created_at' => $appt->originalCall->created_at,
                ]);
            }

            // Add cancellation call (if different)
            if ($appt->status === 'cancelled' && $appt->cancellation) {
                $cancelCall = Call::find($appt->cancellation->call_id);
                if ($cancelCall && $cancelCall->id !== $this->id) {
                    $relatedCalls->push([
                        'id' => $cancelCall->id,
                        'call_type_label' => 'Cancellation Call',
                        'created_at' => $cancelCall->created_at,
                    ]);
                }
            }

            // Add reschedule calls (if any)
            $reschedules = $appt->rescheduleHistory()
                ->with('call')
                ->get();

            foreach ($reschedules as $reschedule) {
                if ($reschedule->call && $reschedule->call->id !== $this->id) {
                    $relatedCalls->push([
                        'id' => $reschedule->call->id,
                        'call_type_label' => 'Reschedule Call',
                        'created_at' => $reschedule->call->created_at,
                    ]);
                }
            }
        }

        // If this call cancelled an appointment created by another call
        $cancelledAppointments = Appointment::where('status', 'cancelled')
            ->whereHas('cancellation', function ($query) {
                $query->where('call_id', $this->id);
            })
            ->with(['originalCall', 'cancellation'])
            ->get();

        foreach ($cancelledAppointments as $appt) {
            if ($appt->originalCall && $appt->originalCall->id !== $this->id) {
                $relatedCalls->push([
                    'id' => $appt->originalCall->id,
                    'call_type_label' => 'Original Booking Call',
                    'created_at' => $appt->originalCall->created_at,
                ]);
            }
        }

        return $relatedCalls->sortBy('created_at')->values();
    }

    /**
     * Check if this call has related calls
     *
     * @return bool
     */
    public function hasRelatedCalls(): bool
    {
        return $this->getRelatedCallsWithContext()->isNotEmpty();
    }

    /**
     * Relationship: Appointment created by this call
     */
    public function appointment()
    {
        return $this->hasOne(Appointment::class, 'call_id');
    }

    /**
     * Relationship: Appointments cancelled by this call
     */
    public function cancelledAppointments()
    {
        return $this->hasManyThrough(
            Appointment::class,
            AppointmentCancellation::class,
            'call_id', // FK on cancellations
            'id',      // FK on appointments
            'id',      // Local key on calls
            'appointment_id' // Local key on cancellations
        )->where('appointments.status', 'cancelled');
    }
}

/**
 * Appointment Model - Extended Methods for UX Support
 */
class Appointment extends Model
{
    /**
     * Relationship: Original call that created this appointment
     */
    public function originalCall()
    {
        return $this->belongsTo(Call::class, 'call_id');
    }

    /**
     * Relationship: Cancellation details
     */
    public function cancellation()
    {
        return $this->hasOne(AppointmentCancellation::class);
    }

    /**
     * Relationship: Reschedule history
     */
    public function rescheduleHistory()
    {
        return $this->hasMany(AppointmentReschedule::class);
    }

    /**
     * Get formatted cancellation summary for tooltips
     *
     * @return string|null
     */
    public function getCancellationSummaryAttribute()
    {
        if ($this->status !== 'cancelled' || !$this->cancellation) {
            return null;
        }

        $cancel = $this->cancellation;
        $summary = "Cancelled on {$cancel->cancelled_at->format('M j, Y')}";

        if ($cancel->cancelled_by_type) {
            $summary .= " by " . ucfirst($cancel->cancelled_by_type);
        }

        if ($cancel->cancellation_fee > 0) {
            $summary .= " (Fee: " . number_format($cancel->cancellation_fee, 2) . " €)";
        }

        return $summary;
    }
}

/**
 * AppointmentCancellation Model - Metadata for cancelled appointments
 */
class AppointmentCancellation extends Model
{
    protected $fillable = [
        'appointment_id',
        'call_id',              // Call that performed cancellation
        'cancelled_at',
        'cancelled_by_type',    // 'customer', 'staff', 'admin', 'system'
        'cancelled_by_name',
        'cancellation_fee',
        'reason',
        'refund_status',        // 'pending', 'refunded', 'failed', 'not_applicable'
        'refund_amount',
        'policy_applied',       // JSON of policy rules applied
    ];

    protected $casts = [
        'cancelled_at' => 'datetime',
        'cancellation_fee' => 'decimal:2',
        'refund_amount' => 'decimal:2',
        'policy_applied' => 'array',
    ];

    /**
     * Relationship: The appointment that was cancelled
     */
    public function appointment()
    {
        return $this->belongsTo(Appointment::class);
    }

    /**
     * Relationship: The call that performed the cancellation
     */
    public function call()
    {
        return $this->belongsTo(Call::class);
    }
}

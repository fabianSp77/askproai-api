<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

class WorkingHour extends Model
{
    use BelongsToCompany;
    protected $guarded = [];

    protected $casts = [
        'start' => 'datetime:H:i',
        'end' => 'datetime:H:i',
        'is_active' => 'boolean',
        'day_of_week' => 'integer',
        'weekday' => 'integer',
    ];

    public function staff(): BelongsTo
    {
        return $this->belongsTo(Staff::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'company_id')->withDefault([
            'name' => $this->staff?->company?->name ?? 'N/A'
        ]);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'branch_id')->withDefault([
            'name' => $this->staff?->branch?->name ?? 'N/A'
        ]);
    }

    /**
     * Get company through staff relationship if company_id is not set
     */
    public function getCompanyAttribute()
    {
        if ($this->company_id) {
            return $this->company()->first();
        }
        return $this->staff?->company;
    }

    /**
     * Get branch through staff relationship if branch_id is not set
     */
    public function getBranchAttribute()
    {
        if ($this->branch_id) {
            return $this->branch()->first();
        }
        return $this->staff?->branch;
    }

    /**
     * Get day name in German
     */
    public function getDayNameAttribute(): string
    {
        $days = [
            0 => 'Sonntag',
            1 => 'Montag',
            2 => 'Dienstag',
            3 => 'Mittwoch',
            4 => 'Donnerstag',
            5 => 'Freitag',
            6 => 'Samstag',
        ];

        return $days[$this->day_of_week ?? $this->weekday ?? 1] ?? 'Unbekannt';
    }

    /**
     * Get formatted time range
     */
    public function getTimeRangeAttribute(): string
    {
        return sprintf('%s - %s',
            Carbon::parse($this->start)->format('H:i'),
            Carbon::parse($this->end)->format('H:i')
        );
    }

    /**
     * Scope for active working hours
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for specific day
     */
    public function scopeForDay($query, $day)
    {
        return $query->where(function($q) use ($day) {
            $q->where('day_of_week', $day)
              ->orWhere('weekday', $day);
        });
    }

    /**
     * Check if working hour overlaps with another time range
     */
    public function overlapsWithTime($start, $end): bool
    {
        $thisStart = Carbon::parse($this->start);
        $thisEnd = Carbon::parse($this->end);
        $checkStart = Carbon::parse($start);
        $checkEnd = Carbon::parse($end);

        return !($checkEnd <= $thisStart || $checkStart >= $thisEnd);
    }
}

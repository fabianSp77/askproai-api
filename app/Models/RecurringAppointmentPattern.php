<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RecurringAppointmentPattern extends Model
{
    use HasFactory, BelongsToCompany;

    protected $fillable = [
        'appointment_id',
        'frequency',
        'interval',
        'days_of_week',
        'day_of_month',
        'start_date',
        'end_date',
        'occurrences',
        'exceptions',
    ];

    protected $casts = [
        'days_of_week' => 'array',
        'exceptions' => 'array',
        'start_date' => 'date',
        'end_date' => 'date',
        'day_of_month' => 'integer',
        'interval' => 'integer',
        'occurrences' => 'integer',
    ];

    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class);
    }

    public function getFrequencyLabelAttribute(): string
    {
        return match($this->frequency) {
            'daily' => 'Täglich',
            'weekly' => 'Wöchentlich',
            'monthly' => 'Monatlich',
            'yearly' => 'Jährlich',
            default => ucfirst($this->frequency)
        };
    }

    public function getRecurrenceDescriptionAttribute(): string
    {
        $description = '';

        switch ($this->frequency) {
            case 'daily':
                $description = $this->interval === 1
                    ? 'Täglich'
                    : "Alle {$this->interval} Tage";
                break;

            case 'weekly':
                $description = $this->interval === 1
                    ? 'Wöchentlich'
                    : "Alle {$this->interval} Wochen";

                if ($this->days_of_week) {
                    $days = array_map(function($day) {
                        return $this->getDayName($day);
                    }, $this->days_of_week);
                    $description .= ' am ' . implode(', ', $days);
                }
                break;

            case 'monthly':
                $description = $this->interval === 1
                    ? 'Monatlich'
                    : "Alle {$this->interval} Monate";

                if ($this->day_of_month) {
                    $description .= " am {$this->day_of_month}.";
                }
                break;

            case 'yearly':
                $description = $this->interval === 1
                    ? 'Jährlich'
                    : "Alle {$this->interval} Jahre";
                break;
        }

        if ($this->occurrences) {
            $description .= ", {$this->occurrences} Mal";
        } elseif ($this->end_date) {
            $description .= " bis " . $this->end_date->format('d.m.Y');
        }

        return $description;
    }

    protected function getDayName(string $day): string
    {
        $days = [
            'monday' => 'Montag',
            'tuesday' => 'Dienstag',
            'wednesday' => 'Mittwoch',
            'thursday' => 'Donnerstag',
            'friday' => 'Freitag',
            'saturday' => 'Samstag',
            'sunday' => 'Sonntag',
        ];

        return $days[strtolower($day)] ?? $day;
    }

    public function isActive(): bool
    {
        if ($this->end_date && $this->end_date->isPast()) {
            return false;
        }

        if ($this->occurrences) {
            $createdCount = $this->appointment->children()->count();
            return $createdCount < $this->occurrences;
        }

        return true;
    }

    public function getRemainingOccurrencesAttribute(): ?int
    {
        if (!$this->occurrences) {
            return null;
        }

        $createdCount = $this->appointment->children()->count();
        return max(0, $this->occurrences - $createdCount);
    }
}
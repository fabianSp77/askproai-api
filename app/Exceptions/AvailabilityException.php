<?php

namespace App\Exceptions;

use Exception;
use Carbon\Carbon;

class AvailabilityException extends Exception
{
    /**
     * Error codes
     */
    const ERROR_NO_SLOTS_AVAILABLE = 'NO_SLOTS_AVAILABLE';
    const ERROR_OUTSIDE_WORKING_HOURS = 'OUTSIDE_WORKING_HOURS';
    const ERROR_STAFF_NOT_WORKING = 'STAFF_NOT_WORKING';
    const ERROR_DOUBLE_BOOKING = 'DOUBLE_BOOKING';
    const ERROR_PAST_DATE = 'PAST_DATE';
    const ERROR_TOO_FAR_IN_FUTURE = 'TOO_FAR_IN_FUTURE';
    const ERROR_MINIMUM_NOTICE = 'MINIMUM_NOTICE';
    const ERROR_CALENDAR_CONNECTION = 'CALENDAR_CONNECTION';
    
    /**
     * Suggested alternative slots
     */
    protected array $alternatives = [];
    
    /**
     * Error code
     */
    protected string $errorCode = self::ERROR_NO_SLOTS_AVAILABLE;
    
    /**
     * Additional context
     */
    protected array $context = [];
    
    /**
     * Create a new availability exception
     */
    public function __construct(
        string $message = "",
        string $errorCode = self::ERROR_NO_SLOTS_AVAILABLE,
        array $alternatives = [],
        array $context = [],
        int $code = 0,
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
        $this->errorCode = $errorCode;
        $this->alternatives = $alternatives;
        $this->context = $context;
    }
    
    /**
     * Get error code
     */
    public function getErrorCode(): string
    {
        return $this->errorCode;
    }
    
    /**
     * Get alternative time slots
     */
    public function getAlternatives(): array
    {
        return $this->alternatives;
    }
    
    /**
     * Get context data
     */
    public function getContext(): array
    {
        return $this->context;
    }
    
    /**
     * Get user-friendly error message
     */
    public function getUserMessage(): string
    {
        return match ($this->errorCode) {
            self::ERROR_NO_SLOTS_AVAILABLE => 'Leider ist kein freier Termin in diesem Zeitraum verfügbar.',
            self::ERROR_OUTSIDE_WORKING_HOURS => 'Der gewünschte Termin liegt außerhalb unserer Geschäftszeiten.',
            self::ERROR_STAFF_NOT_WORKING => 'Der gewünschte Mitarbeiter arbeitet zu diesem Zeitpunkt nicht.',
            self::ERROR_DOUBLE_BOOKING => 'Dieser Termin ist bereits vergeben.',
            self::ERROR_PAST_DATE => 'Der gewünschte Termin liegt in der Vergangenheit.',
            self::ERROR_TOO_FAR_IN_FUTURE => 'Der gewünschte Termin liegt zu weit in der Zukunft.',
            self::ERROR_MINIMUM_NOTICE => 'Termine müssen mindestens ' . ($this->context['minimum_hours'] ?? 24) . ' Stunden im Voraus gebucht werden.',
            self::ERROR_CALENDAR_CONNECTION => 'Die Verfügbarkeit konnte nicht geprüft werden. Bitte versuchen Sie es später erneut.',
            default => 'Der gewünschte Termin ist nicht verfügbar.',
        };
    }
    
    /**
     * Get formatted alternatives message
     */
    public function getAlternativesMessage(): ?string
    {
        if (empty($this->alternatives)) {
            return null;
        }
        
        $message = "Alternative Termine:\n";
        foreach ($this->alternatives as $i => $slot) {
            $start = Carbon::parse($slot['start']);
            $end = Carbon::parse($slot['end']);
            $message .= sprintf(
                "%d. %s, %s - %s Uhr\n",
                $i + 1,
                $start->locale('de')->isoFormat('dddd, D. MMMM'),
                $start->format('H:i'),
                $end->format('H:i')
            );
        }
        
        return $message;
    }
    
    /**
     * Convert to array for logging
     */
    public function toArray(): array
    {
        return [
            'error_code' => $this->errorCode,
            'message' => $this->getMessage(),
            'user_message' => $this->getUserMessage(),
            'alternatives' => $this->alternatives,
            'alternatives_message' => $this->getAlternativesMessage(),
            'context' => $this->context,
            'file' => $this->getFile(),
            'line' => $this->getLine(),
        ];
    }
    
    /**
     * Static factory methods
     */
    public static function noSlotsAvailable(array $alternatives = [], array $context = []): self
    {
        return new self(
            'No available time slots found',
            self::ERROR_NO_SLOTS_AVAILABLE,
            $alternatives,
            $context
        );
    }
    
    public static function outsideWorkingHours(array $workingHours = [], array $alternatives = []): self
    {
        return new self(
            'Requested time is outside working hours',
            self::ERROR_OUTSIDE_WORKING_HOURS,
            $alternatives,
            ['working_hours' => $workingHours]
        );
    }
    
    public static function staffNotWorking(string $staffName, array $alternatives = []): self
    {
        return new self(
            "Staff member {$staffName} is not working at this time",
            self::ERROR_STAFF_NOT_WORKING,
            $alternatives,
            ['staff_name' => $staffName]
        );
    }
    
    public static function doubleBooking(array $existingBooking = [], array $alternatives = []): self
    {
        return new self(
            'Time slot already booked',
            self::ERROR_DOUBLE_BOOKING,
            $alternatives,
            ['existing_booking' => $existingBooking]
        );
    }
    
    public static function pastDate(Carbon $requestedDate): self
    {
        return new self(
            'Cannot book appointments in the past',
            self::ERROR_PAST_DATE,
            [],
            ['requested_date' => $requestedDate->toDateTimeString()]
        );
    }
    
    public static function tooFarInFuture(int $maxDays = 90): self
    {
        return new self(
            "Cannot book appointments more than {$maxDays} days in advance",
            self::ERROR_TOO_FAR_IN_FUTURE,
            [],
            ['max_days' => $maxDays]
        );
    }
    
    public static function minimumNotice(int $minimumHours = 24): self
    {
        return new self(
            "Appointments must be booked at least {$minimumHours} hours in advance",
            self::ERROR_MINIMUM_NOTICE,
            [],
            ['minimum_hours' => $minimumHours]
        );
    }
    
    public static function calendarConnection(string $message = 'Calendar connection failed'): self
    {
        return new self(
            $message,
            self::ERROR_CALENDAR_CONNECTION
        );
    }
}
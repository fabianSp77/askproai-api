<?php

namespace App\Exceptions;

use Exception;

class BookingException extends Exception
{
    /**
     * Error codes
     */
    const ERROR_INVALID_DATA = 'INVALID_DATA';
    const ERROR_CUSTOMER_NOT_FOUND = 'CUSTOMER_NOT_FOUND';
    const ERROR_SERVICE_NOT_AVAILABLE = 'SERVICE_NOT_AVAILABLE';
    const ERROR_STAFF_NOT_AVAILABLE = 'STAFF_NOT_AVAILABLE';
    const ERROR_TIME_SLOT_NOT_AVAILABLE = 'TIME_SLOT_NOT_AVAILABLE';
    const ERROR_CALENDAR_SYNC_FAILED = 'CALENDAR_SYNC_FAILED';
    const ERROR_NOTIFICATION_FAILED = 'NOTIFICATION_FAILED';
    const ERROR_GENERAL = 'GENERAL_ERROR';
    
    /**
     * Additional context data
     */
    protected array $context = [];
    
    /**
     * Error code
     */
    protected string $errorCode = self::ERROR_GENERAL;
    
    /**
     * Create a new booking exception
     */
    public function __construct(
        string $message = "",
        string $errorCode = self::ERROR_GENERAL,
        array $context = [],
        int $code = 0,
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
        $this->errorCode = $errorCode;
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
            self::ERROR_INVALID_DATA => 'Die angegebenen Daten sind ungültig. Bitte überprüfen Sie Ihre Eingaben.',
            self::ERROR_CUSTOMER_NOT_FOUND => 'Kunde konnte nicht gefunden oder erstellt werden.',
            self::ERROR_SERVICE_NOT_AVAILABLE => 'Die gewünschte Dienstleistung ist nicht verfügbar.',
            self::ERROR_STAFF_NOT_AVAILABLE => 'Der gewünschte Mitarbeiter ist nicht verfügbar.',
            self::ERROR_TIME_SLOT_NOT_AVAILABLE => 'Der gewünschte Termin ist nicht verfügbar.',
            self::ERROR_CALENDAR_SYNC_FAILED => 'Die Kalendersynchronisation ist fehlgeschlagen. Der Termin wurde trotzdem gespeichert.',
            self::ERROR_NOTIFICATION_FAILED => 'Die Benachrichtigung konnte nicht gesendet werden. Der Termin wurde trotzdem gebucht.',
            default => 'Es ist ein Fehler aufgetreten. Bitte versuchen Sie es später erneut.',
        };
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
            'context' => $this->context,
            'file' => $this->getFile(),
            'line' => $this->getLine(),
            'trace' => $this->getTraceAsString(),
        ];
    }
    
    /**
     * Static factory methods for common errors
     */
    public static function invalidData(string $message, array $context = []): self
    {
        return new self($message, self::ERROR_INVALID_DATA, $context);
    }
    
    public static function customerNotFound(string $message, array $context = []): self
    {
        return new self($message, self::ERROR_CUSTOMER_NOT_FOUND, $context);
    }
    
    public static function serviceNotAvailable(string $message, array $context = []): self
    {
        return new self($message, self::ERROR_SERVICE_NOT_AVAILABLE, $context);
    }
    
    public static function staffNotAvailable(string $message, array $context = []): self
    {
        return new self($message, self::ERROR_STAFF_NOT_AVAILABLE, $context);
    }
    
    public static function timeSlotNotAvailable(string $message, array $context = []): self
    {
        return new self($message, self::ERROR_TIME_SLOT_NOT_AVAILABLE, $context);
    }
    
    public static function calendarSyncFailed(string $message, array $context = []): self
    {
        return new self($message, self::ERROR_CALENDAR_SYNC_FAILED, $context);
    }
    
    public static function notificationFailed(string $message, array $context = []): self
    {
        return new self($message, self::ERROR_NOTIFICATION_FAILED, $context);
    }
}
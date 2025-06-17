<?php

namespace App\Results;

use App\Models\Appointment;

class AppointmentResult
{
    private bool $success;
    private ?Appointment $appointment;
    private ?string $message;
    private ?string $errorCode;
    private array $metadata;
    private array $warnings;

    private function __construct(
        bool $success,
        ?Appointment $appointment = null,
        ?string $message = null,
        ?string $errorCode = null,
        array $metadata = [],
        array $warnings = []
    ) {
        $this->success = $success;
        $this->appointment = $appointment;
        $this->message = $message;
        $this->errorCode = $errorCode;
        $this->metadata = $metadata;
        $this->warnings = $warnings;
    }

    /**
     * Create a successful result
     */
    public static function success(Appointment $appointment, ?string $message = null, array $metadata = []): self
    {
        return new self(
            success: true,
            appointment: $appointment,
            message: $message ?? 'Appointment booked successfully',
            metadata: $metadata
        );
    }

    /**
     * Create a failed result
     */
    public static function failure(string $message, ?string $errorCode = null, array $metadata = []): self
    {
        return new self(
            success: false,
            message: $message,
            errorCode: $errorCode,
            metadata: $metadata
        );
    }

    /**
     * Create a result with warnings
     */
    public static function successWithWarnings(
        Appointment $appointment,
        array $warnings,
        ?string $message = null,
        array $metadata = []
    ): self {
        return new self(
            success: true,
            appointment: $appointment,
            message: $message ?? 'Appointment booked with warnings',
            metadata: $metadata,
            warnings: $warnings
        );
    }

    /**
     * Check if the booking was successful
     */
    public function isSuccess(): bool
    {
        return $this->success;
    }

    /**
     * Check if the booking failed
     */
    public function isFailure(): bool
    {
        return !$this->success;
    }

    /**
     * Get the appointment (if successful)
     */
    public function getAppointment(): ?Appointment
    {
        return $this->appointment;
    }

    /**
     * Get the message
     */
    public function getMessage(): ?string
    {
        return $this->message;
    }

    /**
     * Get the error code (if failed)
     */
    public function getErrorCode(): ?string
    {
        return $this->errorCode;
    }

    /**
     * Get metadata
     */
    public function getMetadata(): array
    {
        return $this->metadata;
    }

    /**
     * Get a specific metadata value
     */
    public function getMeta(string $key, $default = null)
    {
        return $this->metadata[$key] ?? $default;
    }

    /**
     * Check if there are warnings
     */
    public function hasWarnings(): bool
    {
        return !empty($this->warnings);
    }

    /**
     * Get warnings
     */
    public function getWarnings(): array
    {
        return $this->warnings;
    }

    /**
     * Convert to array
     */
    public function toArray(): array
    {
        $data = [
            'success' => $this->success,
            'message' => $this->message,
        ];

        if ($this->appointment) {
            $data['appointment'] = $this->appointment->toArray();
            $data['appointment_id'] = $this->appointment->id;
        }

        if ($this->errorCode) {
            $data['error_code'] = $this->errorCode;
        }

        if (!empty($this->metadata)) {
            $data['metadata'] = $this->metadata;
        }

        if (!empty($this->warnings)) {
            $data['warnings'] = $this->warnings;
        }

        return $data;
    }

    /**
     * Convert to JSON
     */
    public function toJson(): string
    {
        return json_encode($this->toArray());
    }

    /**
     * Get response status code
     */
    public function getStatusCode(): int
    {
        if ($this->success) {
            return $this->appointment ? 201 : 200;
        }

        // Map error codes to HTTP status codes
        return match ($this->errorCode) {
            'slot_unavailable', 'time_conflict' => 409,
            'invalid_data', 'missing_required_field' => 400,
            'unauthorized' => 401,
            'service_unavailable' => 503,
            default => 500,
        };
    }
}
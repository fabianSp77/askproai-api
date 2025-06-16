<?php

namespace App\Mail;

use App\Models\Appointment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class AppointmentReminder extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public Appointment $appointment;
    public string $reminderType;

    /**
     * Create a new message instance.
     */
    public function __construct(Appointment $appointment, string $reminderType)
    {
        $this->appointment = $appointment;
        $this->reminderType = $reminderType;
    }

    /**
     * Build the message.
     */
    public function build()
    {
        $subject = match($this->reminderType) {
            '24_hours' => 'Terminerinnerung: Ihr Termin morgen',
            '2_hours' => 'Terminerinnerung: Ihr Termin heute',
            default => 'Terminerinnerung'
        };

        return $this->subject($subject)
                    ->view('emails.appointments.reminder')
                    ->with([
                        'appointment' => $this->appointment,
                        'reminderType' => $this->reminderType,
                    ]);
    }
}
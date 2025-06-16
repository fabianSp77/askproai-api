<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use App\Models\Appointment;

class AppointmentBookedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public $appointment;

    public function __construct(Appointment $appointment)
    {
        $this->appointment = $appointment;
    }

    public function via($notifiable)
    {
        return ['mail'];
    }

    public function toMail($notifiable)
    {
        $customer = $this->appointment->customer;
        $branch = $this->appointment->branch ?? null;
        $company = $branch ? $branch->company : null;
        $startsAt = $this->appointment->starts_at ? $this->appointment->starts_at->format('d.m.Y H:i') : 'unbekannt';

        return (new MailMessage)
            ->subject('Neue Terminbuchung')
            ->greeting('Hallo!')
            ->line('Es wurde ein neuer Termin gebucht:')
            ->line('Kunde: ' . ($customer->name ?? 'Unbekannt'))
            ->line('E-Mail Kunde: ' . ($customer->email ?? '---'))
            ->line('Filiale: ' . ($branch->name ?? '---'))
            ->line('Datum/Zeit: ' . $startsAt)
            ->action('Zur Terminübersicht', url('/admin/appointments'))
            ->line('Viele Grüße, Dein AskProAI-System');
    }
}

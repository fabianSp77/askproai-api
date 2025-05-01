<?php

namespace App\Jobs;

use App\Models\{Integration, Call, Appointment};
use App\Services\CalcomService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ProcessRetellCallJob implements ShouldQueue
{
    use Dispatchable, Queueable, SerializesModels;

    public function __construct(private array $payload) {}

    public function handle(): void
    {
        /* 1) Call sichern */
        $call = Call::create([
            'external_id' => $this->payload['call_id']    ?? null,
            'transcript'  => $this->payload['transcript'] ?? '',
            'raw'         => $this->payload,
        ]);

        /* 2) aktive Integration holen */
        $integration = Integration::where('system','retell')
                                  ->where('active',true)
                                  ->first();
        if (! $integration) {
            return;
        }

        /* 3) Customer-ID sofort in Call schreiben */
        $call->update(['customer_id' => $integration->customer_id]);

        $cal = new CalcomService($integration);

        /* 4) erstes freies Zeitfenster */
        $slot = $cal->availability([
            'eventTypeId' => env('CALCOM_EVENT_TYPE_ID'),
        ])->json()['slots'][0] ?? null;
        if (! $slot) { return; }

        /* 5) Termin buchen */
        $booking = $cal->book([
            'eventTypeId' => env('CALCOM_EVENT_TYPE_ID'),
            'slot'        => $slot,
            'fields'      => [
                'name'  => $this->payload['caller_name']  ?? 'Unbekannt',
                'phone' => $this->payload['caller_phone'] ?? '',
            ],
            'metadata'    => ['call_id' => $call->id],
        ])->json();

        /* 6) Termin in DB spiegeln */
        Appointment::create([
            'customer_id' => $integration->customer_id,
            'external_id' => $booking['uid']   ?? null,
            'starts_at'   => $slot['start'],
            'ends_at'     => $slot['end'],
            'payload'     => $booking,
            'status'      => $booking['status'] ?? 'pending',
        ]);
    }
}

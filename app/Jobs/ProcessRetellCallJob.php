<?php
namespace App\Jobs;

use App\Models\{Appointment,Call,Integration};
use App\Services\CalcomService;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessRetellCallJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $timeout = 120;
    public $backoff = [60, 300];

    public function __construct(private array $payload){}

    public function handle(CalcomService $cal): void
    {
        $call = Call::create([
            'external_id'=>$this->payload['call_id']??null,
            'transcript' =>$this->payload['transcript']??'',
            'raw'        =>$this->payload,
        ]);

        $integration = Integration::where('system','retell')
                                  ->where('active',true)
                                  ->first();
        if(!$integration){ return; }

        $call->update(['customer_id'=>$integration->customer_id]);

        $from = now()->toIso8601String();
        $to   = now()->addWeek()->toIso8601String();
        $slots = collect($cal->availableSlots($from,$to)->json('slots'))
                 ->flatMap(fn($t)=>$t)->pluck('time');
        $start = $slots->first();
        if(!$start){
            Log::warning('[RetellJob] kein Slot');
            return;
        }

        $end = Carbon::parse($start)->addMinutes(30)->toIso8601String();

        $booking = $cal->createBooking([
            'eventTypeId'=>config('services.calcom.event_type_id'),
            'start'      =>$start,
            'end'        =>$end,
            'attendees'  =>[[
                'email'=>$this->payload['caller_email']??'',
                'name' =>$this->payload['caller_name'] ??'Unbekannt',
            ]],
            'metadata'   =>['call_id'=>$call->id],
        ])->json();

        Appointment::create([
            'customer_id'=>$integration->customer_id,
            'external_id'=>$booking['uid']??null,
            'starts_at'  =>$start,
            'ends_at'    =>$end,
            'payload'    =>$booking,
            'status'     =>$booking['status']??'pending',
        ]);
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('[ProcessRetellCallJob] failed permanently', [
            'call_id' => $this->payload['call_id'] ?? null,
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);
    }
}

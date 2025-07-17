<?php

namespace App\Mail;

use App\Models\Call;
use App\Models\Company;
use App\Services\CallExportService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Queue\SerializesModels;

class CallSummaryEmail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public $callId;
    public $call;
    public $company;
    public $includeTranscript;
    public $includeCsv;
    public $customMessage;
    public $recipientType;
    
    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 3;

    /**
     * Create a new message instance.
     */
    public function __construct(
        Call $call,
        bool $includeTranscript = true,
        bool $includeCsv = false,
        ?string $customMessage = null,
        string $recipientType = 'internal'
    ) {
        // Store only the ID to avoid serialization issues
        $this->callId = $call->id;
        $this->includeTranscript = $includeTranscript;
        $this->includeCsv = $includeCsv;
        $this->customMessage = $customMessage;
        $this->recipientType = $recipientType;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        // Load the call with all necessary relationships
        $this->call = Call::withoutGlobalScope(\App\Scopes\TenantScope::class)
            ->with(['company', 'customer', 'branch', 'charge'])
            ->findOrFail($this->callId);
        
        $this->company = $this->call->company;
        
        // Set company context for tenant scope
        if ($this->call->company_id) {
            app()->instance('current_company_id', $this->call->company_id);
        }
        
        return $this;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        // Ensure call is loaded
        if (!$this->call) {
            $this->build();
        }
        
        $subject = sprintf(
            'Anrufzusammenfassung - %s - %s',
            $this->call->customer?->name ?? $this->call->phone_number ?? 'Unbekannt',
            $this->call->created_at->format('d.m.Y H:i')
        );

        return new Envelope(
            subject: $subject,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        // Ensure call is loaded
        if (!$this->call) {
            $this->build();
        }
        
        // Prepare email data
        $emailData = [
            'call' => $this->call,
            'company' => $this->company,
            'includeTranscript' => $this->includeTranscript,
            'customMessage' => $this->customMessage,
            'sender_name' => $this->company->name,
            'sender_email' => config('mail.from.address'),
            'recipientType' => $this->recipientType,
            'callDuration' => $this->formatDuration($this->call->duration_sec),
            'hasAppointment' => $this->call->appointment_id !== null,
            'urgencyLevel' => $this->call->custom_analysis_data['urgency_level'] ?? null,
            'actionItems' => $this->extractActionItems(),
        ];

        return new Content(
            view: 'emails.call-summary',
            with: $emailData,
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        // Ensure call is loaded
        if (!$this->call) {
            $this->build();
        }
        
        $attachments = [];

        // Add CSV export if requested
        if ($this->includeCsv) {
            try {
                $exportService = new CallExportService();
                $csvContent = $exportService->exportSingleCall($this->call);
                
                $attachments[] = Attachment::fromData(
                    fn () => $csvContent,
                    sprintf('anruf_%s_%s.csv', $this->call->id, $this->call->created_at->format('Y-m-d'))
                )->withMime('text/csv');
            } catch (\Exception $e) {
                \Log::error('Failed to generate CSV attachment', [
                    'call_id' => $this->call->id,
                    'error' => $e->getMessage()
                ]);
            }
        }

        return $attachments;
    }

    /**
     * Format duration
     */
    protected function formatDuration(?int $seconds): string
    {
        if (!$seconds) {
            return '00:00';
        }

        $minutes = floor($seconds / 60);
        $secs = $seconds % 60;

        return sprintf('%02d:%02d', $minutes, $secs);
    }

    /**
     * Extract action items from call data
     */
    protected function extractActionItems(): array
    {
        $actionItems = [];

        // Check if appointment was requested but not booked
        if (!$this->call->appointment_id && (isset($this->call->metadata['appointment_intent_detected']) && $this->call->metadata['appointment_intent_detected'])) {
            $actionItems[] = [
                'type' => 'appointment_needed',
                'title' => 'Terminbuchung erforderlich',
                'description' => 'Kunde wollte einen Termin vereinbaren, aber es wurde noch keiner gebucht.',
                'priority' => 'high'
            ];
        }

        // Check if callback was requested
        if (isset($this->call->metadata['callback_requested']) && $this->call->metadata['callback_requested']) {
            $actionItems[] = [
                'type' => 'callback_needed',
                'title' => 'Rückruf erforderlich',
                'description' => 'Kunde hat um Rückruf gebeten.',
                'priority' => 'high'
            ];
        }

        // Check urgency level
        $urgencyLevel = $this->call->custom_analysis_data['urgency_level'] ?? null;
        if ($urgencyLevel === 'urgent' || $urgencyLevel === 'high' || $urgencyLevel === 'sehr dringend' || $urgencyLevel === 'dringend') {
            $actionItems[] = [
                'type' => 'urgent_followup',
                'title' => 'Dringende Nachverfolgung',
                'description' => 'Anruf wurde als dringend eingestuft.',
                'priority' => 'urgent'
            ];
        }

        // Check if customer data is incomplete
        if ($this->call->customer && (!$this->call->customer->email || !$this->call->customer->name)) {
            $actionItems[] = [
                'type' => 'data_completion',
                'title' => 'Kundendaten vervollständigen',
                'description' => 'E-Mail-Adresse oder Name des Kunden fehlt.',
                'priority' => 'medium'
            ];
        }

        return $actionItems;
    }
}
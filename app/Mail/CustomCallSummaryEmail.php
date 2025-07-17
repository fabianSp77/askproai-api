<?php

namespace App\Mail;

use App\Models\Call;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Queue\SerializesModels;
use App\Services\CallExportService;

class CustomCallSummaryEmail extends Mailable
{
    use Queueable, SerializesModels;

    protected $call;
    protected $customSubject;
    protected $htmlContent;
    protected $includeOptions;

    /**
     * Create a new message instance.
     */
    public function __construct(
        Call $call,
        string $customSubject,
        string $htmlContent,
        array $includeOptions
    ) {
        // Load all necessary relationships
        $this->call = $call->load(['company', 'customer', 'branch', 'charge']);
        $this->customSubject = $customSubject;
        $this->htmlContent = $htmlContent;
        $this->includeOptions = $includeOptions;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->customSubject,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        // Prepare data for the view
        // Generate CSV download token if CSV is included
        $csvDownloadToken = null;
        if ($this->includeOptions['attachCSV'] ?? false) {
            $csvDownloadToken = \App\Http\Controllers\Portal\PublicDownloadController::generateDownloadToken($this->call->id);
        }
        
        // Get summary and translate if needed
        $summary = $this->getSummary();
        $translatedSummary = null;
        
        if ($summary) {
            // Check if summary is in English
            if ($this->isEnglish($summary)) {
                try {
                    $translationService = app(\App\Services\TranslationService::class);
                    $translatedSummary = $translationService->translate($summary, 'de');
                } catch (\Exception $e) {
                    \Log::warning('Translation failed for email summary', ['error' => $e->getMessage()]);
                    $translatedSummary = $summary; // Fallback to original
                }
            } else {
                $translatedSummary = $summary;
            }
        }
        
        // Get and translate customer request
        $customerRequest = $this->getCustomerRequest();
        $translatedCustomerRequest = null;
        
        if ($customerRequest && $this->isEnglish($customerRequest)) {
            try {
                $translationService = app(\App\Services\TranslationService::class);
                $translatedCustomerRequest = $translationService->translate($customerRequest, 'de');
            } catch (\Exception $e) {
                \Log::warning('Translation failed for customer request', ['error' => $e->getMessage()]);
                $translatedCustomerRequest = $customerRequest; // Fallback to original
            }
        } else {
            $translatedCustomerRequest = $customerRequest;
        }
        
        // Generate CSV URL
        $csvUrl = $csvDownloadToken 
            ? config('app.url') . '/portal/public-download/' . $csvDownloadToken
            : config('app.url') . '/business/api/email/csv/' . $this->call->id;
        
        $data = [
            'call' => $this->call,
            'customContent' => $this->htmlContent,
            'includeOptions' => $this->includeOptions,
            'summary' => $summary,
            'translatedSummary' => $translatedSummary,
            'customerRequest' => $customerRequest,
            'translatedCustomerRequest' => $translatedCustomerRequest,
            'customerInfo' => $this->getCustomerInfo(),
            'appointmentInfo' => $this->getAppointmentInfo(),
            'urgency' => $this->call->urgency_level ?? $this->call->custom_analysis_data['urgency_level'] ?? null,
            'csvDownloadToken' => $csvDownloadToken,
            'csvUrl' => $csvUrl,
            'detailsUrl' => config('app.url') . '/business/calls/' . $this->call->id . '/v2',
            'audioUrl' => $this->call->recording_url,
            'transcript' => ($this->includeOptions['transcript'] ?? false) ? $this->call->transcript : null,
        ];

        return new Content(
            view: 'emails.call-summary-responsive',
            with: $data
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        $attachments = [];

        // Attach CSV if requested
        if ($this->includeOptions['attachCSV'] ?? false) {
            try {
                $exportService = app(CallExportService::class);
                $csvContent = $exportService->exportSingleCall($this->call);
                
                $attachments[] = Attachment::fromData(
                    fn () => $csvContent,
                    'anruf-' . $this->call->id . '-' . date('Y-m-d') . '.csv'
                )->withMime('text/csv');
            } catch (\Exception $e) {
                \Log::error('Failed to attach CSV', ['error' => $e->getMessage()]);
            }
        }

        // Note: Recording attachment would need to be implemented based on your storage system
        if ($this->includeOptions['attachRecording'] ?? false) {
            // TODO: Implement recording attachment if stored locally
            // This would depend on how recordings are stored in your system
        }

        return $attachments;
    }

    /**
     * Get formatted summary
     */
    private function getSummary()
    {
        if (!($this->includeOptions['summary'] ?? false)) {
            return null;
        }

        return $this->call->call_summary ?? $this->call->summary ?? $this->generateSummaryFromAnalysisData();
    }

    /**
     * Get customer information
     */
    private function getCustomerInfo()
    {
        if (!($this->includeOptions['customerInfo'] ?? false)) {
            return null;
        }

        return [
            'name' => $this->call->extracted_name ?? $this->call->custom_analysis_data['caller_full_name'] ?? 'Unbekannt',
            'phone' => $this->call->from_number,
            'email' => $this->call->extracted_email ?? $this->call->custom_analysis_data['caller_email'] ?? null,
            'company' => $this->call->custom_analysis_data['company_name'] ?? null,
        ];
    }

    /**
     * Get appointment information
     */
    private function getAppointmentInfo()
    {
        if (!($this->includeOptions['appointmentInfo'] ?? false)) {
            return null;
        }

        if (!$this->call->appointment_requested) {
            return null;
        }

        return [
            'requested' => true,
            'date' => $this->call->datum_termin,
            'time' => $this->call->uhrzeit_termin,
            'service' => $this->call->dienstleistung,
            'made' => $this->call->appointment_made,
        ];
    }
    
    /**
     * Get customer request
     */
    private function getCustomerRequest()
    {
        // Priority: custom_analysis_data > reason_for_visit > call fields
        if (isset($this->call->custom_analysis_data['customer_request']) && !empty($this->call->custom_analysis_data['customer_request'])) {
            return $this->call->custom_analysis_data['customer_request'];
        }
        
        if ($this->call->reason_for_visit) {
            return $this->call->reason_for_visit;
        }
        
        // Check if there's a customer request in metadata
        if (isset($this->call->metadata['customer_data']['customer_request'])) {
            return $this->call->metadata['customer_data']['customer_request'];
        }
        
        return null;
    }


    /**
     * Check if text is likely in English
     */
    private function isEnglish($text)
    {
        // Common English words that rarely appear in German
        $englishIndicators = [
            ' the ', ' is ', ' are ', ' was ', ' were ', ' have ', ' has ', 
            ' been ', ' their ', ' with ', ' from ', ' about ', ' would ',
            ' could ', ' should ', ' this ', ' that ', ' these ', ' those ',
            'appointment', 'schedule', 'requested', 'customer', 'call'
        ];
        
        $textLower = strtolower($text);
        $englishCount = 0;
        
        foreach ($englishIndicators as $word) {
            if (strpos($textLower, $word) !== false) {
                $englishCount++;
            }
        }
        
        // If we find 3 or more English indicators, it's likely English
        return $englishCount >= 3;
    }

    /**
     * Generate summary from analysis data
     */
    private function generateSummaryFromAnalysisData()
    {
        $analysisData = $this->call->custom_analysis_data ?? [];
        $parts = [];
        
        if (isset($analysisData['caller_full_name'])) {
            $parts[] = "Anrufer: " . $analysisData['caller_full_name'];
        }
        
        if (isset($analysisData['company_name'])) {
            $parts[] = "Firma: " . $analysisData['company_name'];
        }
        
        if (isset($analysisData['customer_request'])) {
            $parts[] = "Anliegen: " . $analysisData['customer_request'];
        }
        
        if (isset($analysisData['callback_requested']) && $analysisData['callback_requested']) {
            $parts[] = "Rückruf angefordert";
            if (isset($analysisData['preferred_callback_time'])) {
                $parts[] = "Bevorzugte Zeit: " . $analysisData['preferred_callback_time'];
            }
        }
        
        if (isset($analysisData['urgency_level']) && $analysisData['urgency_level'] !== 'Routine') {
            $parts[] = "Dringlichkeit: " . $analysisData['urgency_level'];
        }
        
        return implode('. ', $parts);
    }
}
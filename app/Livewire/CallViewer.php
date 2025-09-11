<?php

namespace App\Livewire;

use App\Models\Call;
use Livewire\Component;
use Carbon\Carbon;
use Illuminate\Support\Facades\Response;
use Barryvdh\DomPDF\Facade\Pdf;
use League\Csv\Writer;
use Livewire\Attributes\On;

class CallViewer extends Component
{
    public $callId;
    public Call $call;
    
    // UI States
    public $activeTab = 'overview';
    public $isPlaying = false;
    public $showTranscript = false;
    public $showAnalysisDetails = false;
    public $searchQuery = '';
    public $exportFormat = 'csv';
    public $showExportModal = false;
    public $autoRefresh = false;
    public $refreshInterval = 5; // seconds
    
    // Keyboard shortcuts enabled
    public $shortcutsEnabled = true;
    
    // Computed properties
    public $formattedDuration;
    public $callMetrics = [];
    public $timelineEvents = [];
    public $sentimentData = [];
    
    public function mount($callId)
    {
        $this->callId = $callId;
        $this->refreshCallData();
    }
    
    public function refreshCallData()
    {
        $this->call = Call::with(['tenant', 'customer', 'appointment', 'appointments'])
            ->findOrFail($this->callId);
            
        $this->prepareCallData();
        
        if ($this->autoRefresh) {
            $this->dispatch('call-data-refreshed');
        }
    }
    
    protected function prepareCallData()
    {
        // Format duration
        $this->formattedDuration = $this->formatDuration($this->call->duration_sec ?? 0);
        
        // Prepare metrics
        $this->callMetrics = $this->calculateMetrics();
        
        // Build timeline events
        $this->timelineEvents = $this->buildTimeline();
        
        // Process sentiment data
        $this->sentimentData = $this->processSentiment();
    }
    
    // Enable/disable auto-refresh
    public function toggleAutoRefresh()
    {
        $this->autoRefresh = !$this->autoRefresh;
        
        if ($this->autoRefresh) {
            $this->dispatch('start-polling', ['interval' => $this->refreshInterval * 1000]);
        } else {
            $this->dispatch('stop-polling');
        }
    }
    
    // Handle keyboard shortcuts
    #[On('keydown.window')]
    public function handleKeyboard($event)
    {
        if (!$this->shortcutsEnabled) return;
        
        switch($event['key']) {
            case '1':
                $this->activeTab = 'overview';
                break;
            case '2':
                $this->activeTab = 'timeline';
                break;
            case '3':
                $this->activeTab = 'transcript';
                break;
            case '4':
                $this->activeTab = 'analysis';
                break;
            case 'e':
                if ($event['ctrlKey'] || $event['metaKey']) {
                    $this->showExportModal = true;
                }
                break;
            case 'r':
                if ($event['ctrlKey'] || $event['metaKey']) {
                    $this->refreshCallData();
                }
                break;
            case '/':
                $this->dispatch('focus-search');
                break;
        }
    }
    
    // Export functionality
    public function exportCall($format = 'csv')
    {
        switch($format) {
            case 'csv':
                return $this->exportToCsv();
            case 'pdf':
                return $this->exportToPdf();
            case 'json':
                return $this->exportToJson();
            default:
                return;
        }
    }
    
    protected function exportToCsv()
    {
        $csv = Writer::createFromString();
        
        // Headers
        $csv->insertOne([
            'Call ID', 'Date', 'Duration', 'Status', 'Caller', 'Agent', 
            'Sentiment', 'Cost', 'Recording URL'
        ]);
        
        // Data
        $csv->insertOne([
            $this->call->id,
            $this->call->created_at->format('Y-m-d H:i:s'),
            $this->formattedDuration,
            $this->call->status,
            $this->getCallerInfo()['name'] ?? 'Unknown',
            $this->call->agent_name ?? 'N/A',
            $this->sentimentData['overall'] ?? 'neutral',
            $this->call->cost ?? 0,
            $this->call->recording_url ?? 'N/A'
        ]);
        
        // Add transcript if available
        if ($this->call->transcript) {
            $csv->insertOne(['', '', '', '', '', '', '', '', '']);
            $csv->insertOne(['Transcript']);
            
            $transcript = is_string($this->call->transcript) 
                ? json_decode($this->call->transcript, true) 
                : $this->call->transcript;
                
            if (is_array($transcript)) {
                foreach ($transcript as $entry) {
                    $csv->insertOne([
                        '',
                        $entry['timestamp'] ?? '',
                        '',
                        '',
                        $entry['role'] ?? '',
                        $entry['content'] ?? '',
                        '',
                        '',
                        ''
                    ]);
                }
            }
        }
        
        $filename = 'call_' . $this->call->id . '_' . date('Y-m-d_H-i-s') . '.csv';
        
        return Response::streamDownload(function() use ($csv) {
            echo $csv->toString();
        }, $filename, [
            'Content-Type' => 'text/csv',
        ]);
    }
    
    protected function exportToPdf()
    {
        $pdf = Pdf::loadView('exports.call-detail', [
            'call' => $this->call,
            'metrics' => $this->callMetrics,
            'timeline' => $this->timelineEvents,
            'sentiment' => $this->sentimentData,
            'duration' => $this->formattedDuration
        ]);
        
        $filename = 'call_' . $this->call->id . '_' . date('Y-m-d_H-i-s') . '.pdf';
        
        return $pdf->download($filename);
    }
    
    protected function exportToJson()
    {
        $data = [
            'call' => $this->call->toArray(),
            'metrics' => $this->callMetrics,
            'timeline' => $this->timelineEvents,
            'sentiment' => $this->sentimentData,
            'formatted_duration' => $this->formattedDuration
        ];
        
        $filename = 'call_' . $this->call->id . '_' . date('Y-m-d_H-i-s') . '.json';
        
        return Response::streamDownload(function() use ($data) {
            echo json_encode($data, JSON_PRETTY_PRINT);
        }, $filename, [
            'Content-Type' => 'application/json',
        ]);
    }
    
    // Search within transcript
    public function searchTranscript()
    {
        if (empty($this->searchQuery)) {
            return $this->call->transcript;
        }
        
        $transcript = is_string($this->call->transcript) 
            ? json_decode($this->call->transcript, true) 
            : $this->call->transcript;
            
        if (!is_array($transcript)) {
            return [];
        }
        
        return array_filter($transcript, function($entry) {
            return stripos($entry['content'] ?? '', $this->searchQuery) !== false;
        });
    }
    
    // Print-friendly view
    public function printView()
    {
        $this->dispatch('print-page');
    }
    
    protected function formatDuration($seconds)
    {
        if (!$seconds) return '0:00';
        
        $minutes = floor($seconds / 60);
        $secs = $seconds % 60;
        return sprintf('%d:%02d', $minutes, $secs);
    }
    
    protected function calculateMetrics()
    {
        $analysis = $this->getAnalysisData();
        
        return [
            'response_time' => $this->calculateResponseTime(),
            'resolution_rate' => $this->calculateResolutionRate(),
            'customer_satisfaction' => $this->estimateSatisfaction($analysis),
            'efficiency_score' => $this->calculateEfficiency()
        ];
    }
    
    protected function buildTimeline()
    {
        $events = [];
        $analysis = $this->getAnalysisData();
        
        // Start of call
        if ($this->call->start_timestamp) {
            $events[] = [
                'time' => '0:00',
                'type' => 'start',
                'title' => 'Anruf gestartet',
                'description' => 'Verbindung hergestellt',
                'icon' => 'phone-incoming',
                'color' => 'blue'
            ];
        }
        
        // Key moments from analysis
        if (!empty($analysis['customer_request'])) {
            $events[] = [
                'time' => '0:15',
                'type' => 'request',
                'title' => 'Kundenanliegen',
                'description' => $analysis['customer_request'],
                'icon' => 'chat-bubble-left-right',
                'color' => 'purple'
            ];
        }
        
        if (!empty($analysis['caller_full_name'])) {
            $events[] = [
                'time' => '0:30',
                'type' => 'identification',
                'title' => 'Kunde identifiziert',
                'description' => $analysis['caller_full_name'] . ' - ' . ($analysis['company_name'] ?? ''),
                'icon' => 'user-circle',
                'color' => 'green'
            ];
        }
        
        // End of call
        if ($this->call->end_timestamp) {
            $events[] = [
                'time' => $this->formattedDuration,
                'type' => 'end',
                'title' => 'Anruf beendet',
                'description' => $this->getDisconnectReason(),
                'icon' => 'phone-x-mark',
                'color' => 'red'
            ];
        }
        
        return $events;
    }
    
    protected function processSentiment()
    {
        $analysis = $this->getAnalysisData();
        $sentiment = $analysis['sentiment'] ?? 'neutral';
        
        // Calculate sentiment percentages
        $sentimentScores = [
            'positive' => 0,
            'neutral' => 0,
            'negative' => 0
        ];
        
        // Based on the sentiment, distribute scores
        switch(strtolower($sentiment)) {
            case 'positive':
                $sentimentScores = [
                    'positive' => 75,
                    'neutral' => 20,
                    'negative' => 5
                ];
                break;
            case 'neutral':
                $sentimentScores = [
                    'positive' => 25,
                    'neutral' => 60,
                    'negative' => 15
                ];
                break;
            case 'negative':
                $sentimentScores = [
                    'positive' => 10,
                    'neutral' => 25,
                    'negative' => 65
                ];
                break;
        }
        
        return [
            'overall' => $sentiment,
            'scores' => $sentimentScores,
            'confidence' => 85, // Mock confidence score
            'trend' => 'stable'
        ];
    }
    
    public function getAnalysisData()
    {
        if (!$this->call->analysis) {
            return [];
        }
        
        if (is_string($this->call->analysis)) {
            return json_decode($this->call->analysis, true) ?? [];
        }
        
        return $this->call->analysis;
    }
    
    public function getCustomAnalysisData()
    {
        $analysis = $this->getAnalysisData();
        return $analysis['custom_analysis_data'] ?? [];
    }
    
    public function getFormattedTranscript()
    {
        if (!$this->call->transcript) {
            return null;
        }
        
        $transcript = is_string($this->call->transcript) 
            ? json_decode($this->call->transcript, true) 
            : $this->call->transcript;
            
        return $transcript;
    }
    
    public function getFormattedMetadata()
    {
        if (!$this->call->metadata) {
            return null;
        }
        
        $metadata = is_string($this->call->metadata) 
            ? json_decode($this->call->metadata, true) 
            : $this->call->metadata;
            
        return json_encode($metadata, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }
    
    public function getCallDuration()
    {
        return $this->formattedDuration;
    }
    
    public function getDisconnectionReasonColor()
    {
        if (!$this->call->disconnection_reason) {
            return 'gray';
        }
        
        return match($this->call->disconnection_reason) {
            'user_hangup', 'agent_hangup' => 'success',
            'error_noanswer', 'voicemail_reached' => 'warning',
            'inactivity', 'error' => 'danger',
            'call_transfer' => 'info',
            default => 'secondary'
        };
    }
    
    protected function calculateResponseTime()
    {
        // Mock calculation - would use real data in production
        return rand(2, 8) . 's';
    }
    
    protected function calculateResolutionRate()
    {
        $analysis = $this->getAnalysisData();
        return ($analysis['call_successful'] ?? false) ? '100%' : '0%';
    }
    
    protected function estimateSatisfaction($analysis)
    {
        $sentiment = $analysis['sentiment'] ?? 'neutral';
        
        return match(strtolower($sentiment)) {
            'positive' => '95%',
            'neutral' => '75%',
            'negative' => '45%',
            default => '70%'
        };
    }
    
    protected function calculateEfficiency()
    {
        $duration = $this->call->duration_sec ?? 0;
        
        if ($duration < 60) return 'Sehr gut';
        if ($duration < 180) return 'Gut';
        if ($duration < 300) return 'Normal';
        return 'Lang';
    }
    
    public function getStatusColor()
    {
        return match($this->call->call_status) {
            'ended' => 'success',
            'error', 'failed' => 'danger',
            'in-progress', 'ongoing' => 'warning',
            'no-answer' => 'gray',
            default => 'secondary'
        };
    }
    
    public function getDisconnectReason()
    {
        if (!$this->call->disconnection_reason) {
            return 'Normal beendet';
        }
        
        return match($this->call->disconnection_reason) {
            'user_hangup' => 'Kunde hat aufgelegt',
            'agent_hangup' => 'Agent hat aufgelegt',
            'call_transfer' => 'Anruf weitergeleitet',
            'voicemail_reached' => 'Anrufbeantworter erreicht',
            'inactivity' => 'Inaktivität',
            'error_noanswer' => 'Keine Antwort',
            default => ucfirst(str_replace('_', ' ', $this->call->disconnection_reason))
        };
    }
    
    public function getCallerInfo()
    {
        $customData = $this->getCustomAnalysisData();
        
        return [
            'name' => $customData['caller_full_name'] ?? 'Unbekannt',
            'company' => $customData['company_name'] ?? null,
            'phone' => $customData['caller_phone'] ?? $this->call->from_number,
            'email' => $customData['caller_email'] ?? null,
            'customer_number' => $customData['customer_number'] ?? null,
        ];
    }
    
    public function getCallInsights()
    {
        $analysis = $this->getAnalysisData();
        $customData = $this->getCustomAnalysisData();
        
        $insights = [];
        
        // Urgency insight
        if (!empty($customData['urgency_level'])) {
            $insights[] = [
                'type' => 'urgency',
                'level' => $customData['urgency_level'],
                'message' => $this->getUrgencyMessage($customData['urgency_level']),
                'icon' => 'clock',
                'color' => $this->getUrgencyColor($customData['urgency_level'])
            ];
        }
        
        // GDPR insight
        if (isset($customData['gdpr_consent_given'])) {
            $insights[] = [
                'type' => 'gdpr',
                'status' => $customData['gdpr_consent_given'],
                'message' => $customData['gdpr_consent_given'] ? 'DSGVO-Einwilligung erteilt' : 'Keine DSGVO-Einwilligung',
                'icon' => 'shield-check',
                'color' => $customData['gdpr_consent_given'] ? 'green' : 'yellow'
            ];
        }
        
        // Callback insight
        if ($customData['callback_requested'] ?? false) {
            $insights[] = [
                'type' => 'callback',
                'time' => $customData['preferred_callback_time'],
                'message' => 'Rückruf angefordert' . ($customData['preferred_callback_time'] ? ' um ' . $customData['preferred_callback_time'] : ''),
                'icon' => 'phone-arrow-up-right',
                'color' => 'blue'
            ];
        }
        
        return $insights;
    }
    
    protected function getUrgencyMessage($level)
    {
        return match($level) {
            'sofort' => 'Sofortige Bearbeitung erforderlich',
            'bald' => 'Baldige Bearbeitung gewünscht',
            'normal' => 'Normale Priorität',
            default => ucfirst($level)
        };
    }
    
    protected function getUrgencyColor($level)
    {
        return match($level) {
            'sofort' => 'red',
            'bald' => 'yellow',
            'normal' => 'green',
            default => 'gray'
        };
    }
    
    // Livewire Actions
    public function switchTab($tab)
    {
        $this->activeTab = $tab;
    }
    
    public function toggleTranscript()
    {
        $this->showTranscript = !$this->showTranscript;
    }
    
    public function toggleAnalysis()
    {
        $this->showAnalysisDetails = !$this->showAnalysisDetails;
    }
    
    public function playRecording()
    {
        $this->isPlaying = !$this->isPlaying;
        
        if ($this->isPlaying && $this->call->recording_url) {
            $this->dispatchBrowserEvent('play-audio', ['url' => $this->call->recording_url]);
        } else {
            $this->dispatchBrowserEvent('pause-audio');
        }
    }
    
    public function shareCall()
    {
        // Share logic here
        $shareUrl = route('filament.admin.resources.calls.view', $this->call);
        $this->dispatchBrowserEvent('copy-to-clipboard', ['text' => $shareUrl]);
        session()->flash('message', 'Link in Zwischenablage kopiert');
    }
    
    public function render()
    {
        return view('livewire.call-viewer');
    }
}
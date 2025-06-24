<?php

namespace App\Filament\Admin\Pages;

use Filament\Pages\Page;
use App\Models\RetellWebhook;
use App\Models\Call;
use Illuminate\Support\Collection;

class WebhookAnalysis extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';
    protected static ?string $navigationLabel = 'Webhook Analyse';
    protected static ?string $navigationGroup = 'System & Überwachung';
    protected static string $view = 'filament.admin.pages.webhook-analysis';
    
    public function mount(): void
    {
        // Check permissions
        if (!auth()->user()->hasRole('super_admin')) {
            abort(403);
        }
    }
    
    public function getWebhookData(): array
    {
        // Get latest Retell webhook
        $latestWebhook = RetellWebhook::retell()->latest()->first();
        
        if (!$latestWebhook) {
            return [
                'hasData' => false,
                'message' => 'Keine Retell Webhooks gefunden'
            ];
        }
        
        $payload = is_string($latestWebhook->payload) 
            ? json_decode($latestWebhook->payload, true) 
            : $latestWebhook->payload;
            
        return [
            'hasData' => true,
            'webhook' => $latestWebhook,
            'payload' => $payload,
            'payloadKeys' => $payload ? array_keys($payload) : [],
            'timestamp' => $latestWebhook->created_at->format('d.m.Y H:i:s')
        ];
    }
    
    public function getCallDataAnalysis(): array
    {
        $totalCalls = Call::count();
        
        return [
            'total' => $totalCalls,
            'withTranscript' => Call::whereNotNull('transcript')->where('transcript', '!=', '')->count(),
            'withDuration' => Call::whereNotNull('duration_sec')->where('duration_sec', '>', 0)->count(),
            'withBranch' => Call::whereNotNull('branch_id')->count(),
            'withAppointment' => Call::whereNotNull('appointment_id')->count(),
            'withRecording' => Call::whereNotNull('audio_url')->where('audio_url', '!=', '')->count(),
            'withAnalysis' => Call::whereJsonLength('analysis', '>', 0)->count(),
            'withCost' => Call::whereNotNull('cost')->where('cost', '>', 0)->count(),
        ];
    }
    
    public function getMissingDataReport(): Collection
    {
        return Call::latest()
            ->limit(10)
            ->get()
            ->map(function ($call) {
                $missing = [];
                
                if (empty($call->from_number)) $missing[] = 'from_number';
                if (empty($call->to_number)) $missing[] = 'to_number';
                if (empty($call->duration_sec)) $missing[] = 'duration_sec';
                if (empty($call->transcript)) $missing[] = 'transcript';
                if (empty($call->branch_id)) $missing[] = 'branch_id';
                if (empty($call->analysis) || (is_array($call->analysis) && count($call->analysis) === 0)) $missing[] = 'analysis';
                
                return [
                    'id' => $call->id,
                    'call_id' => $call->call_id,
                    'created' => $call->created_at->format('d.m.Y H:i'),
                    'missing' => $missing,
                    'completeness' => 100 - (count($missing) * 100 / 6)
                ];
            });
    }
    
    public function getSampleWebhookStructure(): array
    {
        // Get a call with raw_data
        $callWithData = Call::whereNotNull('raw_data')
            ->where('raw_data', '!=', '')
            ->latest()
            ->first();
            
        if (!$callWithData) {
            return ['hasData' => false];
        }
        
        $rawData = $callWithData->raw_data;
        $decoded = is_string($rawData) ? json_decode($rawData, true) : $rawData;
        
        return [
            'hasData' => true,
            'callId' => $callWithData->id,
            'structure' => $this->getDataStructure($decoded),
            'sampleData' => $decoded
        ];
    }
    
    private function getDataStructure($data, $prefix = ''): array
    {
        $structure = [];
        
        if (!is_array($data)) {
            return $structure;
        }
        
        foreach ($data as $key => $value) {
            $fullKey = $prefix ? "$prefix.$key" : $key;
            
            if (is_array($value)) {
                $structure[$fullKey] = 'array (' . count($value) . ' items)';
                $structure = array_merge($structure, $this->getDataStructure($value, $fullKey));
            } else {
                $type = gettype($value);
                $sample = is_string($value) && strlen($value) > 50 
                    ? substr($value, 0, 50) . '...' 
                    : $value;
                $structure[$fullKey] = "$type: " . json_encode($sample);
            }
        }
        
        return $structure;
    }
}
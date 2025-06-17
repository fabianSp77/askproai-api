<?php
// MARKED_FOR_DELETION - 2025-06-17

namespace App\Services;
use App\Models\CallLog;
use Illuminate\Support\Facades\Log;

class RetellAIService
{
    protected $apiKey;
    protected $baseUrl;

    public function __construct()
    {
        $this->apiKey = env('RETELLAI_API_KEY');
        $this->baseUrl = env('RETELLAI_BASE_URL', 'https://api.retellai.com/v1');
    }

    public function getCalls($limit = 10)
    {
        // Mock-Daten für Tests
        return [
            'calls' => [
                [
                    'id' => 'call_123456',
                    'caller' => '+49123456789',
                    'start_time' => now()->subHours(2)->toIso8601String(),
                    'end_time' => now()->subHours(1)->toIso8601String(),
                    'duration' => 600,
                    'status' => 'completed'
                ],
                [
                    'id' => 'call_654321',
                    'caller' => '+49987654321',
                    'start_time' => now()->subDay()->toIso8601String(),
                    'end_time' => now()->subDay()->addMinutes(15)->toIso8601String(),
                    'duration' => 900,
                    'status' => 'completed'
                ]
            ],
            'meta' => [
                'total' => 2,
                'limit' => $limit
            ]
        ];
    }

    public function getCallDetails($callId)
    {
        // Mock-Daten für Tests
        return [
            'id' => $callId,
            'caller' => '+49123456789',
            'start_time' => now()->subHours(2)->toIso8601String(),
            'end_time' => now()->subHours(1)->toIso8601String(),
            'duration' => 600,
            'status' => 'completed'
        ];
    }

    public function getCallTranscript($callId)
    {
        // Mock-Daten für Tests
        return [
            'id' => $callId,
            'transcript' => [
                [
                    'speaker' => 'ai',
                    'text' => 'Hallo, wie kann ich Ihnen helfen?',
                    'timestamp' => 0
                ],
                [
                    'speaker' => 'human',
                    'text' => 'Ich möchte einen Termin für nächste Woche buchen.',
                    'timestamp' => 5
                ],
                [
                    'speaker' => 'ai',
                    'text' => 'Gerne. Welcher Tag würde Ihnen passen?',
                    'timestamp' => 10
                ]
            ]
        ];
    }
}

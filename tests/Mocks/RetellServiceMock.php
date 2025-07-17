<?php

namespace Tests\Mocks;

class RetellServiceMock
{
    public function createCall($phoneNumber, $agentId = null)
    {
        return [
            'call_id' => 'call_' . uniqid(),
            'status' => 'completed',
            'duration' => 120,
            'recording_url' => 'https://example.com/recording.mp3',
            'transcript' => 'Mock transcript for testing'
        ];
    }
    
    public function getCallDetails($callId)
    {
        return [
            'call_id' => $callId,
            'status' => 'completed',
            'duration_sec' => 180,
            'cost' => 0.50,
            'transcript' => 'Test call transcript',
            'sentiment' => 'positive',
            'recording_url' => 'https://example.com/recording.mp3'
        ];
    }
    
    public function listCalls($limit = 10)
    {
        $calls = [];
        for ($i = 0; $i < $limit; $i++) {
            $calls[] = [
                'call_id' => 'call_' . ($i + 1),
                'created_at' => now()->subMinutes($i * 10)->toIso8601String(),
                'duration_sec' => rand(60, 300),
                'status' => 'completed'
            ];
        }
        return ['calls' => $calls];
    }
}
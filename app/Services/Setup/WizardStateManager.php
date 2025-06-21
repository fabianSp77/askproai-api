<?php

namespace App\Services\Setup;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class WizardStateManager
{
    private const STATE_TTL = 7200; // 2 hours
    private string $sessionId;

    public function __construct()
    {
        $this->sessionId = $this->generateSessionId();
    }

    /**
     * Save wizard state for later resumption
     */
    public function saveState(array $data, int $currentStep = 1): void
    {
        $state = [
            'data' => $data,
            'current_step' => $currentStep,
            'user_id' => Auth::id(),
            'saved_at' => now()->toISOString(),
            'expires_at' => now()->addSeconds(self::STATE_TTL)->toISOString()
        ];

        Cache::put($this->getStateKey(), $state, self::STATE_TTL);
        
        Log::info('Wizard state saved', [
            'session_id' => $this->sessionId,
            'step' => $currentStep
        ]);
    }

    /**
     * Resume wizard from saved state
     */
    public function resumeState(): ?array
    {
        $state = Cache::get($this->getStateKey());
        
        if (!$state) {
            return null;
        }

        // Verify user ownership
        if ($state['user_id'] !== Auth::id()) {
            Log::warning('Wizard state access denied', [
                'session_id' => $this->sessionId,
                'expected_user' => $state['user_id'],
                'actual_user' => Auth::id()
            ]);
            return null;
        }

        Log::info('Wizard state resumed', [
            'session_id' => $this->sessionId,
            'step' => $state['current_step']
        ]);

        return $state;
    }

    /**
     * Clear saved state
     */
    public function clearState(): void
    {
        Cache::forget($this->getStateKey());
        
        Log::info('Wizard state cleared', [
            'session_id' => $this->sessionId
        ]);
    }

    /**
     * Check if saved state exists
     */
    public function hasState(): bool
    {
        return Cache::has($this->getStateKey());
    }

    /**
     * Auto-save state periodically
     */
    public function autoSave(array $data, int $currentStep): void
    {
        // Only save if data has changed
        $currentState = Cache::get($this->getStateKey());
        
        if (!$currentState || $currentState['data'] !== $data) {
            $this->saveState($data, $currentStep);
        }
    }

    /**
     * Get all saved wizard sessions for current user
     */
    public function getUserSessions(): array
    {
        $pattern = "wizard.state." . Auth::id() . ".*";
        $keys = Cache::getRedis()->keys($pattern);
        
        $sessions = [];
        foreach ($keys as $key) {
            $state = Cache::get($key);
            if ($state && $state['user_id'] === Auth::id()) {
                $sessions[] = [
                    'session_id' => str_replace('wizard.state.' . Auth::id() . '.', '', $key),
                    'saved_at' => $state['saved_at'],
                    'current_step' => $state['current_step'],
                    'company_name' => $state['data']['company_name'] ?? 'Unbenannt'
                ];
            }
        }
        
        return $sessions;
    }

    private function generateSessionId(): string
    {
        return session()->getId() ?: uniqid('wizard_', true);
    }

    private function getStateKey(): string
    {
        return "wizard.state." . Auth::id() . "." . $this->sessionId;
    }
}